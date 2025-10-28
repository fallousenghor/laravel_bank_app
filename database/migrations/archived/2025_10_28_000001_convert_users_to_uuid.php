<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First update users table
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable();
        });

        // Generate UUIDs for existing users
        DB::statement('UPDATE users SET uuid = gen_random_uuid()');

        // Add UUID column to comptes table
        Schema::table('comptes', function (Blueprint $table) {
            $table->uuid('uuid_client_id')->nullable();
        });

        // Drop existing foreign key
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });

        // Update relationships
        DB::statement('UPDATE comptes c SET uuid_client_id = u.uuid FROM users u WHERE c.client_id = u.id');

        // Drop old columns and rename new ones
        Schema::table('users', function (Blueprint $table) {
            $table->dropPrimary();
            $table->dropColumn('id');
            $table->renameColumn('uuid', 'id');
            $table->primary('id');
        });

        Schema::table('comptes', function (Blueprint $table) {
            $table->dropColumn('client_id');
            $table->renameColumn('uuid_client_id', 'client_id');
            $table->foreign('client_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // This migration cannot be reversed due to data loss
        throw new \Exception('This migration cannot be reversed.');
    }
};
