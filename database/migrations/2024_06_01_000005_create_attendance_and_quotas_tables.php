<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Suivi des volumes HP/TPE par centre (RG-025)
        Schema::create('matiere_centre', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matiere_id')->constrained()->onDelete('cascade');
            $table->foreignId('centre_id')->constrained()->onDelete('cascade');
            $table->integer('hp_restant');
            $table->integer('tpe_dynamique');
            $table->timestamps();
            $table->unique(['matiere_id', 'centre_id']);
        });

        // Enregistrement de pointage (RG-068)
        Schema::create('presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seance_id')->constrained()->onDelete('cascade');
            $table->foreignId('etudiant_id')->constrained()->onDelete('cascade');
            $table->dateTime('heure_entree')->nullable();
            $table->dateTime('heure_sortie_definitive')->nullable();
            $table->enum('statut', ['present', 'absent', 'presence_insuffisante', 'sortie_anticipee_toleree', 'sortie_anticipee_non_toleree'])->default('absent');
            $table->timestamps();
            $table->unique(['seance_id', 'etudiant_id']);
        });

        // Sorties temporaires (RG-061)
        Schema::create('sorties_temporaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presence_id')->constrained('presences')->onDelete('cascade');
            $table->dateTime('heure_sortie');
            $table->dateTime('heure_rentree')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sorties_temporaires');
        Schema::dropIfExists('presences');
        Schema::dropIfExists('matiere_centre');
    }
};
