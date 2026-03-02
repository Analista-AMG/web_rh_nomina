<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Delegacion extends Model
{
    use LogsActivity;

    protected $table = 'dbo.delegaciones';

    protected $fillable = [
        'delegante_id',
        'delegado_id',
        'campana_id',
        'fecha_inicio',
        'fecha_fin',
        'motivo',
        'creado_por',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('delegaciones');
    }

    public function delegante()
    {
        return $this->belongsTo(User::class, 'delegante_id');
    }

    public function delegado()
    {
        return $this->belongsTo(User::class, 'delegado_id');
    }

    public function campana()
    {
        return $this->belongsTo(Campana::class, 'campana_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function scopeVigentes($query)
    {
        $hoy = now()->toDateString();
        return $query->where('fecha_inicio', '<=', $hoy)
                     ->where('fecha_fin', '>=', $hoy);
    }

    public function scopeDeDelegado($query, int $userId)
    {
        return $query->where('delegado_id', $userId);
    }
}
