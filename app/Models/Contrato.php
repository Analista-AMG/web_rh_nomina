<?php

namespace App\Models;

use App\Models\Scopes\AlcanceUsuarioScope;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Contrato extends Model
{
    use LogsActivity;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new AlcanceUsuarioScope);
    }

    // Expresión SQL para el fin efectivo del contrato (fecha_renuncia tiene precedencia sobre fin_contrato)
    const FIN_EFECTIVO = "COALESCE(fecha_renuncia, fin_contrato)";

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('contratos');
    }

    protected $table = 'nomina.fact_contratos';

    protected $fillable = [
        'persona_id', 'cargo_id', 'planilla_id', 'fondo_pensiones_id', 'condicion_id',
        'asignacion_familiar', 'haber_basico', 'movilidad', 'banco_id',
        'numero_cuenta', 'codigo_interbancario',
        'numero_cuenta_cts', 'codigo_interbancario_cts',
        'moneda_id', 'familia_id',
        'inicio_contrato', 'fin_contrato', 'fecha_renuncia',
        'periodo_prueba', 'suspension_renta', 'centro_costo_id'
    ];

    protected $casts = [
        'inicio_contrato'    => 'date',
        'fin_contrato'       => 'date',
        'fecha_renuncia'     => 'date',
        'asignacion_familiar'=> 'boolean',
        'periodo_prueba'     => 'boolean',
        'suspension_renta'   => 'boolean',
        'haber_basico'       => 'decimal:2',
        'movilidad'          => 'decimal:2',
    ];

    // Relaciones
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
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

    public function banco()
    {
        return $this->belongsTo(Banco::class, 'banco_id');
    }

    public function condicion()
    {
        return $this->belongsTo(Condicion::class, 'condicion_id');
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

    public function movimientos()
    {
        return $this->hasMany(ContratoMovimiento::class, 'contrato_id');
    }

    public function baja()
    {
        return $this->hasOne(Baja::class, 'contrato_id');
    }

    public function adicionales()
    {
        return $this->hasMany(Adicional::class, 'contrato_id');
    }

    // Accessor para calcular el estado en tiempo real
    public function getEstadoAttribute(): string
    {
        $hoy = now()->startOfDay();
        $finEfectivo = $this->fecha_renuncia ?? $this->fin_contrato;

        if ($this->inicio_contrato->gt($hoy)) {
            return 'Pendiente';
        }

        if ($finEfectivo === null || $finEfectivo->gte($hoy)) {
            return 'Activo';
        }

        return 'Finalizado';
    }

    // Scope para obtener contratos activos
    public function scopeActivos($query)
    {
        $hoy = now()->toDateString();

        return $query->where('inicio_contrato', '<=', $hoy)
                     ->whereRaw(self::FIN_EFECTIVO . " >= ?", [$hoy]);
    }
}
