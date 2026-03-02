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
        DB::statement("IF NOT EXISTS (SELECT 1 FROM sys.schemas WHERE name='nomina') EXEC('CREATE SCHEMA nomina')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS nomina');
    }
};
