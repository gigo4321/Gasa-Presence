<?php
// database/migrations/0000_01_01_000000_create_centres_and_filieres_tables.php
// DOIT être la PREMIÈRE migration car toutes les autres en dépendent

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // CENTRES — table racine du cloisonnement (RG-001)
        Schema::create('centres', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('ville', 100);
            $table->timestamps();
        });

        // FILIÈRES — globales, partagées entre tous les centres (RG-006 et RG-007)
        Schema::create('filieres', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('code', 20)->unique(); // Unique GLOBAL
            $table->timestamps();
        });

        // MATIÈRES — globales, partagées entre tous les centres (RG-007)
        Schema::create('matieres', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 150);
            $table->string('code', 20)->unique(); // Unique GLOBAL
            $table->unsignedTinyInteger('semestre'); // 1 ou 2
            $table->unsignedInteger('hp_initial');
            $table->unsignedInteger('tpe_initial')->default(0);
            $table->foreignId('filiere_id')->constrained('filieres')->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matieres');
        Schema::dropIfExists('filieres');
        Schema::dropIfExists('centres');
    }
};
