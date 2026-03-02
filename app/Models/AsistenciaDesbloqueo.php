<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AsistenciaDesbloqueo extends Model
{
    use LogsActivity;

    protected $table = 'dbo.asistencia_desbloqueos';

    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_APROBADO  = 'aprobado';
    const ESTADO_RECHAZADO = 'rechazado';

    protected $fillable = [
        'supervisor_id',
        'campana_id',
        'fecha_inicio',
        'fecha_fin',
        'motivo',
        'estado',
        'aprobado_por',
        'aprobado_en',
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
            ->useLogName('asistencia_desbloqueos');
    }

    // Relaciones
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function campana()
    {
        return $this->belongsTo(Campana::class, 'campana_id');
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
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    // Verifica si hay un desbloqueo aprobado vigente para un supervisor/fecha
    public static function estaDesbloqueado(int $supervisorId, string $fecha): bool
    {
        return self::where('supervisor_id', $supervisorId)
            ->where('estado', self::ESTADO_APROBADO)
            ->where('fecha_inicio', '<=', $fecha)
            ->where('fecha_fin', '>=', $fecha)
            ->exists();
    }
}
