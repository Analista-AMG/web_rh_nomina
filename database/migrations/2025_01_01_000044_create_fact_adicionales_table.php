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
        Schema::create('nomina.fact_adicionales', function (Blueprint $table) {
            $table->id();

            // Foreign key
            $table->unsignedBigInteger('contrato_id');

            // Campos
            $table->string('periodo', 10);
            $table->string('tipo_adicional', 100);
            $table->decimal('monto', 12, 2)->default(0);
            $table->string('encargado', 150)->nullable();
            $table->text('motivo')->nullable();

            // Constraints
            $table->foreign('contrato_id')->references('id')->on('nomina.fact_contratos')->onDelete('cascade');
            $table->unique(['contrato_id', 'periodo', 'tipo_adicional'], 'uq_adicionales_contrato_periodo_tipo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomina.fact_adicionales');
    }
};
