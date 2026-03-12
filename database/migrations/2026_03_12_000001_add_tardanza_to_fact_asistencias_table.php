<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nomina.fact_asistencias', function (Blueprint $table) {
            $table->boolean('tardanza')->default(false)->after('item_asistencia_id');
            $table->unsignedSmallInteger('min_tardanza')->nullable()->after('tardanza');
        });
    }

    public function down(): void
    {
        Schema::table('nomina.fact_asistencias', function (Blueprint $table) {
            $table->dropColumn(['tardanza', 'min_tardanza']);
        });
    }
};
