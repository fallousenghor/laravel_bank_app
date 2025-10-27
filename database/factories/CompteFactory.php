<?php

namespace Database\Factories;

use App\Models\Compte;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompteFactory extends Factory
{
    protected $model = Compte::class;

    public function definition(): array
    {
        static $number = 1;
        return [
            'numero' => 'CPT' . str_pad($number++, 6, '0', STR_PAD_LEFT),
            'type' => fake()->randomElement(['epargne', 'cheque']),
            'solde' => fake()->randomFloat(2, 1000, 50000),
            'statut' => 'actif',
            'date_creation' => fake()->dateTimeBetween('-2 years', 'now'),
            'devise' => 'FCFA',
        ];
    }
}
