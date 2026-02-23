<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EquipoDia extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('equipos');
    }

    protected $table = 'bronze.fact_equipo_dia';
    protected $primaryKey = 'id_asignacion';
    public $timestamps = true;

    protected $fillable = [
        'fecha',
        'user_id',
        'id_contrato',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'id_contrato', 'id_contrato');
    }
}
