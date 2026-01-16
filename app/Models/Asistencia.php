<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $table = 'bronze.fact_asistencia';
    protected $primaryKey = 'id_asistencia';
    public $timestamps = false;

    protected $fillable = [
        'id_contrato',
        'fecha',
        'id_cod_asistencia',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'id_contrato', 'id_contrato');
    }

    public function itemAsistencia()
    {
        return $this->belongsTo(ItemAsistencia::class, 'id_cod_asistencia', 'id_cod_asistencia');
    }
}
