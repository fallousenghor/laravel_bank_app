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
        // Only seed if users table is empty to avoid duplicate key errors on repeated deploys
        $userCount = \App\Models\User::count();
        if ($userCount > 0) {
            $this->command->info("Database already seeded (users count: {$userCount}). Skipping seeding.");
            return;
        }

        // Create admin user with specific UUID (idempotent guard above prevents duplicates)
        \App\Models\User::create([
            'id' => '550e8400-e29b-41d4-a716-446655440000', // Specific UUID for easy testing
            'prenom' => 'Admin',
            'nom' => 'System',
            'name' => 'Admin System',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'password' => bcrypt('password123'),
            'nci' => '12345678901',
            'code' => '123456'
        ]);

        // Create 9 regular users
        \App\Models\User::factory(9)->create();

        // Seed comptes and transactions for users
        $this->call([
            ComptesTableSeeder::class,
            TransactionsTableSeeder::class,
        ]);

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
