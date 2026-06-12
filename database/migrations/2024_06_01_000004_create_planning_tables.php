<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salles', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->integer('capacite');
            $table->enum('type', ['banalisee', 'laboratoire']);
            $table->foreignId('centre_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['nom', 'centre_id']);
        });

        Schema::create('seances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matiere_id')->constrained();
            $table->foreignId('salle_id')->constrained();
            $table->foreignId('professeur_id')->constrained('users');
            $table->dateTime('debut');
            $table->dateTime('fin');
            $table->enum('type', ['HP', 'TPE']);
            $table->enum('statut', ['planifiee', 'en_cours', 'terminee', 'annulee'])->default('planifiee');
            $table->boolean('is_inter_centre')->default(false);
            $table->timestamps();
        });

        // Table pivot pour les options rattachées à une séance (multi-options / inter-centres)
        Schema::create('option_seance', function (Blueprint $table) {
            $table->foreignId('seance_id')->constrained()->onDelete('cascade');
            $table->foreignId('option_id')->constrained()->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_seance');
        Schema::dropIfExists('seances');
        Schema::dropIfExists('salles');
    }
};
