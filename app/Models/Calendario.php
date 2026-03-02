<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calendario extends Model
{
    protected $table = 'nomina.dim_calendarios';
    protected $primaryKey = 'fecha';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'fecha',
        'nro_dia_mes',
        'year',
        'nro_mes',
        'mes',
        'mes_abrev',
        'periodo',
        'nro_dia_semana',
        'nombre_dia',
        'nombre_dia_abrev',
        'tipo_dia',
        'detalle_dia',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];
}
