<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('matieres', function (Blueprint $table) {
            // Niveau : 1, 2 ou 3 — après semestre
            $table->unsignedTinyInteger('niveau')->default(1)->after('semestre');
        });
    }
    public function down(): void
    {
        Schema::table('matieres', function (Blueprint $table) {
            $table->dropColumn('niveau');
        });
    }
};
