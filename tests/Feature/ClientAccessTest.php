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
});
