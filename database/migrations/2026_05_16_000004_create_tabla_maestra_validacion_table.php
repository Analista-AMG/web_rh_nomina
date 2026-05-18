<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina.tabla_maestra_validacion', function (Blueprint $table) {
            $table->increments('id');
            $table->string('periodo', 7);
            $table->string('tipo_calculo', 20)->nullable();
            $table->string('empresa', 100)->nullable();
            $table->string('regimen', 50)->nullable();
            $table->string('familia', 150)->nullable();
            $table->string('centro_costo', 100)->nullable();

            // Referencias (sin FK — tabla de resultados calculados)
            $table->integer('persona_id')->nullable();
            $table->integer('contrato_id')->nullable();
            $table->integer('movimiento_id')->nullable();

            // Datos del colaborador
            $table->string('numero_documento', 15);
            $table->string('nombre_completo', 150)->nullable();
            $table->string('cargo', 100)->nullable();
            $table->string('status', 20)->nullable();
            $table->string('nacionalidad', 50)->nullable();
            $table->string('fondo_pensiones', 50)->nullable();
            $table->decimal('haber_basico', 10, 2)->default(0);
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_de_cese')->nullable();
            $table->string('banco', 50)->nullable();
            $table->string('moneda', 10)->nullable();
            $table->string('nro_de_cuenta', 30)->nullable();
            $table->string('codigo_interbancario', 30)->nullable();

            // Asistencia
            $table->integer('dias_lsg')->default(0);
            $table->integer('dias_feriado')->default(0);
            $table->integer('dias_con_faltas')->default(0);
            $table->integer('dias_dm')->default(0);
            $table->integer('dias_vacaciones')->default(0);
            $table->decimal('tardanza', 5, 2)->default(0);
            $table->integer('dias_trabajados')->default(0);

            // Haberes
            $table->decimal('haber_basico_real', 10, 2)->default(0);
            $table->decimal('remun_vacacional', 10, 2)->default(0);
            $table->decimal('remun_dm', 10, 2)->default(0);
            $table->decimal('remun_feriado', 10, 2)->default(0);
            $table->decimal('conceptos_afectos_afp_onp', 10, 2)->default(0);
            $table->decimal('conceptos_afectos_essalud', 10, 2)->default(0);
            $table->decimal('mov_supeditada', 10, 2)->default(0);
            $table->decimal('comision', 10, 2)->default(0);
            $table->decimal('comision_capacitacion', 10, 2)->default(0);
            $table->decimal('capacitacion', 10, 2)->default(0);
            $table->decimal('asignacion_familiar', 10, 2)->default(0);
            $table->decimal('movilidad', 10, 2)->default(0);
            $table->decimal('movilidades', 10, 2)->default(0);
            $table->decimal('movilidad_supeditada_asistencia', 10, 2)->default(0);
            $table->decimal('reintegro_inafecto', 10, 2)->default(0);
            $table->decimal('bonificacion', 10, 2)->default(0);
            $table->decimal('reintegro_afecto', 10, 2)->default(0);
            $table->decimal('maqueta_inafecto', 10, 2)->default(0);
            $table->decimal('bono_rendimiento', 10, 2)->default(0);
            $table->decimal('bono_nocturnidad', 10, 2)->default(0);
            $table->decimal('bono_mensual', 10, 2)->default(0);
            $table->decimal('bono_reintegro', 10, 2)->default(0);
            $table->decimal('gratificacion', 10, 2)->default(0);
            $table->decimal('bono_extra_9pct', 10, 2)->default(0);

            // Aportes fondo pensiones
            $table->decimal('aporte_fp', 10, 2)->nullable()->default(0);
            $table->decimal('prima_fp', 10, 2)->nullable()->default(0);
            $table->decimal('comision_fp', 10, 2)->nullable()->default(0);

            // Descuentos
            $table->decimal('essalud', 10, 2)->default(0);
            $table->decimal('retencion_5ta', 10, 2)->default(0);
            $table->decimal('adelanto_comision', 10, 2)->default(0);
            $table->decimal('adelanto_movilidad', 10, 2)->default(0);
            $table->decimal('adelanto_gratificacion', 10, 2)->default(0);
            $table->decimal('adelanto_quincena', 10, 2)->default(0);
            $table->decimal('tardanzas_descuentos', 10, 2)->default(0);
            $table->decimal('otros_descuentos', 10, 2)->default(0);
            $table->decimal('tard', 10, 2)->default(0);
            $table->decimal('descuentos', 10, 2)->default(0);
            $table->decimal('ir_8pct', 10, 2)->default(0);

            // Totales
            $table->decimal('total_haberes', 10, 2)->default(0);
            $table->decimal('total_descuentos', 10, 2)->nullable()->default(0);
            $table->decimal('neto_soles', 10, 2)->nullable()->default(0);
            $table->decimal('neto_usd', 10, 2)->nullable()->default(0);

            // Provisiones
            $table->decimal('basico_compensatorio', 10, 2)->nullable();
            $table->decimal('prov_grat', 10, 2)->nullable();
            $table->decimal('prov_9pct', 10, 2)->nullable();
            $table->decimal('prov_cts', 10, 2)->nullable();
            $table->decimal('prov_vac', 10, 2)->nullable();
            $table->decimal('costo_total_empleado', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina.tabla_maestra_validacion');
    }
};
