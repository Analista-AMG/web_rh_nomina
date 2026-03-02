<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dbo.asistencia_desbloqueos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supervisor_id');
            $table->unsignedBigInteger('campana_id');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('motivo', 500);
            $table->string('estado', 20)->default('pendiente'); // pendiente|aprobado|rechazado
            $table->unsignedBigInteger('aprobado_por')->nullable();
            $table->dateTime('aprobado_en')->nullable();
            $table->string('motivo_rechazo', 500)->nullable();
            $table->unsignedBigInteger('creado_por');
            $table->timestamps();

            $table->foreign('supervisor_id')->references('id')->on('users')->onDelete('no action');
            $table->foreign('campana_id')->references('id')->on('dbo.campanas')->onDelete('no action');
            $table->foreign('aprobado_por')->references('id')->on('users')->onDelete('no action');
            $table->foreign('creado_por')->references('id')->on('users')->onDelete('no action');

            $table->index(['supervisor_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dbo.asistencia_desbloqueos');
    }
};
