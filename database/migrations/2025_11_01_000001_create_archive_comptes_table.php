<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Force this migration to run against the Railway connection by default.
    public $connection = 'railway';
    public $withinTransaction = false;

    public function up()
    {
        Schema::connection($this->connection)->create('archive_comptes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero', 20)->unique();
            $table->enum('type', ['epargne', 'cheque']);
            $table->decimal('solde', 10, 2);
            $table->enum('statut', ['actif', 'bloque', 'ferme']);
            $table->string('devise', 10)->default('FCFA');
            $table->date('date_creation');
            // Keep client_id column but avoid adding a foreign constraint because
            // the users table may not exist on the Railway archive DB.
            $table->uuid('client_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('archive_comptes');
    }
};
