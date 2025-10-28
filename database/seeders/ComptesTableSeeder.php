<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Compte;

class ComptesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create comptes for non-admin users only (case-insensitive check)
        User::whereRaw("LOWER(role) != 'admin'")->get()->each(function ($user) {
            Compte::factory(2)->create([
                'client_id' => $user->id,
            ]);
        });
    }
}
