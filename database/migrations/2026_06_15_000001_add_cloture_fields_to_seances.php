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

    }

    public function down(): void
    {
        Schema::table('seances', function (Blueprint $table) {
            $table->dropColumn(['nb_presents_valide', 'cloture_validee_at', 'cloture_validee_par']);
        });
    }
};
