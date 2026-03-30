<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('an admin can list all clients', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $firstClient = Client::factory()->create([
        'name' => 'First Client',
        'client_key' => 'first_client',
    ]);

    $secondClient = Client::factory()->create([
        'name' => 'Second Client',
        'client_key' => 'second_client',
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/clients');

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'id' => $firstClient->id,
            'name' => 'First Client',
            'client_key' => 'first_client',
        ])
        ->assertJsonFragment([
            'id' => $secondClient->id,
            'name' => 'Second Client',
            'client_key' => 'second_client',
        ]);
});

test('a client user only sees assigned clients', function () {
    $clientUser = User::factory()->create([
        'role' => 'client',
    ]);

    $assignedClient = Client::factory()->create([
        'name' => 'Assigned Client',
        'client_key' => 'assigned_client',
    ]);

    $otherClient = Client::factory()->create([
        'name' => 'Other Client',
        'client_key' => 'other_client',
    ]);

    $clientUser->clients()->attach($assignedClient->id);

    Sanctum::actingAs($clientUser);

    $response = $this->getJson('/api/clients');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $assignedClient->id)
        ->assertJsonPath('data.0.name', 'Assigned Client')
        ->assertJsonPath('data.0.client_key', 'assigned_client');

    $response->assertJsonMissing([
        'id' => $otherClient->id,
        'name' => 'Other Client',
        'client_key' => 'other_client',
    ]);
});

test('an admin can view a single client', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $client = Client::factory()->create([
        'name' => 'Viewable Client',
        'client_key' => 'viewable_client',
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson("/api/clients/{$client->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $client->id)
        ->assertJsonPath('data.name', 'Viewable Client')
        ->assertJsonPath('data.client_key', 'viewable_client');
});

test('a client user can view an assigned client', function () {
    $clientUser = User::factory()->create([
        'role' => 'client',
    ]);

    $client = Client::factory()->create([
        'name' => 'Assigned Client',
        'client_key' => 'assigned_client',
    ]);

    $clientUser->clients()->attach($client->id);

    Sanctum::actingAs($clientUser);

    $response = $this->getJson("/api/clients/{$client->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $client->id)
        ->assertJsonPath('data.name', 'Assigned Client');
});

test('an admin can create a client', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/clients', [
        'name' => 'Created Client',
        'client_key' => 'created_client',
        'is_active' => true,
        'webhook_secret' => 'super-secret-created-client-key-123456',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'Created Client')
        ->assertJsonPath('data.client_key', 'created_client')
        ->assertJsonPath('data.is_active', true);

    $this->assertDatabaseHas('clients', [
        'name' => 'Created Client',
        'client_key' => 'created_client',
        'is_active' => true,
    ]);
});

test('an admin can create a client with an auto generated webhook secret', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/clients', [
        'name' => 'Generated Secret Client',
        'client_key' => 'generated_secret_client',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'Generated Secret Client')
        ->assertJsonPath('data.client_key', 'generated_secret_client')
        ->assertJsonPath('data.is_active', true);

    $client = Client::query()
        ->where('client_key', 'generated_secret_client')
        ->firstOrFail();

    expect(strlen($client->webhook_secret))->toBe(64);
});

test('an admin can update a client', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $client = Client::factory()->create([
        'name' => 'Old Client',
        'client_key' => 'old_client',
        'is_active' => true,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->putJson("/api/clients/{$client->id}", [
        'name' => 'Updated Client',
        'client_key' => 'updated_client',
        'is_active' => false,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Client')
        ->assertJsonPath('data.client_key', 'updated_client')
        ->assertJsonPath('data.is_active', false);

    $this->assertDatabaseHas('clients', [
        'id' => $client->id,
        'name' => 'Updated Client',
        'client_key' => 'updated_client',
        'is_active' => false,
    ]);
});

test('an admin can update a clients webhook secret', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $client = Client::factory()->create([
        'webhook_secret' => 'old-secret-value-12345678901234567890',
    ]);

    Sanctum::actingAs($admin);

    $this->putJson("/api/clients/{$client->id}", [
        'webhook_secret' => 'new-secret-value-12345678901234567890',
    ])->assertOk();

    expect($client->fresh()->webhook_secret)
        ->toBe('new-secret-value-12345678901234567890');
});

test('a client user cannot create a client', function () {
    $clientUser = User::factory()->create([
        'role' => 'client',
    ]);

    Sanctum::actingAs($clientUser);

    $this->postJson('/api/clients', [
        'name' => 'Blocked Client',
        'client_key' => 'blocked_client',
    ])->assertForbidden();
});

test('a client user cannot update another client', function () {
    $clientUser = User::factory()->create([
        'role' => 'client',
    ]);

    $client = Client::factory()->create();

    Sanctum::actingAs($clientUser);

    $this->putJson("/api/clients/{$client->id}", [
        'name' => 'Blocked Update',
    ])->assertForbidden();
});

test('a client user cannot view another client', function () {
    $clientUser = User::factory()->create([
        'role' => 'client',
    ]);

    $assignedClient = Client::factory()->create();
    $otherClient = Client::factory()->create();

    $clientUser->clients()->attach($assignedClient->id);

    Sanctum::actingAs($clientUser);

    $this->getJson("/api/clients/{$otherClient->id}")
        ->assertForbidden();
});

test('an unauthenticated user cannot access client endpoints', function () {
    $client = Client::factory()->create();

    $this->getJson('/api/clients')
        ->assertUnauthorized();

    $this->getJson("/api/clients/{$client->id}")
        ->assertUnauthorized();

    $this->postJson('/api/clients', [
        'name' => 'Blocked Client',
        'client_key' => 'blocked_client',
    ])->assertUnauthorized();

    $this->putJson("/api/clients/{$client->id}", [
        'name' => 'Blocked Update',
    ])->assertUnauthorized();
});
