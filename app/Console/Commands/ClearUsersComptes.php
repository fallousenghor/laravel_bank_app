<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearUsersComptes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-users-comptes {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Supprime toutes les lignes des tables 'users' et 'comptes' (et 'transactions'). IRRÉVERSIBLE.";

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->option('force')) {
            if (! $this->confirm("Confirmez-vous la suppression de toutes les lignes des tables users, comptes et transactions ? Cette action est IRRÉVERSIBLE.")) {
                $this->info('Action annulée.');
                return 1;
            }
        }

        $this->info('Vidage des tables en cours...');

        try {
            // Pour être portable sur différentes bases, utiliser Schema::disableForeignKeyConstraints()
            Schema::disableForeignKeyConstraints();

            // Supprimer les données dans l'ordre pour éviter les problèmes de FK
            if (Schema::hasTable('transactions')) {
                DB::table('transactions')->truncate();
            }

            if (Schema::hasTable('comptes')) {
                DB::table('comptes')->truncate();
            }

            if (Schema::hasTable('users')) {
                DB::table('users')->truncate();
            }

            Schema::enableForeignKeyConstraints();

            $this->info('Opération terminée : tables vidées (transactions, comptes, users).');
            return 0;
        } catch (\Throwable $e) {
            // Tentative de réactiver les contraintes si quelque chose a échoué
            try {
                Schema::enableForeignKeyConstraints();
            } catch (\Throwable $_) {
                // ignore
            }

            $this->error('Échec : ' . $e->getMessage());
            return 1;
        }
    }
}
