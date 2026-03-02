<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemAsistencia extends Model
{
    protected $table = 'nomina.dim_items_asistencias';

    protected $fillable = [
        'codigo_asistencia',
        'descripcion',
        'tipo',
        'horas_regulares',
        'horas_nocturnas',
        'factor_regular',
        'factor_nocturno',
    ];
}
