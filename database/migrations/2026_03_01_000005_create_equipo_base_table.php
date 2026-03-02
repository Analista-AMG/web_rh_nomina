<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dbo.equipo_base', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supervisor_id');
            $table->unsignedBigInteger('empleado_id');   // FK → nomina.dim_personas
            $table->unsignedBigInteger('campana_id');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();        // NULL = vigente
            $table->unsignedBigInteger('creado_por');
            $table->timestamps();

            $table->foreign('supervisor_id')->references('id')->on('users')->onDelete('no action');
            $table->foreign('empleado_id')->references('id')->on('nomina.dim_personas')->onDelete('no action');
            $table->foreign('campana_id')->references('id')->on('dbo.campanas')->onDelete('no action');
            $table->foreign('creado_por')->references('id')->on('users')->onDelete('no action');

            $table->index(['supervisor_id', 'fecha_fin']);
            $table->index('empleado_id');
        });

        // Filtered unique index: un empleado solo en un equipo activo por supervisor
        DB::statement("CREATE UNIQUE INDEX [idx_equipo_base_activo] ON [dbo].[equipo_base] ([supervisor_id], [empleado_id]) WHERE [fecha_fin] IS NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('dbo.equipo_base');
    }
};
