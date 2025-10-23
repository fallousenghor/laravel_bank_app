<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('comptes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero', 20)->unique();
            $table->enum('type', ['Épargne', 'Chèque']);
            $table->decimal('solde', 10, 2);
            $table->enum('statut', ['Actif', 'Bloqué']);
            $table->date('date_creation');
            $table->foreignId('utilisateur_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('comptes');
    }
};
