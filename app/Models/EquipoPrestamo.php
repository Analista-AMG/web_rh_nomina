<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EquipoPrestamo extends Model
{
    use LogsActivity;

    protected $table = 'dbo.equipo_prestamos';

    const TIPO_PRESTAMO  = 'prestamo';

    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_APROBADO  = 'aprobado';
    const ESTADO_RECHAZADO = 'rechazado';
    const ESTADO_ANULADO   = 'anulado';

    protected $fillable = [
        'empleado_id',
        'supervisor_origen_id',
        'supervisor_destino_id',
        'campana_origen_id',
        'campana_destino_id',
        'fecha_inicio',
        'fecha_fin',
        'tipo',
        'estado',
        'aprobado_por',
        'aprobado_en',
        'motivo',
        'motivo_rechazo',
        'creado_por',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'aprobado_en'  => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('equipo_prestamos');
    }

    // Relaciones
    public function empleado()
    {
        return $this->belongsTo(Persona::class, 'empleado_id');
    }

    public function supervisorOrigen()
    {
        return $this->belongsTo(User::class, 'supervisor_origen_id');
    }

    public function supervisorDestino()
    {
        return $this->belongsTo(User::class, 'supervisor_destino_id');
    }

    public function campanaOrigen()
    {
        return $this->belongsTo(Campana::class, 'campana_origen_id');
    }

    public function campanaDestino()
    {
        return $this->belongsTo(Campana::class, 'campana_destino_id');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    // Scopes
    public function scopeAprobados($query)
    {
        return $query->where('estado', self::ESTADO_APROBADO);
    }

    public function scopeVigentes($query)
    {
        $hoy = now()->toDateString();
        return $query->where('estado', self::ESTADO_APROBADO)
                     ->where('fecha_inicio', '<=', $hoy)
                     ->where('fecha_fin', '>=', $hoy);
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    // Verifica solapamiento de fechas para un empleado (validación C3)
    public static function tieneSolapamiento(int $empleadoId, string $fechaInicio, string $fechaFin, ?int $excludeId = null): bool
    {
        return self::where('empleado_id', $empleadoId)
            ->whereNotIn('estado', [self::ESTADO_RECHAZADO, self::ESTADO_ANULADO])
            ->where('fecha_inicio', '<=', $fechaFin)
            ->where('fecha_fin', '>=', $fechaInicio)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
}
