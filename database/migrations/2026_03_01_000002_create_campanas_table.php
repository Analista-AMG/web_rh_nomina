<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dbo.campanas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('parent_id')->nullable(); // sub-campañas
            $table->string('nombre', 200);
            $table->boolean('activo')->default(true);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('dbo.empresas')->onDelete('no action');
        });

        // FK auto-referencial separada para evitar problemas en SQL Server
        DB::statement('ALTER TABLE [dbo].[campanas] ADD CONSTRAINT [fk_campanas_parent] FOREIGN KEY ([parent_id]) REFERENCES [dbo].[campanas]([id])');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE [dbo].[campanas] DROP CONSTRAINT IF EXISTS [fk_campanas_parent]');
        Schema::dropIfExists('dbo.campanas');
    }
};
