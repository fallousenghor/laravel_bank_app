<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'nci')) {
                $table->string('nci', 20)->nullable();
            }
            if (!Schema::hasColumn('users', 'code')) {
                $table->string('code', 10)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'nci')) {
                $table->dropColumn('nci');
            }
            if (Schema::hasColumn('users', 'code')) {
                $table->dropColumn('code');
            }
        });
    }
};
