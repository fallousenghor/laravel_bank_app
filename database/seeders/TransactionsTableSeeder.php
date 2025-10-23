<?php

namespace Database\Seeders;

use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

class TransactionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pour chaque compte, créer entre 5 et 15 transactions
        Compte::all()->each(function ($compte) {
            $soldeInitial = 1000; // Solde initial pour chaque compte
            $soldeActuel = $soldeInitial;

            // Créer entre 5 et 15 transactions
            $nbTransactions = rand(5, 15);

            for ($i = 0; $i < $nbTransactions; $i++) {
                $type = rand(0, 1) ? 'credit' : 'debit';
                $montant = $type === 'credit' ?
                    rand(50, 500) : // Crédits entre 50 et 500
                    min($soldeActuel * 0.8, rand(20, 300)); // Débits limités à 80% du solde disponible

                // Si c'est un débit, vérifier que le solde est suffisant
                if ($type === 'debit' && $montant > $soldeActuel) {
                    continue; // Passer cette transaction si le solde est insuffisant
                }

                // Mettre à jour le solde
                $soldeActuel += ($type === 'credit' ? $montant : -$montant);

                // Créer la transaction
                Transaction::create([
                    'compte_id' => $compte->id,
                    'montant' => $montant,
                    'type' => $type,
                    'date' => fake()->dateTimeBetween('-6 months', 'now')
                ]);
            }

            // Mettre à jour le solde final du compte
            $compte->update(['solde' => $soldeActuel]);
        });
    }
}
