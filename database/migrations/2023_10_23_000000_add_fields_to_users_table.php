<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('prenom', 50)->after('id');
            $table->string('nom', 50)->after('prenom');
            $table->string('telephone', 20)->nullable()->after('email');
            $table->text('adresse')->nullable()->after('telephone');
            $table->enum('role', ['Admin', 'Client'])->default('Client')->after('password');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['prenom', 'nom', 'telephone', 'adresse', 'role']);
        });
    }
};
