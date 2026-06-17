<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('emplois_du_temps', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('numero');
            $table->foreignId('centre_id')->constrained()->onDelete('cascade');
            $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->nullOnDelete();
            $table->foreignId('option_id')->nullable()->constrained('options')->nullOnDelete();
            $table->string('orientation_label', 120);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->timestamps();
        });

        // Ajout de la liaison dans la table séances
        Schema::table('seances', function (Blueprint $table) {
            $table->foreignId('emploi_du_temps_id')
                  ->nullable()
                  ->after('est_composition')
                  ->constrained('emplois_du_temps')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emplois_du_temps');
    }
};
