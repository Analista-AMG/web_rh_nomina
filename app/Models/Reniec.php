<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reniec extends Model
{
    protected $table = 'gold.reniec';
    protected $primaryKey = 'id';
    public $timestamps = false;

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
