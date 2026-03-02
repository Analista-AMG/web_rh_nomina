<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dbo.delegaciones', function (Blueprint $table) {
            // delegante_id nullable = top de jerarquía (reporta al admin)
            $table->unsignedBigInteger('delegante_id')->nullable()->change();
            // fecha_fin nullable = asignación indefinida (sin fecha de fin)
            $table->date('fecha_fin')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('dbo.delegaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('delegante_id')->nullable(false)->change();
            $table->date('fecha_fin')->nullable(false)->change();
        });
    }
};
