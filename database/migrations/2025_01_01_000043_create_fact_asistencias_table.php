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
        Schema::create('nomina.fact_asistencias', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->unsignedBigInteger('contrato_id');
            $table->unsignedBigInteger('item_asistencia_id');

            // Campos
            $table->date('fecha');

            // Constraints
            $table->foreign('contrato_id')->references('id')->on('nomina.fact_contratos')->onDelete('cascade');
            $table->foreign('item_asistencia_id')->references('id')->on('nomina.dim_items_asistencias')->onDelete('no action');

            // Índice único para evitar duplicados
            $table->unique(['contrato_id', 'fecha']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomina.fact_asistencias');
    }
};
