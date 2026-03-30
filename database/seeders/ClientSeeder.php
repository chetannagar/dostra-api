<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Client::query()->updateOrCreate(
            ['client_key' => 'client_1'],
            [
                'name' => 'Client 1',
                'webhook_secret' => Str::random(64),
                'is_active' => true,
            ],
        );
    }
}
