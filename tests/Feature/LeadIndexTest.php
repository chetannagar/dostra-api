<?php

use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('an admin can paginate all leads', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $client = Client::factory()->create();

    foreach (range(1, 20) as $index) {
        Lead::query()->create([
            'client_id' => $client->id,
            'name' => "Lead {$index}",
            'email' => "lead{$index}@example.com",
            'message' => "Message {$index}",
            'source' => 'wpforms-zapier',
            'raw_payload' => ['index' => $index],
        ]);
    }

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/leads?per_page=5');

    $response
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.per_page', 5)
        ->assertJsonPath('meta.total', 20);
});

test('an admin can filter leads by client', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $firstClient = Client::factory()->create();
    $secondClient = Client::factory()->create();

    $firstLead = Lead::query()->create([
        'client_id' => $firstClient->id,
        'name' => 'First Client Lead',
        'email' => 'first@example.com',
        'message' => 'First message',
        'source' => 'wpforms-zapier',
        'raw_payload' => ['client' => 'first'],
    ]);

    Lead::query()->create([
        'client_id' => $secondClient->id,
        'name' => 'Second Client Lead',
        'email' => 'second@example.com',
        'message' => 'Second message',
        'source' => 'wpforms-zapier',
        'raw_payload' => ['client' => 'second'],
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson("/api/leads?client_id={$firstClient->id}");

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $firstLead->id)
        ->assertJsonPath('data.0.client_id', $firstClient->id);
});

test('a client user only sees leads for assigned clients even when filtering', function () {
    $clientUser = User::factory()->create([
        'role' => 'client',
    ]);

    $assignedClient = Client::factory()->create();
    $otherClient = Client::factory()->create();

    $clientUser->clients()->attach($assignedClient->id);

    Lead::query()->create([
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

    $response = $this->getJson("/api/leads?client_id={$otherClient->id}");

    $response
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0);
});

test('an admin can search leads by name email or message', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $client = Client::factory()->create();

    Lead::query()->create([
        'client_id' => $client->id,
        'name' => 'John Smith',
        'email' => 'john@example.com',
        'message' => 'Needs a plumbing quote',
        'source' => 'wpforms-zapier',
        'raw_payload' => ['match' => 'yes'],
    ]);

    Lead::query()->create([
        'client_id' => $client->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'message' => 'General enquiry',
        'source' => 'wpforms-zapier',
        'raw_payload' => ['match' => 'no'],
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/leads?search=plumbing');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.email', 'john@example.com');
});

test('an admin can filter leads by email', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $client = Client::factory()->create();

    Lead::query()->create([
        'client_id' => $client->id,
        'name' => 'John Smith',
        'email' => 'john@example.com',
        'message' => 'Message one',
        'source' => 'wpforms-zapier',
        'raw_payload' => ['match' => 'john'],
    ]);

    Lead::query()->create([
        'client_id' => $client->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'message' => 'Message two',
        'source' => 'wpforms-zapier',
        'raw_payload' => ['match' => 'jane'],
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/leads?email=jane@example.com');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Jane Doe');
});

test('an admin can filter leads by created at date range', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $client = Client::factory()->create();

    $olderLead = Lead::query()->create([
        'client_id' => $client->id,
        'name' => 'Older Lead',
        'email' => 'older@example.com',
        'message' => 'Older message',
        'source' => 'wpforms-zapier',
        'raw_payload' => ['age' => 'older'],
    ]);

    $olderLead->forceFill([
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ])->save();

    $newerLead = Lead::query()->create([
        'client_id' => $client->id,
        'name' => 'Newer Lead',
        'email' => 'newer@example.com',
        'message' => 'Newer message',
        'source' => 'wpforms-zapier',
        'raw_payload' => ['age' => 'newer'],
    ]);

    $newerLead->forceFill([
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ])->save();

    Sanctum::actingAs($admin);

    $from = now()->subDays(2)->toDateString();
    $to = now()->toDateString();

    $response = $this->getJson("/api/leads?from={$from}&to={$to}");

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $newerLead->id);
});

test('an unauthenticated user cannot access the paginated lead index', function () {
    $this->getJson('/api/leads')
        ->assertUnauthorized();
});
