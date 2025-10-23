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
        // For each existing user, create 2 comptes
        User::all()->each(function ($user) {
            Compte::factory(2)->create([
                'utilisateur_id' => $user->id,
            ]);
        });
    }
}
