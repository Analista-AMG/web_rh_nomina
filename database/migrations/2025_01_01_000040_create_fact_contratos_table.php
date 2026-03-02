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

            // Foreign keys
            $table->unsignedBigInteger('persona_id');
            $table->unsignedBigInteger('cargo_id')->nullable();
            $table->unsignedBigInteger('planilla_id')->nullable();
            $table->unsignedBigInteger('fondo_pensiones_id')->nullable();
            $table->unsignedBigInteger('condicion_id')->nullable();
            $table->unsignedBigInteger('banco_id')->nullable();
            $table->unsignedBigInteger('moneda_id')->nullable();
            $table->unsignedBigInteger('centro_costo_id')->nullable();
            $table->unsignedBigInteger('familia_id')->nullable();

            // Campos de contrato
            $table->boolean('asignacion_familiar')->default(false);
            $table->decimal('haber_basico', 12, 2)->default(0);
            $table->decimal('movilidad', 10, 2)->default(0);
            $table->string('numero_cuenta', 50)->nullable();
            $table->string('codigo_interbancario', 30)->nullable();
            $table->string('numero_cuenta_cts', 50)->nullable();
            $table->string('codigo_interbancario_cts', 30)->nullable();

            // Fechas
            $table->date('inicio_contrato');
            $table->date('fin_contrato')->nullable();
            $table->date('fecha_renuncia')->nullable();
            $table->boolean('periodo_prueba')->default(false);

            // Constraints
            $table->foreign('persona_id')->references('id')->on('nomina.dim_personas')->onDelete('no action');
            $table->foreign('cargo_id')->references('id')->on('nomina.dim_cargos')->onDelete('set null');
            $table->foreign('planilla_id')->references('id')->on('nomina.dim_planillas')->onDelete('set null');
            $table->foreign('fondo_pensiones_id')->references('id')->on('nomina.dim_fondos_pensiones')->onDelete('set null');
            $table->foreign('condicion_id')->references('id')->on('nomina.dim_condiciones')->onDelete('set null');
            $table->foreign('banco_id')->references('id')->on('nomina.dim_bancos')->onDelete('set null');
            $table->foreign('moneda_id')->references('id')->on('nomina.dim_monedas')->onDelete('set null');
            $table->foreign('centro_costo_id')->references('id')->on('nomina.dim_centro_costos')->onDelete('set null');
            $table->foreign('familia_id')->references('id')->on('nomina.dim_familias')->onDelete('set null');

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
