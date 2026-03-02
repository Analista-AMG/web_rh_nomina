<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentroCosto extends Model
{
    protected $table = 'nomina.dim_centro_costos';

    protected $fillable = [
        'nombre_centro_costo',
    ];
}
