<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Asistencia extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('asistencia');
    }

    protected $table = 'nomina.fact_asistencias';

    protected $fillable = [
        'contrato_id',
        'persona_id',
        'fecha',
        'item_asistencia_id',
        'tardanza',
        'min_tardanza',
    ];

    protected $casts = [
        'fecha'    => 'date',
        'tardanza' => 'boolean',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    public function itemAsistencia()
    {
        return $this->belongsTo(ItemAsistencia::class, 'item_asistencia_id');
    }
}
