<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salles', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 80);
            $table->integer('capacite');
            $table->string('type', 80)->default('');
            $table->foreignId('centre_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['nom','centre_id']);
        });

        Schema::create('seances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matiere_id')->constrained('matieres');
            $table->foreignId('salle_id')->constrained('salles');
            $table->foreignId('professeur_id')->constrained('users');
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires');
            $table->dateTime('debut');
            $table->dateTime('fin');
            $table->enum('type', ['HP','TPE']);
            $table->enum('statut', ['planifiee','en_cours','terminee','annulee'])->default('planifiee');
            $table->boolean('is_inter_centre')->default(false);
            $table->dateTime('heure_scan_professeur')->nullable();
            $table->dateTime('heure_debut_pause')->nullable();
            $table->dateTime('heure_fin_pause')->nullable();
            $table->unsignedInteger('durees_pauses_minutes')->default(0);
            $table->timestamps();
        });

        Schema::create('option_seance', function (Blueprint $table) {
            $table->foreignId('option_id')->constrained('options')->onDelete('cascade');
            $table->foreignId('seance_id')->constrained('seances')->onDelete('cascade');
            $table->primary(['option_id','seance_id']);
        });

        // Quotas HP/TPE par matière, centre et année scolaire
        Schema::create('matiere_centre_annee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matiere_id')->constrained('matieres')->onDelete('cascade');
            $table->foreignId('centre_id')->constrained('centres')->onDelete('cascade');
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->onDelete('cascade');
            $table->integer('hp_restant');
            $table->integer('tpe_dynamique');
            $table->timestamps();
            $table->unique(['matiere_id', 'centre_id', 'annee_scolaire_id'], 'matiere_quota_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matiere_centre_annee');
        Schema::dropIfExists('option_seance');
        Schema::dropIfExists('seances');
        Schema::dropIfExists('salles');
    }
};
