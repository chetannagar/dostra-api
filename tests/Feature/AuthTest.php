<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

test('a user can log in and receive an api token', function () {
    $client = Client::factory()->create();

    $user = User::factory()->create([
        'email' => 'client@example.com',
        'password' => 'password',
        'role' => 'client',
    ]);

    $user->clients()->attach($client->id);

    $response = $this->postJson('/api/login', [
        'email' => 'client@example.com',
        'password' => 'password',
    ]);

    $response
        ->assertOk()
        ->assertJsonStructure([
            'token',
            'user' => [
                'id',
                'name',
                'email',
                'role',
                'clients',
            ],
        ])
        ->assertJsonPath('user.email', 'client@example.com')
        ->assertJsonPath('user.role', 'client');

    expect($user->fresh()->tokens()->count())->toBe(1);
});

test('login fails for an unknown email address', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'missing@example.com',
        'password' => 'password',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('message', 'Invalid credentials.');
});

test('login fails for an invalid password', function () {
    User::factory()->create([
        'email' => 'client@example.com',
        'password' => 'password',
        'role' => 'client',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'client@example.com',
        'password' => 'wrong-password',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('message', 'Invalid credentials.');
});

test('a user can fetch their profile with a bearer token', function () {
    $client = Client::factory()->create();

    $user = User::factory()->create([
        'role' => 'client',
    ]);

    $user->clients()->attach($client->id);

    $token = $user->createToken('crm')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/me');

    $response
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('clients.0.id', $client->id);
});

test('an unauthenticated user cannot fetch their profile', function () {
    $this->getJson('/api/me')
        ->assertUnauthorized();
});

test('a user can log out and revoke the current token', function () {
    $user = User::factory()->create([
        'role' => 'client',
    ]);

    $token = $user->createToken('crm');

    $response = $this->withToken($token->plainTextToken)
        ->postJson('/api/logout');

    $response->assertOk();

    expect($user->fresh()->tokens()->count())->toBe(0);
});

test('a revoked token cannot be reused after logout', function () {
    $user = User::factory()->create([
        'role' => 'client',
    ]);

    $token = $user->createToken('crm');

    $this->withToken($token->plainTextToken)
        ->postJson('/api/logout')
        ->assertOk();

    Auth::forgetGuards();

    $this->withToken($token->plainTextToken)
        ->getJson('/api/me')
        ->assertUnauthorized();
});
