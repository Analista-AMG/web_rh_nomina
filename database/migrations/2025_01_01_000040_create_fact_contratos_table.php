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
        Schema::create('nomina.fact_contratos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('persona_id');

            // Fechas del contrato (ancla — sin datos económicos, ver fact_contratos_movimientos)
            $table->date('inicio_contrato');
            $table->date('fin_contrato')->nullable();
            $table->date('fecha_renuncia')->nullable();
            $table->boolean('periodo_prueba')->default(false);

            $table->foreign('persona_id')->references('id')->on('nomina.dim_personas')->onDelete('no action');

            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomina.fact_contratos');
    }
};
