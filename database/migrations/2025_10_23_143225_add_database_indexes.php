<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Index sur la table users
        Schema::table('users', function (Blueprint $table) {
            $table->index('email'); // Pour accélérer les recherches par email
            $table->index('telephone'); // Pour accélérer les recherches par téléphone
        });

        // Index sur la table comptes
        Schema::table('comptes', function (Blueprint $table) {
            $table->index('utilisateur_id'); // Pour accélérer les jointures avec la table users
            $table->index('numero'); // Pour accélérer les recherches par numéro de compte
            $table->index('solde'); // Pour les tris et recherches par solde
            $table->index('type'); // Pour les filtres par type de compte
            $table->index('statut'); // Pour les filtres par statut
            $table->index('date_creation'); // Pour les tris par date de création
        });

        // Index sur la table transactions
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('compte_id'); // Pour accélérer les jointures avec la table comptes
            $table->index('type'); // Pour les filtres par type de transaction
            $table->index('montant'); // Pour les tris et recherches par montant
            $table->index('date'); // Pour les tris et recherches par date
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Suppression des index de la table transactions
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['compte_id']);
            $table->dropIndex(['type']);
            $table->dropIndex(['montant']);
            $table->dropIndex(['date']);
        });

        // Suppression des index de la table comptes
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropIndex(['utilisateur_id']);
            $table->dropIndex(['numero']);
            $table->dropIndex(['solde']);
            $table->dropIndex(['type']);
            $table->dropIndex(['statut']);
            $table->dropIndex(['date_creation']);
        });

        // Suppression des index de la table users
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['telephone']);
        });
    }
};
