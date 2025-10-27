<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create comptes table in archive database
        Schema::connection('archive')->create('comptes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero', 20)->unique();
            $table->enum('type', ['epargne', 'cheque']);
            $table->decimal('solde', 10, 2);
            $table->enum('statut', ['actif', 'bloque', 'ferme']);
            $table->string('devise', 10)->default('FCFA');
            $table->date('date_creation');
            $table->foreignId('utilisateur_id');
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('date_debut_blocage')->nullable();
            $table->timestamp('date_fin_blocage')->nullable();
        });

        // Create transactions table in archive database
        Schema::connection('archive')->create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->decimal('montant', 10, 2);
            $table->enum('type', ['debit', 'credit']);
            $table->timestamp('date');
            $table->uuid('compte_id');
            $table->timestamps();
        });

        // Create migrations table for archive database
        Schema::connection('archive')->create('migrations', function (Blueprint $table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('archive')->dropIfExists('migrations');
        Schema::connection('archive')->dropIfExists('transactions');
        Schema::connection('archive')->dropIfExists('comptes');
    }
};
