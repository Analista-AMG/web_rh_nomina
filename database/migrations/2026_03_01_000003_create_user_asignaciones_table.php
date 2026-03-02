<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dbo.user_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('campana_id');
            $table->unsignedBigInteger('superior_id')->nullable(); // NULL = cima de jerarquía
            $table->string('rol', 50);                             // Colaborador|Supervisor|Coordinador|Jefe Operaciones
            $table->string('estado', 20)->default('pendiente');    // pendiente|aprobado|rechazado
            $table->unsignedBigInteger('aprobado_por')->nullable();
            $table->dateTime('aprobado_en')->nullable();
            $table->string('motivo_rechazo', 500)->nullable();
            $table->boolean('activo')->default(true);              // pausar sin cerrar (baja temporal)
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();                 // NULL = vigente
            $table->unsignedBigInteger('creado_por');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('no action');
            $table->foreign('campana_id')->references('id')->on('dbo.campanas')->onDelete('no action');
            $table->foreign('superior_id')->references('id')->on('users')->onDelete('no action');
            $table->foreign('aprobado_por')->references('id')->on('users')->onDelete('no action');
            $table->foreign('creado_por')->references('id')->on('users')->onDelete('no action');

            $table->index(['user_id', 'fecha_fin']);
            $table->index(['superior_id', 'fecha_fin']);
            $table->index(['campana_id', 'fecha_fin']);
        });

        // Filtered unique index: un rol activo por usuario/campaña
        DB::statement("CREATE UNIQUE INDEX [idx_user_asignaciones_activa] ON [dbo].[user_asignaciones] ([user_id], [campana_id]) WHERE [fecha_fin] IS NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('dbo.user_asignaciones');
    }
};
