<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina.fact_equipos_dia', function (Blueprint $table) {
            $table->id();

            $table->date('fecha');
            $table->unsignedBigInteger('user_id');        // supervisor (users)
            $table->unsignedBigInteger('contrato_id');    // colaborador (fact_contratos)

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('contrato_id')->references('id')->on('nomina.fact_contratos')->onDelete('cascade');

            // Una persona en UN SOLO equipo por día
            $table->unique(['fecha', 'contrato_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina.fact_equipos_dia');
    }
};
