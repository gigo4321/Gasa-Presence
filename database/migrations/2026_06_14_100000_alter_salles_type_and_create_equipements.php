<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipements', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 150);
            $table->string('type_materiel', 100)->nullable();
            $table->string('numero_serie', 100)->nullable();
            $table->enum('etat', ['bon', 'defectueux', 'hors_service', 'en_maintenance'])->default('bon');
            $table->unsignedInteger('quantite')->default(1);
            $table->foreignId('salle_id')->constrained('salles')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipements');
    }
};
