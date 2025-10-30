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
        // Guard against duplicate table creation when an umbrella initial schema
        // migration already created this table (some environments include both).
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
