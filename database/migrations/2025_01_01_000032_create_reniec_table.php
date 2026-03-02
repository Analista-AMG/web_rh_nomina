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
        Schema::create('nomina.reniec', function (Blueprint $table) {
            $table->id();
            $table->string('nro_documento', 20)->unique();
            $table->string('ap_pat', 100)->nullable();
            $table->string('ap_mat', 100)->nullable();
            $table->string('nombres', 150)->nullable();
            $table->date('fecha_nac')->nullable();
            $table->string('sexo', 10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomina.reniec');
    }
};
