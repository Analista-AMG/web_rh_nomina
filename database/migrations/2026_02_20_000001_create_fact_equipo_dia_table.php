<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bronze.fact_equipo_dia', function (Blueprint $table) {
            $table->id('id_asignacion');

            $table->date('fecha');
            $table->unsignedBigInteger('user_id');        // supervisor (users)
            $table->integer('id_contrato');               // colaborador (fact_contratos) — int en BD

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_contrato')->references('id_contrato')->on('bronze.fact_contratos')->onDelete('cascade');

            // Una persona en UN SOLO equipo por día
            $table->unique(['fecha', 'id_contrato']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bronze.fact_equipo_dia');
    }
};
