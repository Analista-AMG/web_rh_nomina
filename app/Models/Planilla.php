<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Planilla extends Model
{
    protected $table = 'nomina.dim_planillas';

    protected $fillable = [
        'nombre_empresa', 'ruc', 'razon_social', 'direccion', 
        'fecha_creacion', 'nombre_planilla', 'regimen', 'tipo_pago', 'activo'
    ];
}
