<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        // Transactions
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->decimal('montant', 10, 2);
            $table->enum('type', ['debit', 'credit', 'depot', 'retrait', 'virement'])->default('debit');
            $table->timestamp('date')->nullable();
            $table->foreignUuid('compte_id')->constrained('comptes')->onDelete('cascade');
            $table->timestamps();
        });

        // Indexes
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('compte_id');
            $table->index('type');
            $table->index('montant');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
