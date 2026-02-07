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
        Schema::create('bronze.dim_calendario', function (Blueprint $table) {
            $table->date('fecha')->primary();
            $table->integer('nro_dia_mes');
            $table->integer('year');
            $table->integer('nro_mes');
            $table->string('mes', 20);
            $table->string('mes_abrev', 5);
            $table->string('periodo', 10);
            $table->integer('nro_dia_semana');
            $table->string('nombre_dia', 20);
            $table->string('nombre_dia_abrev', 5);
            $table->string('tipo_dia', 20)->nullable();
            $table->string('detalle_dia', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bronze.dim_calendario');
    }
};
