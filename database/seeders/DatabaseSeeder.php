<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        \App\Models\User::factory()->create([
            'prenom' => 'Admin',
            'nom' => 'System',
            'email' => 'admin@example.com',
            'role' => 'Admin',
            'password' => bcrypt('password123')
        ]);

        // Create 9 regular users
        \App\Models\User::factory(9)->create();

        // Seed comptes and transactions for users
        $this->call([
            ComptesTableSeeder::class,
        ]);

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
