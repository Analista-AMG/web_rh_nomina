<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlConfirmacion extends Model
{
    protected $table = 'dbo.control_confirmaciones_personal';

    const ESTADO_PENDIENTE              = 'pendiente';
    const ESTADO_CONFIRMADO             = 'confirmado';
    const ESTADO_REQUIERE_ACTUALIZACION = 'requiere_actualizacion';

    const ORIGEN_SUPERVISOR = 'supervisor';
    const ORIGEN_RRHH       = 'rrhh';

    protected $fillable = [
        'periodo',
        'estado',
        'confirmado_por',
        'origen_accion',
        'comentario',
        'confirmado_en',
        'contrato_id',
        'movimiento_id',
        'persona_id',
        // snapshot del movimiento
        'planilla_id',
        'cargo_id',
        'fondo_pensiones_id',
        'condicion_id',
        'banco_id',
        'moneda_id',
        'centro_costo_id',
        'familia_id',
        'haber_basico',
        'movilidad',
        'asignacion_familiar',
        'suspension_renta',
        'numero_cuenta',
        'codigo_interbancario',
        'numero_cuenta_cts',
        'codigo_interbancario_cts',
        'tipo_movimiento',
        'inicio_movimiento',
        'fin_movimiento',
        // desnormalizados
        'empresa',
        'regimen',
        'nombre_cargo',
        'nombre_centro_costo',
        'nombre_familia',
    ];

    protected $casts = [
        'asignacion_familiar' => 'boolean',
        'suspension_renta'    => 'boolean',
        'inicio_movimiento'   => 'date',
        'fin_movimiento'      => 'date',
        'confirmado_en'       => 'datetime',
        'haber_basico'        => 'decimal:2',
        'movilidad'           => 'decimal:2',
    ];

    public function confirmadoPor()
    {
        return $this->belongsTo(User::class, 'confirmado_por');
    }

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function movimiento()
    {
        return $this->belongsTo(ContratoMovimiento::class, 'movimiento_id');
    }

    public function scopePeriodo($query, string $periodo)
    {
        return $query->where('periodo', $periodo);
    }

    public function scopeConfirmados($query)
    {
        return $query->where('estado', self::ESTADO_CONFIRMADO);
    }

    public function scopeRequierenActualizacion($query)
    {
        return $query->where('estado', self::ESTADO_REQUIERE_ACTUALIZACION);
    }

    /**
     * Construye el snapshot de campos del movimiento para guardar en esta tabla.
     */
    public static function buildSnapshot(ContratoMovimiento $mov): array
    {
        return [
            'movimiento_id'           => $mov->id,
            'planilla_id'             => $mov->planilla_id,
            'cargo_id'                => $mov->cargo_id,
            'fondo_pensiones_id'      => $mov->fondo_pensiones_id,
            'condicion_id'            => $mov->condicion_id,
            'banco_id'                => $mov->banco_id,
            'moneda_id'               => $mov->moneda_id,
            'centro_costo_id'         => $mov->centro_costo_id,
            'familia_id'              => $mov->familia_id,
            'haber_basico'            => $mov->haber_basico,
            'movilidad'               => $mov->movilidad,
            'asignacion_familiar'     => $mov->asignacion_familiar,
            'suspension_renta'        => $mov->suspension_renta,
            'numero_cuenta'           => $mov->numero_cuenta,
            'codigo_interbancario'    => $mov->codigo_interbancario,
            'numero_cuenta_cts'       => $mov->numero_cuenta_cts,
            'codigo_interbancario_cts'=> $mov->codigo_interbancario_cts,
            'tipo_movimiento'         => $mov->tipo_movimiento,
            'inicio_movimiento'       => $mov->inicio,
            'fin_movimiento'          => $mov->fin,
            // desnormalizados
            'empresa'                 => $mov->planilla?->nombre_empresa,
            'regimen'                 => $mov->planilla?->regimen,
            'nombre_cargo'            => $mov->cargo?->nombre_cargo,
            'nombre_centro_costo'     => $mov->centroCosto?->nombre_centro_costo,
            'nombre_familia'          => $mov->familia?->nombre_familia,
        ];
    }
}
