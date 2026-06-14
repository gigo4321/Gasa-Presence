<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seance_id')->constrained('seances')->onDelete('cascade');
            $table->foreignId('inscription_id')->constrained('inscriptions')->onDelete('cascade');
            $table->dateTime('heure_entree')->nullable();
            $table->dateTime('heure_sortie_definitive')->nullable();
            $table->enum('statut', [
                'present','absent','presence_insuffisante',
                'sortie_anticipee_toleree','sortie_anticipee_non_toleree'
            ])->default('absent');
            $table->timestamps();
            $table->unique(['seance_id','inscription_id']);
        });

        Schema::create('sorties_temporaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presence_id')->constrained('presences')->onDelete('cascade');
            $table->dateTime('heure_sortie');
            $table->dateTime('heure_rentree')->nullable();
            $table->unsignedInteger('duree_minutes')->nullable();
            $table->boolean('rentree_refusee')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sorties_temporaires');
        Schema::dropIfExists('presences');
    }
};
