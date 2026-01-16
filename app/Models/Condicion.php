<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Condicion extends Model
{
    protected $table = 'bronze.dim_condicion';
    protected $primaryKey = 'id_condicion';
    public $timestamps = false;

    protected $fillable = [
        'nombre_condicion',
    ];
}
