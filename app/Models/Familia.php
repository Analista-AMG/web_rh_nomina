<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Familia extends Model
{
    protected $table = 'bronze.dim_familia';
    protected $primaryKey = 'id_familia';
    public $timestamps = false;

    protected $fillable = [
        'nombre_familia',
    ];
}
