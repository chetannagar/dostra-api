<?php

use App\Models\Client;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function webhookHeaders(Client $client, string $payload, int $timestamp, string $eventId): array
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

test('it does not create duplicate leads for the same webhook event id', function () {
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
    $eventId = 'evt_duplicate_test';

    $firstResponse = $this->call(
        'POST',
        '/api/webhook/leads',
        [],
        [],
        [],
        webhookHeaders($client, $payload, $timestamp, $eventId),
        $payload,
    );

    $secondResponse = $this->call(
        'POST',
        '/api/webhook/leads',
        [],
        [],
        [],
        webhookHeaders($client, $payload, $timestamp, $eventId),
        $payload,
    );

    $firstResponse
        ->assertCreated()
        ->assertJsonPath('message', 'Lead received successfully');

    $secondResponse
        ->assertOk()
        ->assertJsonPath('message', 'Webhook event already processed.');

    expect(Lead::query()->count())->toBe(1);

    $lead = Lead::query()->sole();

    expect($lead->webhook_event_id)->toBe($eventId);
});

test('it rejects a webhook when the event id header is missing', function () {
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
        'HTTP_X_WEBHOOK_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
    ], $payload);

    $response->assertUnauthorized();

    expect(Lead::query()->count())->toBe(0);
});
