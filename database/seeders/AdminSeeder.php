<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Use updateOrCreate so rerunning the seeder fixes the admin password if the user exists.
        // The User model casts 'password' => 'hashed', so provide the plain password here and let the model hash it once.
        User::updateOrCreate([
            'email' => 'admin@example.com'
        ], [
            'nom' => 'Admin',
            'prenom' => 'System',
            'password' => 'password123',
            // match enum in users table
            'role' => 'admin'
        ]);
    }
}
