<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Adicional extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('adicionales');
    }

    protected $table = 'bronze.fact_adicionales';
    protected $primaryKey = 'id';
    public $timestamps = false;
    public $incrementing = false;

    const TIPO_MOVILIDAD = 'MOVILIDAD';
    const TIPO_REINTEGRO_INAFECTO = 'REINTEGRO_INAFECTO';
    const TIPO_REINTEGRO_AFECTO = 'REINTEGRO_AFECTO';
    const TIPO_BONIFICACION = 'BONIFICACION';
    const TIPO_COMISION = 'COMISION';
    const TIPO_ADELANTO_COMISION = 'ADELANTO_COMISION';
    const TIPO_DESCUENTOS = 'DESCUENTOS';
    const TIPO_TARDANZA = 'TARDANZA';

    const TIPOS = [
        self::TIPO_MOVILIDAD,
        self::TIPO_REINTEGRO_INAFECTO,
        self::TIPO_REINTEGRO_AFECTO,
        self::TIPO_BONIFICACION,
        self::TIPO_COMISION,
        self::TIPO_ADELANTO_COMISION,
        self::TIPO_DESCUENTOS,
        self::TIPO_TARDANZA,
    ];

    const LABELS = [
        self::TIPO_MOVILIDAD => 'Movilidad',
        self::TIPO_REINTEGRO_INAFECTO => 'Reint. Inafecto',
        self::TIPO_REINTEGRO_AFECTO => 'Reint. Afecto',
        self::TIPO_BONIFICACION => 'Bonificación',
        self::TIPO_COMISION => 'Comisión',
        self::TIPO_ADELANTO_COMISION => 'Adel. Comisión',
        self::TIPO_DESCUENTOS => 'Descuentos',
        self::TIPO_TARDANZA => 'Tardanza',
    ];

    // Tipos que restan en el total
    const TIPOS_NEGATIVOS = [
        self::TIPO_DESCUENTOS,
        self::TIPO_TARDANZA,
        self::TIPO_ADELANTO_COMISION,
    ];

    protected $fillable = [
        'periodo',
        'id_contrato',
        'tipo_adicional',
        'monto',
        'encargado',
        'motivo',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'id_contrato', 'id_contrato');
    }
}
