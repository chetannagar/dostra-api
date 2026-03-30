<?php

use App\Models\Client;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function signedWebhookHeaders(Client $client, string $payload, int $timestamp, string $eventId = 'evt_123'): array
{
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $client->webhook_secret);

    return [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_CLIENT_KEY' => $client->client_key,
        'HTTP_X_WEBHOOK_ID' => $eventId,
        'HTTP_X_WEBHOOK_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
    ];
}

test('it stores a lead from a valid signed webhook request', function () {
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

    $response = $this->call(
        'POST',
        '/api/webhook/leads',
        [],
        [],
        [],
        signedWebhookHeaders($client, $payload, now()->timestamp),
        $payload,
    );

    $response
        ->assertCreated()
        ->assertJsonPath('data.client_id', $client->id);

    expect(Lead::query()->count())->toBe(1);
});

test('it rejects a webhook with an invalid signature', function () {
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

    $response = $this->call('POST', '/api/webhook/leads', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_CLIENT_KEY' => $client->client_key,
        'HTTP_X_WEBHOOK_ID' => 'evt_invalid_signature',
        'HTTP_X_WEBHOOK_TIMESTAMP' => (string) now()->timestamp,
        'HTTP_X_WEBHOOK_SIGNATURE' => 'invalid-signature',
    ], $payload);

    $response->assertUnauthorized();

    expect(Lead::query()->count())->toBe(0);
});
