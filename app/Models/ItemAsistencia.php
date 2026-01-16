<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemAsistencia extends Model
{
    protected $table = 'bronze.dim_item_asistencia';
    protected $primaryKey = 'id_cod_asistencia';
    public $timestamps = false;

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
