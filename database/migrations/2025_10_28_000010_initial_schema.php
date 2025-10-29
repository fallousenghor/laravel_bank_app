<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        // Users
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name')->nullable();
                $table->string('prenom', 50)->nullable();
                $table->string('nom', 50)->nullable();
                $table->string('email')->unique();
                $table->string('telephone', 20)->nullable();
                $table->text('adresse')->nullable();
                $table->string('nci', 20)->nullable();
                $table->string('code', 10)->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->enum('role', ['admin', 'user'])->default('user');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Comptes
        if (! Schema::hasTable('comptes')) {
            Schema::create('comptes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('numero', 20)->unique();
                $table->enum('type', ['epargne', 'cheque']);
                $table->decimal('solde', 10, 2);
                $table->enum('statut', ['actif', 'bloque', 'ferme']);
                $table->string('devise', 10)->default('FCFA');
                $table->date('date_creation');
                $table->foreignUuid('client_id')->constrained('users')->onDelete('cascade');
                $table->timestamp('date_debut_blocage')->nullable();
                $table->timestamp('date_fin_blocage')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Transactions
        if (! Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->decimal('montant', 10, 2);
                $table->enum('type', ['debit', 'credit', 'depot', 'retrait', 'virement'])->default('debit');
                $table->timestamp('date')->nullable();
                $table->foreignUuid('compte_id')->constrained('comptes')->onDelete('cascade');
                $table->timestamps();
            });
        }

        // Indexes
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('email');
                $table->index('telephone');
            });
        }

        if (Schema::hasTable('comptes')) {
            Schema::table('comptes', function (Blueprint $table) {
                $table->index('client_id');
                $table->index('numero');
                $table->index('solde');
                $table->index('type');
                $table->index('statut');
                $table->index('date_creation');
            });
        }

        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('compte_id');
                $table->index('type');
                $table->index('montant');
                $table->index('date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('comptes');
        Schema::dropIfExists('users');
    }
};
