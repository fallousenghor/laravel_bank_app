<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        // For Postgres: change user_id from bigint to varchar to support UUID user ids.
        // Use a safe SQL statement that will work when user_id is numeric or already text.
        try {
            DB::statement("ALTER TABLE oauth_access_tokens ALTER COLUMN user_id TYPE varchar USING user_id::varchar;");
        } catch (\Exception $e) {
            // If the ALTER fails (e.g. non-postgres DB), attempt a schema builder fallback.
            Schema::table('oauth_access_tokens', function (Blueprint $table) {
                $table->text('user_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('oauth_access_tokens')) {
            return;
        }

        try {
            DB::statement("ALTER TABLE oauth_access_tokens ALTER COLUMN user_id TYPE bigint USING (user_id::bigint);");
        } catch (\Exception $e) {
            Schema::table('oauth_access_tokens', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });
        }
    }
};
