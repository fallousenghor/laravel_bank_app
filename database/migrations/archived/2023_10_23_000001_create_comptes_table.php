<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;
    public function up()
    {
        Schema::create('comptes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero', 20)->unique();
            $table->enum('type', ['epargne', 'cheque']);
            $table->decimal('solde', 10, 2);
            $table->enum('statut', ['actif', 'bloque', 'ferme']);
            $table->string('devise', 10)->default('FCFA');
            $table->date('date_creation');
            $table->foreignUuid('client_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('comptes');
    }
};
