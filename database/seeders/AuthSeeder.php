<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'prenom' => 'Admin',
                'nom' => 'User',
                'telephone' => '+000000000',
                'adresse' => 'Headquarters',
                // The User model casts 'password' => 'hashed', so provide the
                // plain password here and let the model hash it once.
                'password' => 'password',
                'role' => 'admin',
            ]
        );

        // Create Client
        User::updateOrCreate(
            ['email' => 'client@example.com'],
            [
                'prenom' => 'Client',
                'nom' => 'User',
                'telephone' => '+111111111',
                'adresse' => 'Client Address',
                'password' => Hash::make('password'),
                // users table defines role enum(['admin','user']) so use 'user' here
                'role' => 'user',
            ]
        );
    }
}
