<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reniec extends Model
{
    protected $table = 'BD_AMG_RRHH.gold.reniec';
    protected $primaryKey = 'id';

    protected $fillable = [
        'nro_documento',
        'ap_pat',
        'ap_mat',
        'nombres',
        'fecha_nac',
        'sexo',
    ];

    protected $casts = [
        'fecha_nac' => 'date',
    ];
}
