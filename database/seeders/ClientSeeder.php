<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $firstClient = Client::query()->updateOrCreate(
            ['client_key' => 'client_1'],
            [
                'name' => 'Client 1',
                'webhook_secret' => 'super-secret-test-key-123',
                'is_active' => true,
            ],
        );

        $secondClient = Client::query()->updateOrCreate(
            ['client_key' => 'client_2'],
            [
                'name' => 'Client 2',
                'webhook_secret' => 'super-secret-test-key-456',
                'is_active' => true,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'superuser@example.com'],
            [
                'name' => 'Super User',
                'password' => 'password',
                'role' => 'super_user',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => 'password',
                'role' => 'admin',
            ],
        );

        $firstClientUser = User::query()->updateOrCreate(
            ['email' => 'client@example.com'],
            [
                'name' => 'Client User',
                'password' => 'password',
                'role' => 'client',
            ],
        );

        $secondClientUser = User::query()->updateOrCreate(
            ['email' => 'client2@example.com'],
            [
                'name' => 'Client User 2',
                'password' => 'password',
                'role' => 'client',
            ],
        );

        $firstClient->users()->syncWithoutDetaching([$firstClientUser->id]);
        $secondClient->users()->syncWithoutDetaching([$secondClientUser->id]);

        Lead::query()->updateOrCreate(
            ['webhook_event_id' => 'seed-client-1-lead-1'],
            [
                'client_id' => $firstClient->id,
                'name' => 'John Smith',
                'email' => 'john@example.com',
                'message' => 'Need a plumbing quote',
                'source' => 'wpforms-zapier',
                'raw_payload' => [
                    'name' => 'John Smith',
                    'email' => 'john@example.com',
                    'message' => 'Need a plumbing quote',
                ],
            ],
        );

        Lead::query()->updateOrCreate(
            ['webhook_event_id' => 'seed-client-2-lead-1'],
            [
                'client_id' => $secondClient->id,
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'message' => 'Interested in a maintenance plan',
                'source' => 'wpforms-zapier',
                'raw_payload' => [
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                    'message' => 'Interested in a maintenance plan',
                ],
            ],
        );
    }
}
