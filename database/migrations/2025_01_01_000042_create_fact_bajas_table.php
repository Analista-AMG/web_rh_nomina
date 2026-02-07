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
        Schema::create('bronze.fact_bajas', function (Blueprint $table) {
            $table->id('id_baja');

            // Foreign key
            $table->unsignedBigInteger('id_contrato');

            // Campos de baja
            $table->date('fecha_baja');
            $table->string('motivo_baja', 255)->nullable();
            $table->boolean('aviso_con_15_dias')->default(false);
            $table->boolean('recomienda_reingreso')->default(true);
            $table->text('observacion')->nullable();

            // Constraints
            $table->foreign('id_contrato')->references('id_contrato')->on('bronze.fact_contratos')->onDelete('cascade');
            $table->unique('id_contrato'); // Un contrato solo puede tener una baja
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bronze.fact_bajas');
    }
};
