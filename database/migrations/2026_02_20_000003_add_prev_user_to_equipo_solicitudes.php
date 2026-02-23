<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bronze.fact_equipo_solicitudes', function (Blueprint $table) {
            // Supervisor que tenía la persona cuando se hizo la solicitud
            $table->unsignedBigInteger('prev_user_id')->nullable()->after('solicitado_por');
            $table->foreign('prev_user_id')->references('id')->on('users')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::table('bronze.fact_equipo_solicitudes', function (Blueprint $table) {
            $table->dropForeign(['prev_user_id']);
            $table->dropColumn('prev_user_id');
        });
    }
};
