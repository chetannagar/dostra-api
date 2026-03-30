<?php

use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('it stores a lead from the webhook endpoint', function () {
    $client = Client::factory()->create([
        'client_key' => 'client_1',
        'webhook_secret' => 'test-secret',
        'is_active' => true,
    ]);

    $payload = json_encode([
        'name' => 'John Smith',
        'email' => 'john@example.com',
        'message' => 'Need a quote',
    ], JSON_THROW_ON_ERROR);

    $timestamp = now()->timestamp;
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $client->webhook_secret);

    $response = $this->call('POST', '/api/webhook/leads', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_CLIENT_KEY' => $client->client_key,
        'HTTP_X_WEBHOOK_ID' => 'evt_example_test',
        'HTTP_X_WEBHOOK_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
    ], $payload);

    $response
        ->assertCreated()
        ->assertJsonPath('message', 'Lead received successfully')
        ->assertJsonPath('data.client_id', $client->id)
        ->assertJsonPath('data.source', 'wpforms-zapier');

    $this->assertDatabaseHas('leads', [
        'client_id' => $client->id,
        'email' => 'john@example.com',
        'source' => 'wpforms-zapier',
    ]);

    expect(Lead::query()->sole()->raw_payload)->toMatchArray(json_decode($payload, true, 512, JSON_THROW_ON_ERROR));
});

test('it returns leads ordered from newest to oldest', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $olderClient = Client::factory()->create();
    $newerClient = Client::factory()->create();

    $olderLead = Lead::query()->create([
        'client_id' => $olderClient->id,
        'name' => 'Older Lead',
        'email' => 'older@example.com',
        'message' => 'Older message',
        'source' => 'wpforms-zapier',
        'raw_payload' => ['position' => 'older'],
    ]);

    $newerLead = Lead::query()->create([
        'client_id' => $newerClient->id,
        'name' => 'Newer Lead',
        'email' => 'newer@example.com',
        'message' => 'Newer message',
        'source' => 'wpforms-zapier',
        'raw_payload' => ['position' => 'newer'],
    ]);

    $olderLead->forceFill([
        'created_at' => now()->subMinute(),
        'updated_at' => now()->subMinute(),
    ])->save();

    $newerLead->forceFill([
        'created_at' => now(),
        'updated_at' => now(),
    ])->save();

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/leads');

    $response
        ->assertOk()
        ->assertJsonPath('data.0.id', $newerLead->id)
        ->assertJsonPath('data.1.id', $olderLead->id);

});

test('it returns a single lead by route model binding', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $client = Client::factory()->create();

    $lead = Lead::query()->create([
        'client_id' => $client->id,
        'name' => 'Single Lead',
        'email' => 'single@example.com',
        'message' => 'Single message',
        'source' => 'wpforms-zapier',
        'raw_payload' => ['type' => 'single'],
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson("/api/leads/{$lead->id}");

    $response
        ->assertOk()
        ->assertJsonPath('id', $lead->id)
        ->assertJsonPath('client_id', $client->id);
});
