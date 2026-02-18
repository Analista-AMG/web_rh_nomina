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
        Schema::table('bronze.fact_adicionales', function (Blueprint $table) {
            $table->unique(['id_contrato', 'periodo', 'tipo_adicional'], 'uq_adicionales_contrato_periodo_tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bronze.fact_adicionales', function (Blueprint $table) {
            $table->dropUnique('uq_adicionales_contrato_periodo_tipo');
        });
    }
};
