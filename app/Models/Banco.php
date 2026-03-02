<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
    protected $table = 'nomina.dim_bancos';

    protected $fillable = ['nombre_banco'];
}
