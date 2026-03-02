<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Baja extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('bajas');
    }

    protected $table = 'nomina.fact_bajas';

    protected $fillable = [
        'contrato_id',
        'fecha_baja',
        'motivo_baja',
        'aviso_con_15_dias',
        'recomienda_reingreso',
        'observacion',
    ];

    protected $casts = [
        'fecha_baja' => 'date',
        'aviso_con_15_dias' => 'boolean',
        'recomienda_reingreso' => 'boolean',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
}
