<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('dbo.equipo_base');
    }

    public function down(): void
    {
        // Restaurar la tabla si se revierte (solo estructura base)
        Schema::create('dbo.equipo_base', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supervisor_id');
            $table->unsignedBigInteger('empleado_id');
            $table->unsignedBigInteger('campana_id');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->unsignedBigInteger('creado_por');
            $table->timestamps();
        });
    }
};
