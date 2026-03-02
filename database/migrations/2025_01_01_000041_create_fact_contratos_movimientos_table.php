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

            // Foreign keys
            $table->unsignedBigInteger('contrato_id');
            $table->unsignedBigInteger('cargo_id')->nullable();
            $table->unsignedBigInteger('planilla_id')->nullable();
            $table->unsignedBigInteger('fondo_pensiones_id')->nullable();
            $table->unsignedBigInteger('condicion_id')->nullable();
            $table->unsignedBigInteger('banco_id')->nullable();
            $table->unsignedBigInteger('moneda_id')->nullable();
            $table->unsignedBigInteger('centro_costo_id')->nullable();
            $table->unsignedBigInteger('familia_id')->nullable();

            // Campos de movimiento
            $table->string('tipo_movimiento', 50)->nullable();
            $table->boolean('asignacion_familiar')->default(false);
            $table->decimal('haber_basico', 12, 2)->default(0);
            $table->decimal('movilidad', 10, 2)->default(0);
            $table->string('numero_cuenta', 50)->nullable();
            $table->string('codigo_interbancario', 30)->nullable();
            $table->string('numero_cuenta_cts', 50)->nullable();
            $table->string('codigo_interbancario_cts', 30)->nullable();

            // Fechas y estado
            $table->date('inicio');
            $table->date('fin')->nullable();
            $table->boolean('estado')->default(true);

            // Índice para búsquedas por contrato
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
