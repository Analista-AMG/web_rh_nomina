<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UserAsignacion extends Model
{
    use LogsActivity;

    protected $table = 'dbo.user_asignaciones';

    const ROL_COLABORADOR        = 'Colaborador';
    const ROL_SUPERVISOR         = 'Supervisor';
    const ROL_COORDINADOR        = 'Coordinador';
    const ROL_JEFE_OPERACIONES   = 'Jefe Operaciones';

    const ROLES = [
        self::ROL_COLABORADOR,
        self::ROL_SUPERVISOR,
        self::ROL_COORDINADOR,
        self::ROL_JEFE_OPERACIONES,
    ];

    // Jerarquía numérica para comparar niveles (mayor = más autoridad)
    const NIVEL_ROL = [
        self::ROL_COLABORADOR      => 1,
        self::ROL_SUPERVISOR       => 2,
        self::ROL_COORDINADOR      => 3,
        self::ROL_JEFE_OPERACIONES => 4,
    ];

    const ESTADO_PENDIENTE  = 'pendiente';
    const ESTADO_APROBADO   = 'aprobado';
    const ESTADO_RECHAZADO  = 'rechazado';

    protected $fillable = [
        'user_id',
        'campana_id',
        'superior_id',
        'rol',
        'estado',
        'aprobado_por',
        'aprobado_en',
        'motivo_rechazo',
        'activo',
        'fecha_inicio',
        'fecha_fin',
        'creado_por',
    ];

    protected $casts = [
        'activo'      => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'aprobado_en'  => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('user_asignaciones');
    }

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function campana()
    {
        return $this->belongsTo(Campana::class, 'campana_id');
    }

    public function superior()
    {
        return $this->belongsTo(User::class, 'superior_id');
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
    public function scopeVigentes($query)
    {
        return $query->where('estado', self::ESTADO_APROBADO)
                     ->where('activo', true)
                     ->whereNull('fecha_fin');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeDeUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
