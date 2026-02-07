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
        Schema::create('bronze.fact_contratos', function (Blueprint $table) {
            $table->id('id_contrato');

            // Foreign keys
            $table->unsignedBigInteger('id_persona');
            $table->unsignedBigInteger('id_cargo')->nullable();
            $table->unsignedBigInteger('id_planilla')->nullable();
            $table->unsignedBigInteger('id_fp')->nullable();
            $table->unsignedBigInteger('id_condicion')->nullable();
            $table->unsignedBigInteger('id_banco')->nullable();
            $table->unsignedBigInteger('id_moneda')->nullable();
            $table->unsignedBigInteger('id_centro_costo')->nullable();
            $table->unsignedBigInteger('id_familia')->nullable();

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
            $table->timestamp('fecha_insercion')->nullable();

            // Constraints
            $table->foreign('id_persona')->references('id_persona')->on('bronze.dim_persona')->onDelete('restrict');
            $table->foreign('id_cargo')->references('id_cargo')->on('bronze.dim_cargo')->onDelete('set null');
            $table->foreign('id_planilla')->references('id_planilla')->on('bronze.dim_planilla')->onDelete('set null');
            $table->foreign('id_fp')->references('id_fondo')->on('bronze.dim_fondo_pensiones')->onDelete('set null');
            $table->foreign('id_condicion')->references('id_condicion')->on('bronze.dim_condicion')->onDelete('set null');
            $table->foreign('id_banco')->references('id_banco')->on('bronze.dim_banco')->onDelete('set null');
            $table->foreign('id_moneda')->references('id_moneda')->on('bronze.dim_moneda')->onDelete('set null');
            $table->foreign('id_centro_costo')->references('id_centro_costo')->on('bronze.dim_centro_costo')->onDelete('set null');
            $table->foreign('id_familia')->references('id_familia')->on('bronze.dim_familia')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bronze.fact_contratos');
    }
};
