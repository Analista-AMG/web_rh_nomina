<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina.fact_fondos_tasas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fondo_id');
            $table->decimal('aporte', 6, 4);
            $table->decimal('prima', 6, 4);
            $table->decimal('comision', 6, 4);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();

            $table->foreign('fondo_id')->references('id')->on('nomina.dim_fondos_pensiones')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina.fact_fondos_tasas');
    }
};
