<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina.fact_equipo_solicitudes', function (Blueprint $table) {
            $table->id();

            $table->date('fecha');
            $table->unsignedBigInteger('user_id');          // supervisor que recibirá al colaborador
            $table->unsignedBigInteger('contrato_id');       // colaborador solicitado
            $table->string('estado', 20)->default('pendiente'); // pendiente | aprobado | rechazado

            $table->unsignedBigInteger('solicitado_por');    // user que generó la solicitud
            $table->unsignedBigInteger('prev_user_id')->nullable(); // supervisor anterior
            $table->unsignedBigInteger('aprobado_por')->nullable(); // user que aprobó/rechazó
            $table->string('motivo_rechazo')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('no action');
            $table->foreign('contrato_id')->references('id')->on('nomina.fact_contratos')->onDelete('no action');
            $table->foreign('solicitado_por')->references('id')->on('users')->onDelete('no action');
            $table->foreign('prev_user_id')->references('id')->on('users')->onDelete('no action');
            $table->foreign('aprobado_por')->references('id')->on('users')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina.fact_equipo_solicitudes');
    }
};
