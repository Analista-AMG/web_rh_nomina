<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dbo.equipo_dia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supervisor_id')->nullable(); // NULL = sin supervisor (bubble-up a coord/JO)
            $table->unsignedBigInteger('empleado_id');
            $table->unsignedBigInteger('campana_id');
            $table->date('fecha');
            $table->string('origen', 20);                           // base|prestamo
            $table->unsignedBigInteger('prestamo_id')->nullable();
            $table->timestamps();

            $table->foreign('supervisor_id')->references('id')->on('users')->onDelete('no action');
            $table->foreign('empleado_id')->references('id')->on('nomina.dim_personas')->onDelete('no action');
            $table->foreign('campana_id')->references('id')->on('dbo.campanas')->onDelete('no action');
            $table->foreign('prestamo_id')->references('id')->on('dbo.equipo_prestamos')->onDelete('no action');

            // Regla dura: un empleado en un solo roster por día
            $table->unique(['empleado_id', 'fecha']);

            $table->index(['supervisor_id', 'fecha']); // incluye NULLs para query de sin-supervisor
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dbo.equipo_dia');
    }
};
