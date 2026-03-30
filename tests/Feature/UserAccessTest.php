<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('an admin can list all users', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $client = Client::factory()->create();

    $clientUser = User::factory()->create([
        'role' => 'client',
    ]);

    $clientUser->clients()->attach($client->id);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/users');

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'email' => $admin->email,
            'role' => 'admin',
        ])
        ->assertJsonFragment([
            'email' => $clientUser->email,
            'role' => 'client',
        ]);
});

test('an admin can view a single user', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $client = Client::factory()->create();

    $clientUser = User::factory()->create([
        'role' => 'client',
    ]);

    $clientUser->clients()->attach($client->id);

    Sanctum::actingAs($admin);

    $response = $this->getJson("/api/users/{$clientUser->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $clientUser->id)
        ->assertJsonPath('data.email', $clientUser->email)
        ->assertJsonPath('data.role', 'client')
        ->assertJsonPath('data.clients.0.id', $client->id);
});

test('an admin can create a client user with assigned clients', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $firstClient = Client::factory()->create();
    $secondClient = Client::factory()->create();

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/users', [
        'name' => 'Managed Client User',
        'email' => 'managed-client@example.com',
        'password' => 'password123',
        'role' => 'client',
        'client_ids' => [$firstClient->id, $secondClient->id],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.email', 'managed-client@example.com')
        ->assertJsonPath('data.role', 'client')
        ->assertJsonCount(2, 'data.clients');

    $this->assertDatabaseHas('users', [
        'email' => 'managed-client@example.com',
        'role' => 'client',
    ]);

    $user = User::query()->where('email', 'managed-client@example.com')->firstOrFail();

    expect($user->clients()->pluck('clients.id')->all())
        ->toMatchArray([$firstClient->id, $secondClient->id]);
});

test('an admin can create an admin user without client assignments', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $client = Client::factory()->create();

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/users', [
        'name' => 'Managed Admin User',
        'email' => 'managed-admin@example.com',
        'password' => 'password123',
        'role' => 'admin',
        'client_ids' => [$client->id],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.email', 'managed-admin@example.com')
        ->assertJsonPath('data.role', 'admin')
        ->assertJsonCount(0, 'data.clients');

    $user = User::query()->where('email', 'managed-admin@example.com')->firstOrFail();

    expect($user->clients()->count())->toBe(0);
});

test('an admin can update a client users assignments', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $firstClient = Client::factory()->create();
    $secondClient = Client::factory()->create();

    $clientUser = User::factory()->create([
        'role' => 'client',
    ]);

    $clientUser->clients()->attach($firstClient->id);

    Sanctum::actingAs($admin);

    $response = $this->putJson("/api/users/{$clientUser->id}", [
        'name' => 'Updated Client User',
        'role' => 'client',
        'client_ids' => [$secondClient->id],
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Client User')
        ->assertJsonPath('data.role', 'client')
        ->assertJsonCount(1, 'data.clients')
        ->assertJsonPath('data.clients.0.id', $secondClient->id);

    $clientUser->refresh();

    expect($clientUser->clients()->pluck('clients.id')->all())
        ->toBe([$secondClient->id]);
});

test('an admin can promote a client user to admin and clear client assignments', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $client = Client::factory()->create();

    $clientUser = User::factory()->create([
        'role' => 'client',
    ]);

    $clientUser->clients()->attach($client->id);

    Sanctum::actingAs($admin);

    $response = $this->putJson("/api/users/{$clientUser->id}", [
        'role' => 'admin',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.role', 'admin')
        ->assertJsonCount(0, 'data.clients');

    $clientUser->refresh();

    expect($clientUser->clients()->count())->toBe(0);
});

test('a client user cannot list users', function () {
    $clientUser = User::factory()->create([
        'role' => 'client',
    ]);

    Sanctum::actingAs($clientUser);

    $this->getJson('/api/users')
        ->assertForbidden();
});

test('a client user cannot view another user', function () {
    $clientUser = User::factory()->create([
        'role' => 'client',
    ]);

    $otherUser = User::factory()->create([
        'role' => 'client',
    ]);

    Sanctum::actingAs($clientUser);

    $this->getJson("/api/users/{$otherUser->id}")
        ->assertForbidden();
});

test('a client user cannot create a user', function () {
    $clientUser = User::factory()->create([
        'role' => 'client',
    ]);

    Sanctum::actingAs($clientUser);

    $this->postJson('/api/users', [
        'name' => 'Blocked User',
        'email' => 'blocked@example.com',
        'password' => 'password123',
        'role' => 'client',
    ])->assertForbidden();
});

test('a client user cannot update a user', function () {
    $clientUser = User::factory()->create([
        'role' => 'client',
    ]);

    $otherUser = User::factory()->create([
        'role' => 'client',
    ]);

    Sanctum::actingAs($clientUser);

    $this->putJson("/api/users/{$otherUser->id}", [
        'name' => 'Blocked Update',
    ])->assertForbidden();
});

test('an unauthenticated user cannot access user endpoints', function () {
    $user = User::factory()->create([
        'role' => 'client',
    ]);

    $this->getJson('/api/users')
        ->assertUnauthorized();

    $this->getJson("/api/users/{$user->id}")
        ->assertUnauthorized();

    $this->postJson('/api/users', [
        'name' => 'Blocked User',
        'email' => 'blocked@example.com',
        'password' => 'password123',
        'role' => 'client',
    ])->assertUnauthorized();

    $this->putJson("/api/users/{$user->id}", [
        'name' => 'Blocked Update',
    ])->assertUnauthorized();
});
