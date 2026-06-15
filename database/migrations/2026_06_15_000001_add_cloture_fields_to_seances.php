<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('seances', function (Blueprint $table) {
            $table->unsignedInteger('nb_presents_valide')->nullable()->after('durees_pauses_minutes');
            $table->timestamp('cloture_validee_at')->nullable()->after('nb_presents_valide');
            $table->foreignId('cloture_validee_par')->nullable()->after('cloture_validee_at')
                  ->constrained('users')->nullOnDelete();
        });

        Schema::create('contestations_horaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seance_id')->constrained('seances')->onDelete('cascade');
            $table->foreignId('professeur_id')->constrained('users');
            $table->unsignedInteger('duree_calculee_minutes');
            $table->unsignedInteger('duree_contestee_minutes');
            $table->text('motif');
            $table->enum('statut', ['en_attente', 'acceptee', 'rejetee'])->default('en_attente');
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contestations_horaires');
        Schema::table('seances', function (Blueprint $table) {
            $table->dropForeign(['cloture_validee_par']);
            $table->dropColumn(['nb_presents_valide', 'cloture_validee_at', 'cloture_validee_par']);
        });
    }
};
