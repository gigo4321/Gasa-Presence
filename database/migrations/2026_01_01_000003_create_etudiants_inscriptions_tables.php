<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Groupes d'étudiants par centre (GL-SIL-L1-Gbégamey)
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->foreignId('filiere_option_id')->constrained('filiere_options')->onDelete('restrict');
            $table->foreignId('niveau_id')->constrained('niveaux')->onDelete('restrict');
            $table->foreignId('centre_id')->constrained('centres')->onDelete('cascade');
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->onDelete('cascade');
            $table->timestamps();
        });

        // Profil permanent de l'étudiant
        Schema::create('etudiants', function (Blueprint $table) {
            $table->id();
            $table->string('matricule', 191)->unique();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('email', 191)->unique();
            $table->string('telephone', 20)->nullable();
            $table->string('badge_uid', 50)->nullable()->unique();
            $table->date('date_naissance')->nullable();
            $table->timestamps();
        });

        // Inscription par année scolaire
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('etudiants')->onDelete('cascade');
            $table->foreignId('option_id')->constrained('options')->onDelete('restrict');
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->onDelete('restrict');
            $table->enum('statut', ['actif','suspendu','diplome','abandonne'])->default('actif');
            $table->date('date_inscription');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['etudiant_id','annee_scolaire_id']); // 1 inscription par an max
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
        Schema::dropIfExists('etudiants');
        Schema::dropIfExists('options');
    }
};
