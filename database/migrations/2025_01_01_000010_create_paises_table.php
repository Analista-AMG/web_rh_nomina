<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bronze.dim_paises', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('codigo_pais', 8);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bronze.dim_paises');
    }
};
