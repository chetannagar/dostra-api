<?php

use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('an admin can view leads for all clients', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $firstClient = Client::factory()->create();
    $secondClient = Client::factory()->create();

    $firstLead = Lead::query()->create([
        'client_id' => $firstClient->id,
        'name' => 'First Lead',
        'email' => 'first@example.com',
        'message' => 'First message',
        'source' => 'wpforms-zapier',
        'raw_payload' => ['position' => 'first'],
    ]);

    $secondLead = Lead::query()->create([
        'client_id' => $secondClient->id,
        'name' => 'Second Lead',
        'email' => 'second@example.com',
        'message' => 'Second message',
        'source' => 'wpforms-zapier',
        'raw_payload' => ['position' => 'second'],
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/leads');

    $response
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonFragment(['id' => $firstLead->id])
        ->assertJsonFragment(['id' => $secondLead->id]);
});

test('a client user only sees leads for assigned clients', function () {
    $clientUser = User::factory()->create([
        'role' => 'client',
    ]);

    $assignedClient = Client::factory()->create();
    $otherClient = Client::factory()->create();

    $clientUser->clients()->attach($assignedClient->id);

    $assignedLead = Lead::query()->create([
        'client_id' => $assignedClient->id,
        'name' => 'Assigned Lead',
        'email' => 'assigned@example.com',
        'message' => 'Assigned message',
        'source' => 'wpforms-zapier',
        'raw_payload' => ['scope' => 'assigned'],
    ]);

    Lead::query()->create([
        'client_id' => $otherClient->id,
        'name' => 'Other Lead',
        'email' => 'other@example.com',
        'message' => 'Other message',
        'source' => 'wpforms-zapier',
        'raw_payload' => ['scope' => 'other'],
    ]);

    Sanctum::actingAs($clientUser);

    $response = $this->getJson('/api/leads');

    $response
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $assignedLead->id)
        ->assertJsonPath('0.client_id', $assignedClient->id);
});

test('a client user cannot view another clients lead', function () {
    $clientUser = User::factory()->create([
        'role' => 'client',
    ]);

    $assignedClient = Client::factory()->create();
    $otherClient = Client::factory()->create();

    $clientUser->clients()->attach($assignedClient->id);

    $lead = Lead::query()->create([
        'client_id' => $otherClient->id,
        'name' => 'Forbidden Lead',
        'email' => 'forbidden@example.com',
        'message' => 'Forbidden message',
        'source' => 'wpforms-zapier',
        'raw_payload' => ['scope' => 'forbidden'],
    ]);

    Sanctum::actingAs($clientUser);

    $this->getJson("/api/leads/{$lead->id}")
        ->assertForbidden();
});

test('unauthenticated users cannot access lead endpoints', function () {
    $client = Client::factory()->create();
    $lead = Lead::query()->create([
        'client_id' => $client->id,
        'name' => 'Unauthenticated Lead',
        'email' => 'unauthenticated@example.com',
        'message' => 'Unauthenticated message',
        'source' => 'wpforms-zapier',
        'raw_payload' => ['scope' => 'unauthenticated'],
    ]);

    $this->getJson('/api/leads')
        ->assertUnauthorized();

    $this->getJson("/api/leads/{$lead->id}")
        ->assertUnauthorized();
});
