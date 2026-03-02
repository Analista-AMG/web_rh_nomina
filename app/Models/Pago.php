<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'nomina.dim_pagos';

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
