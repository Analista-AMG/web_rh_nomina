<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EquipoSolicitud extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('equipos');
    }

    protected $table = 'nomina.fact_equipo_solicitudes';
    public $timestamps = true;

    protected $fillable = [
        'fecha',
        'user_id',
        'contrato_id',
        'estado',
        'solicitado_por',
        'prev_user_id',
        'aprobado_por',
        'motivo_rechazo',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // Estados posibles
    const PENDIENTE  = 'pendiente';
    const APROBADO   = 'aprobado';
    const RECHAZADO  = 'rechazado';

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    public function solicitadoPor()
    {
        return $this->belongsTo(User::class, 'solicitado_por', 'id');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por', 'id');
    }

    public function prevSupervisor()
    {
        return $this->belongsTo(User::class, 'prev_user_id', 'id');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', self::PENDIENTE);
    }
}
