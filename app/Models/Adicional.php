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

    protected $table = 'nomina.fact_adicionales';

    // ── Tipos existentes ──────────────────────────────────────────────────────
    const TIPO_MOVILIDAD          = 'MOVILIDAD';
    const TIPO_REINTEGRO_INAFECTO = 'REINTEGRO_INAFECTO';
    const TIPO_REINTEGRO_AFECTO   = 'REINTEGRO_AFECTO';
    const TIPO_BONIFICACION       = 'BONIFICACION';
    const TIPO_COMISION           = 'COMISION';
    const TIPO_ADELANTO_COMISION  = 'ADELANTO_COMISION';
    const TIPO_DESCUENTOS         = 'DESCUENTOS';

    // ── Tipos ALIEXPRESS ──────────────────────────────────────────────────────
    const TIPO_BONIFICACION_NOCTURNA = 'BONIFICACION_NOCTURNA';
    const TIPO_BONO_REGULAR_MAQUETA  = 'BONO_REGULAR_MAQUETA';
    const TIPO_MAQUETA_INAFECTO      = 'MAQUETA_INAFECTO';

    /** Todos los tipos en orden de display */
    const TIPOS = [
        self::TIPO_COMISION,
        self::TIPO_BONIFICACION,
        self::TIPO_BONIFICACION_NOCTURNA,
        self::TIPO_BONO_REGULAR_MAQUETA,
        self::TIPO_MAQUETA_INAFECTO,
        self::TIPO_REINTEGRO_AFECTO,
        self::TIPO_MOVILIDAD,
        self::TIPO_REINTEGRO_INAFECTO,
        self::TIPO_ADELANTO_COMISION,
        self::TIPO_DESCUENTOS,
    ];

    const LABELS = [
        self::TIPO_COMISION              => 'Comisión',
        self::TIPO_BONIFICACION          => 'Bonificación',
        self::TIPO_BONIFICACION_NOCTURNA => 'Bonif. Nocturna',
        self::TIPO_BONO_REGULAR_MAQUETA  => 'Bono Reg. Maqueta',
        self::TIPO_MAQUETA_INAFECTO      => 'Maqueta Inafecto',
        self::TIPO_REINTEGRO_AFECTO      => 'Reint. Afecto',
        self::TIPO_MOVILIDAD             => 'Movilidad',
        self::TIPO_REINTEGRO_INAFECTO    => 'Reint. Inafecto',
        self::TIPO_ADELANTO_COMISION     => 'Adel. Comisión',
        self::TIPO_DESCUENTOS            => 'Descuentos',
    ];

    /** Tipos que restan del total neto */
    const TIPOS_NEGATIVOS = [
        self::TIPO_ADELANTO_COMISION,
        self::TIPO_DESCUENTOS,
    ];

    /** Tipos solo editables manualmente (no permitidos en import Excel) */
    const TIPOS_SOLO_MANUAL = [
        self::TIPO_REINTEGRO_AFECTO,
        self::TIPO_REINTEGRO_INAFECTO,
    ];

    protected $fillable = [
        'periodo',
        'contrato_id',
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
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
}
