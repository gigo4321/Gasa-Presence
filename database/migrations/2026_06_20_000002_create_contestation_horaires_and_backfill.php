<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contestation_horaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seance_id')->constrained('seances')->onDelete('cascade');
            $table->foreignId('professeur_id')->constrained('users')->onDelete('cascade');
            $table->integer('duree_calculee_minutes');
            $table->integer('duree_contestee_minutes');
            $table->text('motif');
            $table->enum('statut', ['en_attente', 'acceptee', 'refusee'])->default('en_attente');
            $table->timestamps();
            $table->unique(['seance_id', 'professeur_id']);
        });

        // Backfill cloture_validee_par : pour les séances déjà validées sans le champ rempli,
        // on considère que c'est le professeur de la séance qui a validé.
        DB::statement("
            UPDATE seances
            SET cloture_validee_par = professeur_id
            WHERE cloture_validee_at IS NOT NULL
              AND cloture_validee_par IS NULL
              AND professeur_id IS NOT NULL
        ");

        // Backfill nb_presents_valide : pour les séances clôturées (dont les TPE auto-clôturées)
        // sans nb_presents_valide, on compte les présences effectives enregistrées.
        DB::statement("
            UPDATE seances
            SET nb_presents_valide = (
                SELECT COUNT(*) FROM presences
                WHERE presences.seance_id = seances.id
                  AND presences.statut = 'present'
            )
            WHERE nb_presents_valide IS NULL
              AND cloture_validee_at IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('contestation_horaires');
    }
};
