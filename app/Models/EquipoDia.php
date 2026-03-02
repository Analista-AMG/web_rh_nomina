<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipoDia extends Model
{
    protected $table = 'dbo.equipo_dia';

    const ORIGEN_BASE     = 'base';
    const ORIGEN_PRESTAMO = 'prestamo';

    protected $fillable = [
        'supervisor_id',
        'empleado_id',
        'campana_id',
        'fecha',
        'origen',
        'prestamo_id',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // Relaciones
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function empleado()
    {
        return $this->belongsTo(Persona::class, 'empleado_id');
    }

    public function campana()
    {
        return $this->belongsTo(Campana::class, 'campana_id');
    }

    public function prestamo()
    {
        return $this->belongsTo(EquipoPrestamo::class, 'prestamo_id');
    }

    // Scopes
    public function scopeHoy($query)
    {
        return $query->where('fecha', now()->toDateString());
    }

    public function scopeFecha($query, string $fecha)
    {
        return $query->where('fecha', $fecha);
    }

    public function scopeDeSupervisor($query, int $supervisorId)
    {
        return $query->where('supervisor_id', $supervisorId);
    }

    public function scopeSinSupervisor($query)
    {
        return $query->whereNull('supervisor_id');
    }
}
