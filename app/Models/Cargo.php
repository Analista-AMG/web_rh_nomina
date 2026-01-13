<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    protected $table = 'bronze.dim_cargo';
    protected $primaryKey = 'id_cargo';
    public $timestamps = false;

    protected $fillable = ['nombre_cargo'];
}
