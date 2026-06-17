<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dbo.asistencia_cierres', function (Blueprint $table) {
            $table->id();
            $table->string('periodo', 7);    // YYYY-MM
            $table->tinyInteger('quincena'); // 1 o 2
            $table->boolean('bloqueado')->default(true);
            $table->unsignedBigInteger('bloqueado_por');
            $table->timestamp('bloqueado_en')->useCurrent();

            $table->unique(['periodo', 'quincena']);
            $table->foreign('bloqueado_por')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dbo.asistencia_cierres');
    }
};
