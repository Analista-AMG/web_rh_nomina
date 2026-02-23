<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bronze.fact_equipo_solicitudes', function (Blueprint $table) {
            $table->id('id_solicitud');

            $table->date('fecha');
            $table->unsignedBigInteger('user_id');          // supervisor que recibirá al colaborador
            $table->integer('id_contrato');                  // colaborador solicitado — int en BD
            $table->string('estado', 20)->default('pendiente'); // pendiente | aprobado | rechazado

            $table->unsignedBigInteger('solicitado_por');    // user que generó la solicitud
            $table->unsignedBigInteger('aprobado_por')->nullable(); // user que aprobó/rechazó
            $table->string('motivo_rechazo')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('no action');
            $table->foreign('id_contrato')->references('id_contrato')->on('bronze.fact_contratos')->onDelete('no action');
            $table->foreign('solicitado_por')->references('id')->on('users')->onDelete('no action');
            $table->foreign('aprobado_por')->references('id')->on('users')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bronze.fact_equipo_solicitudes');
    }
};
