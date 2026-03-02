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
        Schema::create('nomina.dim_items_asistencias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_asistencia', 10);
            $table->string('descripcion', 150)->nullable();
            $table->string('tipo', 50)->nullable();
            $table->decimal('horas_regulares', 5, 2)->default(0);
            $table->decimal('horas_nocturnas', 5, 2)->default(0);
            $table->decimal('factor_regular', 5, 2)->default(1);
            $table->decimal('factor_nocturno', 5, 2)->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomina.dim_items_asistencias');
    }
};
