<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Planilla extends Model
{
    protected $table = 'bronze.dim_planilla';
    protected $primaryKey = 'id_planilla';
    public $timestamps = false;

    protected $fillable = [
        'nombre_empresa', 'ruc', 'razon_social', 'direccion', 
        'fecha_creacion', 'nombre_planilla', 'regimen', 'tipo_pago', 'activo'
    ];
}
