<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsistenciaCierre extends Model
{
    protected $table = 'dbo.asistencia_cierres';
    public $timestamps = false;

    protected $fillable = ['periodo', 'quincena', 'bloqueado', 'bloqueado_por', 'bloqueado_en'];

    protected $casts = [
        'bloqueado'    => 'boolean',
        'bloqueado_en' => 'datetime',
    ];

    public function bloqueadoPor()
    {
        return $this->belongsTo(User::class, 'bloqueado_por');
    }
}
