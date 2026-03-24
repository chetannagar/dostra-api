<?php

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it stores a lead from the webhook endpoint', function () {
    $payload = [
        'client_id' => 'client_1',
        'name' => 'John Smith',
        'email' => 'john@example.com',
        'message' => 'Need a quote',
    ];

    $response = $this->postJson('/webhook/leads', $payload);

    $response
        ->assertCreated()
        ->assertJsonPath('message', 'Lead received successfully')
        ->assertJsonPath('data.client_id', 'client_1')
        ->assertJsonPath('data.source', 'wpforms-zapier');

    $this->assertDatabaseHas('leads', [
        'client_id' => 'client_1',
        'email' => 'john@example.com',
        'source' => 'wpforms-zapier',
    ]);

    expect(Lead::query()->sole()->raw_payload)->toMatchArray($payload);
});

test('it returns leads ordered from newest to oldest', function () {
    $olderLead = Lead::query()->create([
        'client_id' => 'client_1',
        'name' => 'Older Lead',
        'email' => 'older@example.com',
        'message' => 'Older message',
        'source' => 'wpforms-zapier',
        'raw_payload' => ['position' => 'older'],
    ]);

    $newerLead = Lead::query()->create([
        'client_id' => 'client_2',
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

    $response = $this->getJson('/leads');

    $response
        ->assertOk()
        ->assertJsonPath('0.id', $newerLead->id)
        ->assertJsonPath('1.id', $olderLead->id);
});

test('it returns a single lead by route model binding', function () {
    $lead = Lead::query()->create([
        'client_id' => 'client_1',
        'name' => 'Single Lead',
        'email' => 'single@example.com',
        'message' => 'Single message',
        'source' => 'wpforms-zapier',
        'raw_payload' => ['type' => 'single'],
    ]);

    $response = $this->getJson("/leads/{$lead->id}");

    $response
        ->assertOk()
        ->assertJsonPath('id', $lead->id)
        ->assertJsonPath('client_id', 'client_1');
});
