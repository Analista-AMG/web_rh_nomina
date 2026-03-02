<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ContratoMovimiento extends Model
{
    use LogsActivity;

    // Tipos generados por el sistema que sincronizan datos al contrato padre
    const TIPOS_SISTEMA = [
        'Contrato inicial',
        'Contrato por reingreso',
        'Contrato por baja',
        'Contrato por renovación',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('movimientos');
    }

    protected $table = 'nomina.fact_contratos_movimientos';

    protected $fillable = [
        'contrato_id',
        'cargo_id',
        'planilla_id',
        'fondo_pensiones_id',
        'condicion_id',
        'asignacion_familiar',
        'haber_basico',
        'movilidad',
        'banco_id',
        'numero_cuenta',
        'codigo_interbancario',
        'numero_cuenta_cts',
        'codigo_interbancario_cts',
        'moneda_id',
        'inicio',
        'fin',
        'centro_costo_id',
        'familia_id',
        'estado',
        'tipo_movimiento',
    ];

    protected $casts = [
        'inicio' => 'date',
        'fin' => 'date',
        'asignacion_familiar' => 'boolean',
        'estado' => 'boolean',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function planilla()
    {
        return $this->belongsTo(Planilla::class, 'planilla_id');
    }

    public function fondoPension()
    {
        return $this->belongsTo(FondoPension::class, 'fondo_pensiones_id');
    }

    public function condicion()
    {
        return $this->belongsTo(Condicion::class, 'condicion_id');
    }

    public function banco()
    {
        return $this->belongsTo(Banco::class, 'banco_id');
    }

    public function moneda()
    {
        return $this->belongsTo(Moneda::class, 'moneda_id');
    }

    public function centroCosto()
    {
        return $this->belongsTo(CentroCosto::class, 'centro_costo_id');
    }

    public function familia()
    {
        return $this->belongsTo(Familia::class, 'familia_id');
    }
}
