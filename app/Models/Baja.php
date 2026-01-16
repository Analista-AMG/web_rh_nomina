<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Baja extends Model
{
    protected $table = 'bronze.fact_bajas';
    protected $primaryKey = 'id_baja';
    public $timestamps = false;

    protected $fillable = [
        'id_contrato',
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
        return $this->belongsTo(Contrato::class, 'id_contrato', 'id_contrato');
    }
}
