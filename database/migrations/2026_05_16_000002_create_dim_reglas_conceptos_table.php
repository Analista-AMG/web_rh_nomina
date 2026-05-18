<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina.dim_reglas_conceptos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('empresa', 50);
            $table->string('regimen', 20);
            $table->string('familia', 50);
            $table->string('centro_costo', 50);
            $table->string('cargo', 100);
            $table->string('concepto', 50);
            $table->decimal('monto', 10, 2);
            $table->string('formula', 30);
            $table->string('descripcion', 200)->nullable();
            $table->date('inicio');
            $table->date('fin')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina.dim_reglas_conceptos');
    }
};
