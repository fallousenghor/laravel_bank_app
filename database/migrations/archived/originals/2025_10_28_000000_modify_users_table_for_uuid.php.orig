<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * This migration cannot be wrapped in a transaction because it requires multiple DDL operations
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ajouter une nouvelle colonne UUID temporaire
        Schema::create('users_temp', function (Blueprint $table) {
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

        // Copier les données des utilisateurs existants
        DB::statement("
            INSERT INTO users_temp (id, name, prenom, nom, email, telephone, adresse, nci, code, email_verified_at, password, role, remember_token, created_at, updated_at)
            SELECT gen_random_uuid(), name, prenom, nom, email, telephone, adresse, nci, code, email_verified_at, password,
                   CASE WHEN role = 'Admin' THEN 'admin' ELSE 'user' END,
                   remember_token, created_at, updated_at
            FROM users
        ");

                // 2. Créer une nouvelle table comptes_temp sans contraintes
        Schema::create('comptes_temp', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero', 20)->unique();
            $table->enum('type', ['epargne', 'cheque']);
            $table->decimal('solde', 10, 2);
            $table->enum('statut', ['actif', 'bloque', 'ferme']);
            $table->string('devise', 10)->default('FCFA');
            $table->date('date_creation');
            $table->uuid('user_id');
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Copier les données dans la nouvelle table avec les UUIDs correspondants
        DB::statement("
            INSERT INTO comptes_temp (id, numero, type, solde, statut, devise, date_creation, user_id, created_at, updated_at, deleted_at)
            SELECT gen_random_uuid(), c.numero, c.type, c.solde, c.statut, c.devise, c.date_creation, ut.id, c.created_at, c.updated_at, c.deleted_at
            FROM comptes c
            JOIN users u ON c.client_id = u.id
            JOIN users_temp ut ON u.email = ut.email
        ");

        // 4. Créer une nouvelle table transactions_temp sans contraintes
        Schema::create('transactions_temp', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('compte_id');
            $table->decimal('montant', 10, 2);
            $table->enum('type', ['debit', 'credit']);
            $table->timestamp('date');
            $table->timestamps();
        });

        // 5. Copier les données dans la nouvelle table avec les UUIDs correspondants
        DB::statement("
            INSERT INTO transactions_temp (id, user_id, compte_id, montant, type, date, created_at, updated_at)
            SELECT gen_random_uuid(), ut.id, ct.id, t.montant, t.type, t.date, t.created_at, t.updated_at
            FROM transactions t
            JOIN comptes c ON t.compte_id = c.id
            JOIN users u ON c.client_id = u.id
            JOIN users_temp ut ON u.email = ut.email
            JOIN comptes_temp ct ON c.numero = ct.numero
        ");

        // 6. Supprimer les anciennes tables
        Schema::drop('transactions');
        Schema::drop('comptes');
        Schema::drop('users');

        // 7. Renommer les nouvelles tables
        Schema::rename('transactions_temp', 'transactions');
        Schema::rename('comptes_temp', 'comptes');
        Schema::rename('users_temp', 'users');

        // 8. Recréer les clés étrangères
        Schema::table('comptes', function (Blueprint $table) {
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            $table->foreign('compte_id')
                  ->references('id')
                  ->on('comptes')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cette migration ne peut pas être annulée en toute sécurité car elle implique une transformation des données
        throw new \Exception('Cette migration ne peut pas être annulée. Veuillez restaurer une sauvegarde de la base de données si nécessaire.');
    }
};
