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
        Schema::create('bronze.dim_planilla', function (Blueprint $table) {
            $table->id('id_planilla');
            $table->string('nombre_empresa', 200)->nullable();
            $table->string('ruc', 20)->nullable();
            $table->string('razon_social', 250)->nullable();
            $table->string('direccion', 300)->nullable();
            $table->date('fecha_creacion')->nullable();
            $table->string('nombre_planilla', 150);
            $table->string('regimen', 100)->nullable();
            $table->string('tipo_pago', 50)->nullable();
            $table->boolean('activo')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bronze.dim_planilla');
    }
};
