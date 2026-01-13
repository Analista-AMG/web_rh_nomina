<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
    protected $table = 'bronze.dim_banco';
    protected $primaryKey = 'id_banco';
    public $timestamps = false;

    protected $fillable = ['nombre_banco'];
}
