<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Moneda extends Model
{
    protected $table = 'nomina.dim_monedas';

    protected $fillable = [
        'nombre_moneda',
    ];
}
