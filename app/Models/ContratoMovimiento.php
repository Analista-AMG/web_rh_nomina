<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ContratoMovimiento extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('movimientos');
    }

    protected $table = 'bronze.fact_contratos_movimientos';
    protected $primaryKey = 'id_movimiento';
    public $timestamps = false;

    protected $fillable = [
        'id_contrato',
        'id_cargo',
        'id_planilla',
        'id_fp',
        'id_condicion',
        'asignacion_familiar',
        'haber_basico',
        'movilidad',
        'id_banco',
        'numero_cuenta',
        'codigo_interbancario',
        'id_moneda',
        'inicio',
        'fin',
        'id_centro_costo',
        'estado',
        'tipo_movimiento',
        'fecha_insercion',
    ];

    protected $casts = [
        'inicio' => 'date',
        'fin' => 'date',
        'fecha_insercion' => 'datetime',
        'asignacion_familiar' => 'boolean',
        'estado' => 'boolean',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'id_contrato', 'id_contrato');
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'id_cargo', 'id_cargo');
    }

    public function planilla()
    {
        return $this->belongsTo(Planilla::class, 'id_planilla', 'id_planilla');
    }

    public function fondoPensiones()
    {
        return $this->belongsTo(FondoPensiones::class, 'id_fp', 'id_fondo');
    }

    public function condicion()
    {
        return $this->belongsTo(Condicion::class, 'id_condicion', 'id_condicion');
    }

    public function banco()
    {
        return $this->belongsTo(Banco::class, 'id_banco', 'id_banco');
    }

    public function moneda()
    {
        return $this->belongsTo(Moneda::class, 'id_moneda', 'id_moneda');
    }

    public function centroCosto()
    {
        return $this->belongsTo(CentroCosto::class, 'id_centro_costo', 'id_centro_costo');
    }
}
