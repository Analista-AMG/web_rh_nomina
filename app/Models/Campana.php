<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Campana extends Model
{
    use LogsActivity;

    protected $table = 'dbo.campanas';

    protected $fillable = [
        'empresa_id',
        'parent_id',
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
            ->useLogName('campanas');
    }

    // Relaciones
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function padre()
    {
        return $this->belongsTo(Campana::class, 'parent_id');
    }

    public function subcampanas()
    {
        return $this->hasMany(Campana::class, 'parent_id');
    }

    public function asignaciones()
    {
        return $this->hasMany(UserAsignacion::class, 'campana_id');
    }

    // Árbol completo de sub-campañas (recursivo)
    public function subcampanasRecursivas()
    {
        return $this->subcampanas()->with('subcampanasRecursivas');
    }

    // Todos los IDs del árbol hacia abajo (para scope de JO)
    public function todosLosIds(): array
    {
        $ids = [$this->id];
        foreach ($this->subcampanas as $sub) {
            $ids = array_merge($ids, $sub->todosLosIds());
        }
        return $ids;
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activo', true)->whereNull('fecha_fin');
    }

    public function scopeRaices($query)
    {
        return $query->whereNull('parent_id');
    }
}
