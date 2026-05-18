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
        Schema::create('nomina.fact_contratos_movimientos', function (Blueprint $table) {
            $table->id();

            // FK al ancla
            $table->unsignedBigInteger('contrato_id');
            $table->unsignedBigInteger('cargo_id')->nullable();
            $table->unsignedBigInteger('planilla_id')->nullable();
            $table->unsignedBigInteger('fondo_pensiones_id')->nullable();
            $table->unsignedBigInteger('condicion_id')->nullable();
            $table->unsignedBigInteger('banco_id')->nullable();
            $table->unsignedBigInteger('moneda_id')->nullable();
            $table->unsignedBigInteger('centro_costo_id')->nullable();
            $table->unsignedBigInteger('familia_id')->nullable();

            // Datos económicos/operacionales (fuente de verdad)
            $table->string('tipo_movimiento', 50)->nullable();
            $table->boolean('asignacion_familiar')->default(false);
            $table->boolean('suspension_renta')->default(false);
            $table->decimal('haber_basico', 12, 2)->default(0);
            $table->decimal('movilidad', 10, 2)->default(0);
            $table->string('numero_cuenta', 50)->nullable();
            $table->string('codigo_interbancario', 30)->nullable();
            $table->string('numero_cuenta_cts', 50)->nullable();
            $table->string('codigo_interbancario_cts', 30)->nullable();

            // Rango de vigencia (SCD Type 2)
            $table->date('inicio');
            $table->date('fin')->nullable();
            $table->boolean('estado')->default(true);

            $table->foreign('contrato_id')->references('id')->on('nomina.fact_contratos')->onDelete('cascade');
            $table->foreign('cargo_id')->references('id')->on('nomina.dim_cargos')->onDelete('set null');
            $table->foreign('planilla_id')->references('id')->on('nomina.dim_planillas')->onDelete('set null');
            $table->foreign('fondo_pensiones_id')->references('id')->on('nomina.dim_fondos_pensiones')->onDelete('set null');
            $table->foreign('condicion_id')->references('id')->on('nomina.dim_condiciones')->onDelete('set null');
            $table->foreign('banco_id')->references('id')->on('nomina.dim_bancos')->onDelete('set null');
            $table->foreign('moneda_id')->references('id')->on('nomina.dim_monedas')->onDelete('set null');
            $table->foreign('centro_costo_id')->references('id')->on('nomina.dim_centro_costos')->onDelete('set null');
            $table->foreign('familia_id')->references('id')->on('nomina.dim_familias')->onDelete('set null');

            $table->index('contrato_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomina.fact_contratos_movimientos');
    }
};
