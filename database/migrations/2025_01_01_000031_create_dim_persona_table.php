<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bronze.dim_persona', function (Blueprint $table) {
            $table->id('id_persona');
            $table->string('numero_documento', 20)->unique();
            $table->string('apellido_paterno', 100)->nullable();
            $table->string('apellido_materno', 100)->nullable();
            $table->string('nombres', 150)->nullable();
            $table->string('tipo_documento', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('genero', 20)->nullable();
            $table->string('pais', 100)->nullable();
            $table->string('departamento', 100)->nullable();
            $table->string('provincia', 100)->nullable();
            $table->string('distrito', 100)->nullable();
            $table->string('numero_telefonico', 20)->nullable();
            $table->string('correo_electronico_personal', 150)->nullable();
            $table->string('correo_electronico_corporativo', 150)->nullable();
            $table->string('direccion', 300)->nullable();
            $table->timestamp('fecha_registro')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bronze.dim_persona');
    }
};
