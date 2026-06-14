<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('matieres', function (Blueprint $table) {
            // Supprimer l'ancienne contrainte globale (celle qui cause l'erreur 1062)
            $table->dropUnique('matieres_code_unique');

            // Créer une nouvelle contrainte limitée au niveau et à la filière
            $table->unique(['code', 'filiere_id', 'niveau_id'], 'matieres_scoped_unique');
        });
    }

    public function down(): void
    {
        Schema::table('matieres', function (Blueprint $table) {
            $table->dropUnique('matieres_scoped_unique');
            $table->unique('code');
        });
    }
};
