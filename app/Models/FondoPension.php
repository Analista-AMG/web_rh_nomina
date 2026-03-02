<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FondoPension extends Model
{
    protected $table = 'nomina.dim_fondos_pensiones';

    protected $fillable = [
        'fondo_pension', 'nombre_fp', 'tipo', 'fecha_inicio',
        'fecha_fin', 'aporte', 'prima', 'comision', 'activo'
    ];
}
