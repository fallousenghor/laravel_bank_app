<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;
use Illuminate\Support\Facades\DB;

class PassportClientsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a simple password grant client and personal access client if they don't exist.
        $now = now();

        $passwordClient = DB::table('oauth_clients')->where('password_client', true)->first();
        if (! $passwordClient) {
            DB::table('oauth_clients')->insert([
                'name' => 'Password Grant Client',
                'secret' => bin2hex(random_bytes(40)),
                'redirect' => url('/'),
                'personal_access_client' => false,
                'password_client' => true,
                'revoked' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->command->info('Password grant client created');
        }

        $personal = DB::table('oauth_clients')->where('personal_access_client', true)->first();
        if (! $personal) {
            DB::table('oauth_clients')->insert([
                'name' => 'Personal Access Client',
                'secret' => bin2hex(random_bytes(40)),
                'redirect' => url('/'),
                'personal_access_client' => true,
                'password_client' => false,
                'revoked' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->command->info('Personal access client created');
        }
    }
}
