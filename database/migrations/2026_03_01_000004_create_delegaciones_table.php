<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dbo.delegaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delegante_id');
            $table->unsignedBigInteger('delegado_id');
            $table->unsignedBigInteger('campana_id');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');              // REQUERIDO — siempre temporal
            $table->string('motivo', 500)->nullable();
            $table->unsignedBigInteger('creado_por');
            $table->timestamps();

            $table->foreign('delegante_id')->references('id')->on('users')->onDelete('no action');
            $table->foreign('delegado_id')->references('id')->on('users')->onDelete('no action');
            $table->foreign('campana_id')->references('id')->on('dbo.campanas')->onDelete('no action');
            $table->foreign('creado_por')->references('id')->on('users')->onDelete('no action');

            $table->index(['delegado_id', 'fecha_fin']);
            $table->index(['delegante_id', 'fecha_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dbo.delegaciones');
    }
};
