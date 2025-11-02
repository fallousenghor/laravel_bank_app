<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $connection = 'railway';
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::connection($this->connection)->create('archive_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->decimal('montant', 10, 2);
            $table->enum('type', ['debit', 'credit', 'depot', 'retrait', 'virement'])->default('debit');
            $table->timestamp('date')->nullable();
            $table->uuid('compte_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('archive_transactions');
    }
};
