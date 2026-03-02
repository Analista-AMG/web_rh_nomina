<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dbo.equipo_prestamos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empleado_id');
            $table->unsignedBigInteger('supervisor_origen_id');
            $table->unsignedBigInteger('supervisor_destino_id');
            $table->unsignedBigInteger('campana_origen_id');
            $table->unsignedBigInteger('campana_destino_id');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');                          // REQUERIDO — siempre acotado
            $table->string('tipo', 20);                         // prestamo|traslado
            $table->string('estado', 20)->default('pendiente'); // pendiente|aprobado|rechazado
            $table->unsignedBigInteger('aprobado_por')->nullable();
            $table->dateTime('aprobado_en')->nullable();
            $table->string('motivo', 500)->nullable();
            $table->string('motivo_rechazo', 500)->nullable();
            $table->unsignedBigInteger('creado_por');
            $table->timestamps();

            $table->foreign('empleado_id')->references('id')->on('nomina.dim_personas')->onDelete('no action');
            $table->foreign('supervisor_origen_id')->references('id')->on('users')->onDelete('no action');
            $table->foreign('supervisor_destino_id')->references('id')->on('users')->onDelete('no action');
            $table->foreign('campana_origen_id')->references('id')->on('dbo.campanas')->onDelete('no action');
            $table->foreign('campana_destino_id')->references('id')->on('dbo.campanas')->onDelete('no action');
            $table->foreign('aprobado_por')->references('id')->on('users')->onDelete('no action');
            $table->foreign('creado_por')->references('id')->on('users')->onDelete('no action');

            $table->index(['empleado_id', 'fecha_inicio', 'fecha_fin']);
            $table->index(['supervisor_origen_id', 'estado']);
            $table->index(['supervisor_destino_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dbo.equipo_prestamos');
    }
};
