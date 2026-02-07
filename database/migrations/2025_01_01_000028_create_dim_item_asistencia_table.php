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
        Schema::create('bronze.dim_item_asistencia', function (Blueprint $table) {
            $table->id('id_cod_asistencia');
            $table->string('codigo_asistencia', 10);
            $table->string('descripcion', 150)->nullable();
            $table->string('tipo', 50)->nullable();
            $table->decimal('horas_regulares', 5, 2)->default(0);
            $table->decimal('horas_nocturnas', 5, 2)->default(0);
            $table->decimal('factor_regular', 5, 2)->default(1);
            $table->decimal('factor_nocturno', 5, 2)->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bronze.dim_item_asistencia');
    }
};
