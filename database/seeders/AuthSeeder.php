<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\Client;

class AuthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only create clients if they don't exist
        if (!Client::where('password_client', true)->exists()) {
            // Create a password grant client for API authentication
            Client::create([
                'name' => 'API Client',
                'secret' => 'K7DQHfYsOuO4fM5NyApuWZiN6f7rNHic87fJ06gg',
                'redirect' => 'http://localhost',
                'personal_access_client' => false,
                'password_client' => true,
                'revoked' => false,
            ]);
        }

        if (!Client::where('personal_access_client', true)->exists()) {
            // Create a personal access client
            Client::create([
                'name' => 'Personal Access Client',
                'secret' => 'secret',
                'redirect' => 'http://localhost',
                'personal_access_client' => true,
                'password_client' => false,
                'revoked' => false,
            ]);
        }
    }
}
