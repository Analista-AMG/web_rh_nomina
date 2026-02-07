<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS bronze');
        DB::statement('CREATE SCHEMA IF NOT EXISTS gold');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS gold CASCADE');
        DB::statement('DROP SCHEMA IF EXISTS bronze CASCADE');
    }
};
