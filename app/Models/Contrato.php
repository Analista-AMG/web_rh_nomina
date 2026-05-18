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
        'persona_id',
        'inicio_contrato',
        'fin_contrato',
        'fecha_renuncia',
        'periodo_prueba',
    ];

    protected $casts = [
        'inicio_contrato' => 'date',
        'fin_contrato'    => 'date',
        'fecha_renuncia'  => 'date',
        'periodo_prueba'  => 'boolean',
    ];

    // Relaciones
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function movimientos()
    {
        return $this->hasMany(ContratoMovimiento::class, 'contrato_id');
    }

    public function movimientoActual()
    {
        $hoy = now()->toDateString();

        return $this->hasOne(ContratoMovimiento::class, 'contrato_id')
            ->where('inicio', '<=', $hoy)
            ->where(fn ($q) => $q->whereNull('fin')->orWhere('fin', '>=', $hoy));
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
