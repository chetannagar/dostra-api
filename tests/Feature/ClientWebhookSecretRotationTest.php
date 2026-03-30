<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('an admin can rotate a clients webhook secret', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $client = Client::factory()->create([
        'webhook_secret' => 'old-secret-value-123456789012345678901234567890',
    ]);

    Sanctum::actingAs($admin);

    $response = $this->postJson("/api/clients/{$client->id}/rotate-webhook-secret");

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Webhook secret rotated successfully.')
        ->assertJsonPath('data.id', $client->id)
        ->assertJsonPath('data.client_key', $client->client_key);

    $client->refresh();

    expect($client->webhook_secret)
        ->not->toBe('old-secret-value-123456789012345678901234567890');

    expect(strlen($client->webhook_secret))->toBe(64);

    expect($response->json('data.webhook_secret'))
        ->toBe($client->webhook_secret);
});

test('a client user cannot rotate a webhook secret', function () {
    $clientUser = User::factory()->create([
        'role' => 'client',
    ]);

    $client = Client::factory()->create();

    Sanctum::actingAs($clientUser);

    $this->postJson("/api/clients/{$client->id}/rotate-webhook-secret")
        ->assertForbidden();
});

test('an unauthenticated user cannot rotate a webhook secret', function () {
    $client = Client::factory()->create();

    $this->postJson("/api/clients/{$client->id}/rotate-webhook-secret")
        ->assertUnauthorized();
});

test('client resource responses do not expose webhook secrets', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $client = Client::factory()->create([
        'webhook_secret' => 'hidden-secret-value-123456789012345678901234567890',
    ]);

    Sanctum::actingAs($admin);

    $this->getJson("/api/clients/{$client->id}")
        ->assertOk()
        ->assertJsonMissingPath('data.webhook_secret');

    $this->getJson('/api/clients')
        ->assertOk()
        ->assertJsonMissingPath('data.0.webhook_secret');
});
