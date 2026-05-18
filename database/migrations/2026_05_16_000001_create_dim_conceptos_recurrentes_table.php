<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina.dim_conceptos_recurrentes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('numero_documento', 20);
            $table->string('concepto', 30);
            $table->string('tipo_adicional', 100);
            $table->decimal('monto', 12, 2);
            $table->string('periodo_inicio', 7);
            $table->string('periodo_final', 7)->nullable();
            $table->string('colaborador', 200)->nullable();
            $table->dateTime('created_at')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina.dim_conceptos_recurrentes');
    }
};
