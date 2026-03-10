<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nomina.fact_asistencias', function (Blueprint $table) {
            $table->unsignedBigInteger('persona_id')->nullable()->after('contrato_id');
            $table->foreign('persona_id')->references('id')->on('nomina.dim_personas')->onDelete('no action');
        });

        // Rellenar persona_id desde fact_contratos para registros existentes
        DB::statement('
            UPDATE fa
            SET fa.persona_id = fc.persona_id
            FROM nomina.fact_asistencias fa
            INNER JOIN nomina.fact_contratos fc ON fc.id = fa.contrato_id
            WHERE fa.persona_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('nomina.fact_asistencias', function (Blueprint $table) {
            $table->dropForeign(['persona_id']);
            $table->dropColumn('persona_id');
        });
    }
};
