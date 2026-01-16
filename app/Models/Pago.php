<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'bronze.dim_pagos';
    protected $primaryKey = 'id_pago';
    public $timestamps = false;

    protected $fillable = [
        'periodo',
        'quincena',
        'inicio',
        'fin',
    ];

    protected $casts = [
        'inicio' => 'date',
        'fin' => 'date',
    ];
}
