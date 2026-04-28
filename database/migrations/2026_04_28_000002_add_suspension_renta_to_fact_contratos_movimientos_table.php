<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nomina.fact_contratos_movimientos', function (Blueprint $table) {
            $table->boolean('suspension_renta')->default(false)->after('tipo_movimiento');
        });
    }

    public function down(): void
    {
        Schema::table('nomina.fact_contratos_movimientos', function (Blueprint $table) {
            $table->dropColumn('suspension_renta');
        });
    }
};
