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
        Schema::create('nomina.dim_fondos_pensiones', function (Blueprint $table) {
            $table->id();
            $table->string('fondo_pension', 50)->nullable();
            $table->string('nombre_fp', 150)->nullable();
            $table->string('tipo', 50)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->decimal('aporte', 8, 4)->nullable();
            $table->decimal('prima', 8, 4)->nullable();
            $table->decimal('comision', 8, 4)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomina.dim_fondos_pensiones');
    }
};
