<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Empresa extends Model
{
    use LogsActivity;

    protected $table = 'dbo.empresas';

    protected $fillable = [
        'nombre',
        'activo',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'activo'       => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('empresas');
    }

    // Relaciones
    public function campanas()
    {
        return $this->hasMany(Campana::class, 'empresa_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activo', true)->whereNull('fecha_fin');
    }
}
