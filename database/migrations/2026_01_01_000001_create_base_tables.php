<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Années scolaires ──────────────────────────────────────────────
        Schema::create('annees_scolaires', function (Blueprint $table) {
            $table->id();
            $table->string('libelle', 20)->unique(); // 2025-2026
            $table->date('date_debut');
            $table->date('date_fin');
            $table->boolean('active')->default(false);
            $table->timestamps();
        });

        // ── Centres ───────────────────────────────────────────────────────
        Schema::create('centres', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('ville', 100);
            $table->timestamps();
        });

        // ── Filières (globales, permanentes, renommables) ─────────────────
        Schema::create('filieres', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('code', 20)->unique();
            $table->boolean('archive')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // ── Options pédagogiques (SIL, Réseau…) ──────────────────────────
        Schema::create('filiere_options', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('code', 20);
            $table->foreignId('filiere_id')->constrained('filieres')->onDelete('cascade');
            $table->boolean('archive')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['code', 'filiere_id']);
        });

        // ── Niveaux libres (L1, M2…) ─────────────────────────────────────
        Schema::create('niveaux', function (Blueprint $table) {
            $table->id();
            $table->string('libelle', 30); // Licence 1
            $table->string('code', 10);    // L1
            $table->unsignedTinyInteger('ordre')->default(1);
            $table->foreignId('filiere_option_id')->constrained('filiere_options')->onDelete('cascade');
            $table->boolean('archive')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // ── Matières (globales, permanentes, renommables) ─────────────────
        Schema::create('matieres', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 150);
            $table->string('code', 20)->unique();
            $table->unsignedTinyInteger('semestre');
            $table->unsignedInteger('hp_initial');
            $table->unsignedInteger('tpe_initial')->default(0);
            $table->foreignId('filiere_id')->constrained('filieres')->onDelete('restrict');
            $table->foreignId('niveau_id')->nullable()->constrained('niveaux')->onDelete('set null');
            $table->boolean('archive')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matieres');
        Schema::dropIfExists('niveaux');
        Schema::dropIfExists('filiere_options');
        Schema::dropIfExists('filieres');
        Schema::dropIfExists('centres');
        Schema::dropIfExists('annees_scolaires');
    }
};
