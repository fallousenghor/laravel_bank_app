<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        // oauth_access_tokens.user_id
        if (Schema::hasTable('oauth_access_tokens') && Schema::hasColumn('oauth_access_tokens', 'user_id')) {
            $count = DB::table('oauth_access_tokens')->count();

            if ($count === 0) {
                // safe to drop and recreate as uuid
                Schema::table('oauth_access_tokens', function (Blueprint $table) {
                    $table->dropIndex(['user_id']);
                });

                Schema::table('oauth_access_tokens', function (Blueprint $table) {
                    $table->dropColumn('user_id');
                });

                Schema::table('oauth_access_tokens', function (Blueprint $table) {
                    $table->uuid('user_id')->nullable()->index();
                });
            } else {
                // attempt to alter column type using USING cast; will fail if values can't be cast
                DB::statement('ALTER TABLE oauth_access_tokens ALTER COLUMN user_id TYPE uuid USING (user_id::uuid)');
            }
        }

        // oauth_auth_codes.user_id
        if (Schema::hasTable('oauth_auth_codes') && Schema::hasColumn('oauth_auth_codes', 'user_id')) {
            $count = DB::table('oauth_auth_codes')->count();

            if ($count === 0) {
                Schema::table('oauth_auth_codes', function (Blueprint $table) {
                    $table->dropIndex(['user_id']);
                });

                Schema::table('oauth_auth_codes', function (Blueprint $table) {
                    $table->dropColumn('user_id');
                });

                Schema::table('oauth_auth_codes', function (Blueprint $table) {
                    $table->uuid('user_id')->index();
                });
            } else {
                DB::statement('ALTER TABLE oauth_auth_codes ALTER COLUMN user_id TYPE uuid USING (user_id::uuid)');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert oauth_access_tokens.user_id to bigint if the column exists and is uuid
        if (Schema::hasTable('oauth_access_tokens') && Schema::hasColumn('oauth_access_tokens', 'user_id')) {
            // Attempt to convert back; this will fail if UUIDs cannot be cast to bigint.
            try {
                DB::statement('ALTER TABLE oauth_access_tokens ALTER COLUMN user_id TYPE bigint USING (user_id::bigint)');
            } catch (\Throwable $e) {
                // as a fallback, drop and recreate as bigint nullable
                Schema::table('oauth_access_tokens', function (Blueprint $table) {
                    $table->dropIndex(['user_id']);
                });
                Schema::table('oauth_access_tokens', function (Blueprint $table) {
                    $table->dropColumn('user_id');
                });
                Schema::table('oauth_access_tokens', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->index();
                });
            }
        }

        if (Schema::hasTable('oauth_auth_codes') && Schema::hasColumn('oauth_auth_codes', 'user_id')) {
            try {
                DB::statement('ALTER TABLE oauth_auth_codes ALTER COLUMN user_id TYPE bigint USING (user_id::bigint)');
            } catch (\Throwable $e) {
                Schema::table('oauth_auth_codes', function (Blueprint $table) {
                    $table->dropIndex(['user_id']);
                });
                Schema::table('oauth_auth_codes', function (Blueprint $table) {
                    $table->dropColumn('user_id');
                });
                Schema::table('oauth_auth_codes', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->index();
                });
            }
        }
    }
};
