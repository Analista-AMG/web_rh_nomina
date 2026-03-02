<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Familia extends Model
{
    protected $table = 'nomina.dim_familias';

    protected $fillable = [
        'nombre_familia',
    ];
}
