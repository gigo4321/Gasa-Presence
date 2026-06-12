<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Ajouter telephone et badge_uid à la table users
        Schema::table('users', function (Blueprint $table) {
            $table->string('telephone', 20)->nullable()->after('email');
            $table->string('badge_uid', 50)->nullable()->unique()->after('telephone');
        });

        // Table pivot : un professeur peut enseigner plusieurs matières dans son centre
        Schema::create('matiere_professeur', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('matiere_id')->constrained('matieres')->onDelete('cascade');
            $table->primary(['user_id', 'matiere_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matiere_professeur');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telephone', 'badge_uid']);
        });
    }
};
