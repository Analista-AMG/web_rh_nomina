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
        Schema::create('bronze.fact_adicionales', function (Blueprint $table) {
            $table->id();

            // Foreign key
            $table->unsignedBigInteger('id_contrato');

            // Campos
            $table->string('periodo', 10);
            $table->string('tipo_adicional', 100);
            $table->decimal('monto', 12, 2)->default(0);
            $table->string('encargado', 150)->nullable();
            $table->text('motivo')->nullable();

            // Constraints
            $table->foreign('id_contrato')->references('id_contrato')->on('bronze.fact_contratos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bronze.fact_adicionales');
    }
};
