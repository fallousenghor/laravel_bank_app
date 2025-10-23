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
        return [
            'type' => fake()->randomElement(['Épargne', 'Chèque']),
            'solde' => fake()->randomFloat(2, 1000, 50000),
            'statut' => 'Actif',
            'date_creation' => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }
}
