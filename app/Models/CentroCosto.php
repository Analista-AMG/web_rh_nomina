<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentroCosto extends Model
{
    protected $table = 'bronze.dim_centro_costo';
    protected $primaryKey = 'id_centro_costo';
    public $timestamps = false;

    protected $fillable = [
        'nombre_centro_costo',
    ];
}
