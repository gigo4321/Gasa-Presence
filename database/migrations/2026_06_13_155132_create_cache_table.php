<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // La table 'cache' est déjà créée dans la migration 2026_01_01_000006_create_cache_jobs_tables.php
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
