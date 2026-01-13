<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FondoPensiones extends Model
{
    protected $table = 'bronze.dim_fondo_pensiones';
    protected $primaryKey = 'id_fondo';
    public $timestamps = false;

    protected $fillable = [
        'fondo_pension', 'nombre_fp', 'tipo', 'fecha_inicio', 
        'fecha_fin', 'aporte', 'prima', 'comision', 'activo'
    ];
}
