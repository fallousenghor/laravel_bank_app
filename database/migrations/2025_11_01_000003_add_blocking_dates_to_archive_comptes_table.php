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
        Schema::connection($this->connection)->table('archive_comptes', function (Blueprint $table) {
            $table->timestamp('date_debut_blocage')->nullable()->after('date_creation');
            $table->timestamp('date_fin_blocage')->nullable()->after('date_debut_blocage');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('archive_comptes', function (Blueprint $table) {
            $table->dropColumn(['date_debut_blocage', 'date_fin_blocage']);
        });
    }
};
