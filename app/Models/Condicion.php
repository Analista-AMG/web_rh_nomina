<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Condicion extends Model
{
    protected $table = 'nomina.dim_condiciones';

    protected $fillable = [
        'nombre_condicion',
    ];
}
