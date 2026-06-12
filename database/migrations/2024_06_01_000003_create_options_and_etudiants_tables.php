<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->string('nom'); // ex: GL Niveau 1
            $table->integer('niveau'); // 1, 2 ou 3
            $table->foreignId('filiere_id')->constrained()->onDelete('restrict');
            $table->foreignId('centre_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['nom', 'filiere_id', 'centre_id']);
        });

        Schema::create('etudiants', function (Blueprint $table) {
            $table->id();
            $table->string('matricule')->unique();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();
            $table->string('badge_uid')->unique()->nullable();
            $table->enum('statut', ['actif', 'suspendu', 'diplome'])->default('actif');
            $table->foreignId('option_id')->constrained()->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etudiants');
        Schema::dropIfExists('options');
    }
};
