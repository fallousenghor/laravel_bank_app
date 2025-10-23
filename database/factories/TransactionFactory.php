<?php

namespace Database\Factories;

use App\Models\Compte;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'montant' => $this->faker->randomFloat(2, 10, 1000),
            'type' => $this->faker->randomElement(['debit', 'credit']),
            'date' => $this->faker->dateTimeThisYear(),
            'compte_id' => Compte::factory(),
        ];
    }
}
