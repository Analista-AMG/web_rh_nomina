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
        Schema::create('bronze.dim_pagos', function (Blueprint $table) {
            $table->id('id_pago');
            $table->string('periodo', 10);
            $table->integer('quincena');
            $table->date('inicio');
            $table->date('fin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bronze.dim_pagos');
    }
};
