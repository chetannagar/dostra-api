<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

test('a user can log out and revoke the current token', function () {
    $user = User::factory()->create([
        'role' => 'client',
    ]);

    $token = $user->createToken('crm');

    $response = $this->withToken($token->plainTextToken)
        ->postJson('/api/logout');

    $response->assertOk();

    expect($user->tokens()->count())->toBe(0);
});
