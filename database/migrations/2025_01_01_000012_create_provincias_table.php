<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nomina.dim_provincias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departamento_id')->constrained('nomina.dim_departamentos')->cascadeOnDelete();
            $table->string('nombre', 120);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina.dim_provincias');
    }
};
