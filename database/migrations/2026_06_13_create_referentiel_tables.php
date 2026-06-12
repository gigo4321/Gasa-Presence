<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // OPTIONS PÉDAGOGIQUES (ex: SIL, Réseau) — globales, dans une filière
        // NE PAS confondre avec la table "options" qui = groupes d'étudiants par centre
        Schema::create('filiere_options', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);           // ex: SIL, Réseau, Informatique de Gestion
            $table->string('code', 20);            // ex: SIL, RSX
            $table->foreignId('filiere_id')->constrained('filieres')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['code', 'filiere_id']);
        });

        // NIVEAUX — définis librement par l'utilisateur (L1, L2, M1, M2...)
        Schema::create('niveaux', function (Blueprint $table) {
            $table->id();
            $table->string('libelle', 30);         // ex: Licence 1, Master 2, DUT 1
            $table->string('code', 10);            // ex: L1, M2, D1
            $table->unsignedTinyInteger('ordre');  // pour trier (L1=1, L2=2, L3=3, M1=4, M2=5)
            $table->foreignId('filiere_option_id')->constrained('filiere_options')->onDelete('cascade');
            $table->timestamps();
        });

        // Ajouter niveau_id à matieres (nullable pour compatibilité avec l'existant)
        Schema::table('matieres', function (Blueprint $table) {
            $table->foreignId('niveau_id')->nullable()->after('filiere_id')
                  ->constrained('niveaux')->onDelete('set null');
        });

        // Supprimer la colonne niveau (int) si elle existe — remplacée par niveau_id
        if (Schema::hasColumn('matieres', 'niveau')) {
            Schema::table('matieres', function (Blueprint $table) {
                $table->dropColumn('niveau');
            });
        }
    }

    public function down(): void
    {
        Schema::table('matieres', function (Blueprint $table) {
            $table->dropForeign(['niveau_id']);
            $table->dropColumn('niveau_id');
        });
        Schema::dropIfExists('niveaux');
        Schema::dropIfExists('filiere_options');
    }
};
