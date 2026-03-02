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
        Schema::create('nomina.fact_bajas', function (Blueprint $table) {
            $table->id();

            // Foreign key
            $table->unsignedBigInteger('contrato_id');

            // Campos de baja
            $table->date('fecha_baja');
            $table->string('motivo_baja', 255)->nullable();
            $table->boolean('aviso_con_15_dias')->default(false);
            $table->boolean('recomienda_reingreso')->default(true);
            $table->text('observacion')->nullable();

            // Constraints
            $table->foreign('contrato_id')->references('id')->on('nomina.fact_contratos')->onDelete('cascade');
            $table->unique('contrato_id'); // Un contrato solo puede tener una baja
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomina.fact_bajas');
    }
};
