<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dbo.control_confirmaciones_personal', function (Blueprint $table) {
            $table->id();

            // Identidad del periodo y aprobación
            $table->string('periodo', 7);                          // '2026-05'
            $table->string('estado', 30)->default('pendiente');    // pendiente|confirmado|requiere_actualizacion
            $table->unsignedBigInteger('confirmado_por')->nullable();
            $table->string('origen_accion', 20)->nullable();       // 'supervisor'|'rrhh'
            $table->string('comentario', 500)->nullable();
            $table->dateTime('confirmado_en')->nullable();

            // Referencias al ancla SCD2
            $table->unsignedBigInteger('contrato_id');
            $table->unsignedBigInteger('movimiento_id');
            $table->unsignedBigInteger('persona_id');

            // Snapshot completo del movimiento (congelado al momento de confirmar)
            $table->unsignedBigInteger('planilla_id')->nullable();
            $table->unsignedBigInteger('cargo_id')->nullable();
            $table->unsignedBigInteger('fondo_pensiones_id')->nullable();
            $table->unsignedBigInteger('condicion_id')->nullable();
            $table->unsignedBigInteger('banco_id')->nullable();
            $table->unsignedBigInteger('moneda_id')->nullable();
            $table->unsignedBigInteger('centro_costo_id')->nullable();
            $table->unsignedBigInteger('familia_id')->nullable();
            $table->decimal('haber_basico', 12, 2)->nullable();
            $table->decimal('movilidad', 10, 2)->nullable();
            $table->boolean('asignacion_familiar')->nullable();
            $table->boolean('suspension_renta')->nullable();
            $table->string('numero_cuenta', 100)->nullable();
            $table->string('codigo_interbancario', 20)->nullable();
            $table->string('numero_cuenta_cts', 50)->nullable();
            $table->string('codigo_interbancario_cts', 30)->nullable();
            $table->string('tipo_movimiento', 100)->nullable();
            $table->date('inicio_movimiento')->nullable();
            $table->date('fin_movimiento')->nullable();

            // Campos desnormalizados para cálculos sin JOINs
            $table->string('empresa', 200)->nullable();
            $table->string('regimen', 100)->nullable();
            $table->string('nombre_cargo', 150)->nullable();
            $table->string('nombre_centro_costo', 200)->nullable();
            $table->string('nombre_familia', 200)->nullable();

            $table->timestamps();

            $table->unique(['periodo', 'contrato_id']);

            $table->foreign('confirmado_por')->references('id')->on('users')->onDelete('no action');
            $table->foreign('contrato_id')->references('id')->on('nomina.fact_contratos')->onDelete('no action');
            $table->foreign('movimiento_id')->references('id')->on('nomina.fact_contratos_movimientos')->onDelete('no action');
            $table->foreign('persona_id')->references('id')->on('nomina.dim_personas')->onDelete('no action');

            $table->index(['periodo', 'estado']);
            $table->index('confirmado_por');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dbo.control_confirmaciones_personal');
    }
};
