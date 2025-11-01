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
        if (! Schema::hasTable('oauth_access_tokens')) {
            return;
        }

        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            // Only add timestamps if they don't already exist (safe for existing DBs)
            if (! Schema::hasColumn('oauth_access_tokens', 'created_at') && ! Schema::hasColumn('oauth_access_tokens', 'updated_at')) {
                $table->timestamps();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('oauth_access_tokens')) {
            return;
        }

        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('oauth_access_tokens', 'created_at') || Schema::hasColumn('oauth_access_tokens', 'updated_at')) {
                $table->dropTimestamps();
            }
        });
    }
};
