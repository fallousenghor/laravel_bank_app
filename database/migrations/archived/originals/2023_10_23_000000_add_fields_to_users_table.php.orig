<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'prenom')) {
                $table->string('prenom', 50)->after('id');
            }
            if (!Schema::hasColumn('users', 'nom')) {
                $table->string('nom', 50)->after('prenom');
            }
            if (!Schema::hasColumn('users', 'telephone')) {
                $table->string('telephone', 20)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'adresse')) {
                $table->text('adresse')->nullable()->after('telephone');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['Admin', 'Client'])->default('Client')->after('password');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['prenom', 'nom', 'telephone', 'adresse', 'role']);
        });
    }
};
