<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nomina.fact_asistencias', function (Blueprint $table) {
            $table->integer('es_remoto')->default(0)->after('min_tardanza');
        });
    }

    public function down(): void
    {
        Schema::table('nomina.fact_asistencias', function (Blueprint $table) {
            $table->dropColumn('es_remoto');
        });
    }
};
