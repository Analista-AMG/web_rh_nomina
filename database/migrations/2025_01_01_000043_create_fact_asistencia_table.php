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
        Schema::create('bronze.fact_asistencia', function (Blueprint $table) {
            $table->id('id_asistencia');

            // Foreign keys
            $table->unsignedBigInteger('id_contrato');
            $table->unsignedBigInteger('id_cod_asistencia');

            // Campos
            $table->date('fecha');

            // Constraints
            $table->foreign('id_contrato')->references('id_contrato')->on('bronze.fact_contratos')->onDelete('cascade');
            $table->foreign('id_cod_asistencia')->references('id_cod_asistencia')->on('bronze.dim_item_asistencia')->onDelete('restrict');

            // Índice único para evitar duplicados
            $table->unique(['id_contrato', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bronze.fact_asistencia');
    }
};
