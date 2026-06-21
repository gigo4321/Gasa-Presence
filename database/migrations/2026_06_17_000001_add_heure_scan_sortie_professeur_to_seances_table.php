<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('seances', function (Blueprint $table) {
            $table->dateTime('heure_scan_sortie_professeur')
                  ->nullable()
                  ->after('heure_scan_professeur');
        });

        // Backfill historique : pour les séances HP déjà terminées avec un scan d'entrée,
        // on utilise l'heure de fin planifiée comme meilleure approximation de la sortie.
        DB::statement("
            UPDATE seances
            SET heure_scan_sortie_professeur = fin
            WHERE statut = 'terminee'
              AND type = 'HP'
              AND heure_scan_professeur IS NOT NULL
              AND heure_scan_sortie_professeur IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('seances', function (Blueprint $table) {
            $table->dropColumn('heure_scan_sortie_professeur');
        });
    }
};
