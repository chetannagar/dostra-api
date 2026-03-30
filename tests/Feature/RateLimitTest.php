<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('login is rate limited after too many attempts', function () {
    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/login', [
            'email' => 'missing@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    $this->postJson('/api/login', [
        'email' => 'missing@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(429);
});

test('webhook is rate limited after too many requests for the same client key', function () {
    $client = Client::factory()->create([
        'client_key' => 'rate_limit_client',
        'webhook_secret' => 'test-secret',
        'is_active' => true,
    ]);

    $payload = json_encode([
        'name' => 'Rate Limited Lead',
        'email' => 'lead@example.com',
        'message' => 'Need a quote',
    ], JSON_THROW_ON_ERROR);

    foreach (range(1, 60) as $attempt) {
        $timestamp = now()->timestamp;
        $eventId = "evt_rate_limit_{$attempt}";
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $client->webhook_secret);

        $this->call('POST', '/api/webhook/leads', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_CLIENT_KEY' => $client->client_key,
            'HTTP_X_WEBHOOK_ID' => $eventId,
            'HTTP_X_WEBHOOK_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
        ], $payload)->assertCreated();
    }

    $timestamp = now()->timestamp;
    $eventId = 'evt_rate_limit_blocked';
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $client->webhook_secret);

    $this->call('POST', '/api/webhook/leads', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_CLIENT_KEY' => $client->client_key,
        'HTTP_X_WEBHOOK_ID' => $eventId,
        'HTTP_X_WEBHOOK_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
    ], $payload)->assertStatus(429);
});

test('authenticated crm api is rate limited after too many requests', function () {
    $user = User::factory()->create([
        'role' => 'admin',
    ]);

    Sanctum::actingAs($user);

    foreach (range(1, 120) as $attempt) {
        $this->getJson('/api/me')->assertOk();
    }

    $this->getJson('/api/me')->assertStatus(429);
});
