<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Contrato extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('contratos');
    }

    protected $table = 'bronze.fact_contratos';
    protected $primaryKey = 'id_contrato';
    public $timestamps = false;

    protected $fillable = [
        'id_persona', 'id_cargo', 'id_planilla', 'id_fp', 'id_condicion',
        'asignacion_familiar', 'haber_basico', 'movilidad', 'id_banco',
        'numero_cuenta', 'codigo_interbancario',
        'numero_cuenta_cts', 'codigo_interbancario_cts',
        'id_moneda', 'id_familia',
        'inicio_contrato', 'fin_contrato', 'fecha_renuncia',
        'periodo_prueba', 'id_centro_costo', 'fecha_insercion'
    ];

    // Relaciones
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
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

    public function banco()
    {
        return $this->belongsTo(Banco::class, 'id_banco', 'id_banco');
    }

    public function condicion()
    {
        return $this->belongsTo(Condicion::class, 'id_condicion', 'id_condicion');
    }

    public function moneda()
    {
        return $this->belongsTo(Moneda::class, 'id_moneda', 'id_moneda');
    }

    public function centroCosto()
    {
        return $this->belongsTo(CentroCosto::class, 'id_centro_costo', 'id_centro_costo');
    }

    public function familia()
    {
        return $this->belongsTo(Familia::class, 'id_familia', 'id_familia');
    }

    public function movimientos()
    {
        return $this->hasMany(ContratoMovimiento::class, 'id_contrato', 'id_contrato');
    }

    // Accessor para calcular el estado en tiempo real
    public function getEstadoAttribute(): string
    {
        $hoy = now()->toDateString();
        $inicio = $this->inicio_contrato;
        $finEfectivo = $this->fecha_renuncia ?? $this->fin_contrato;

        if ($inicio > $hoy) {
            return 'Pendiente'; // Aún no ha iniciado
        }

        if ($finEfectivo === null || $finEfectivo >= $hoy) {
            return 'Activo'; // Indefinido o con fecha de fin/renuncia en el futuro o hoy
        }

        return 'Finalizado'; // Else, ha terminado
    }

    // Scope para obtener contratos activos
    public function scopeActivos($query)
    {
        $hoy = now()->toDateString();
        
        return $query->where('inicio_contrato', '<=', $hoy) // Ha iniciado o inicia hoy
                     ->where(function ($q) use ($hoy) {
                         // Un contrato es activo si su fin efectivo es NULL o es hoy/futuro
                         $q->where(function ($subQ) { // Condición 1: Indefinido (y no tiene fecha_renuncia)
                               $subQ->whereNull('fin_contrato')
                                    ->whereNull('fecha_renuncia');
                           })
                           ->orWhere(function ($subQ) use ($hoy) { // Condición 2: Tiene una fecha de fin efectiva hoy o en el futuro
                               $subQ->whereRaw("
                                   CASE 
                                       WHEN fecha_renuncia IS NOT NULL THEN fecha_renuncia
                                       ELSE fin_contrato
                                   END >= ?
                               ", [$hoy]);
                           });
                     });
    }
}
