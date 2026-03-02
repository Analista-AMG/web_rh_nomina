<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    protected $table = 'nomina.dim_cargos';

    protected $fillable = ['nombre_cargo'];
}
