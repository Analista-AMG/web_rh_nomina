<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adicional extends Model
{
    protected $table = 'bronze.fact_adicionales';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'periodo',
        'id_contrato',
        'tipo_adicional',
        'monto',
        'encargado',
        'motivo',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'id_contrato', 'id_contrato');
    }
}
