<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dbo.control_confirmaciones_personal', function (Blueprint $table) {
            $table->unsignedBigInteger('grupo_gerencia_id')->nullable()->after('comentario');
            $table->foreign('grupo_gerencia_id')
                  ->references('id')->on('dbo.dim_grupo_gerencia')
                  ->onDelete('set null');
            $table->index('grupo_gerencia_id');
        });
    }

    public function down(): void
    {
        Schema::table('dbo.control_confirmaciones_personal', function (Blueprint $table) {
            $table->dropForeign(['grupo_gerencia_id']);
            $table->dropIndex(['grupo_gerencia_id']);
            $table->dropColumn('grupo_gerencia_id');
        });
    }
};
