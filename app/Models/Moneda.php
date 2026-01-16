<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Moneda extends Model
{
    protected $table = 'bronze.dim_moneda';
    protected $primaryKey = 'id_moneda';
    public $timestamps = false;

    protected $fillable = [
        'nombre_moneda',
    ];
}
