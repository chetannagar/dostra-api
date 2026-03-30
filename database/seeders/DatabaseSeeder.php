<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $client = Client::query()->updateOrCreate(
            ['client_key' => 'client_1'],
            [
                'name' => 'Client 1',
                'webhook_secret' => 'super-secret-test-key-123',
                'is_active' => true,
            ],
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => 'password',
                'role' => 'admin',
            ],
        );

        $clientUser = User::query()->updateOrCreate(
            ['email' => 'client@example.com'],
            [
                'name' => 'Client User',
                'password' => 'password',
                'role' => 'client',
            ],
        );

        $client->users()->syncWithoutDetaching([$clientUser->id]);
    }
}
