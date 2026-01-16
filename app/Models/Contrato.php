<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    protected $table = 'bronze.fact_contratos';
    protected $primaryKey = 'id_contrato';
    public $timestamps = false;

    protected $fillable = [
        'id_persona', 'id_cargo', 'id_planilla', 'id_fp', 'id_condicion',
        'asignacion_familiar', 'haber_basico', 'movilidad', 'id_banco',
        'numero_cuenta', 'codigo_interbancario', 'id_moneda',
        'inicio_contrato', 'fin_contrato', 'fecha_renuncia',
        'periodo_prueba', 'id_centro_costo', 'estado', 'fecha_insercion'
    ];

    // Relaciones
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'id_cargo', 'id_cargo');
    }

    public function planilla()
    {
        return $this->belongsTo(Planilla::class, 'id_planilla', 'id_planilla');
    }

    public function fondoPensiones()
    {
        return $this->belongsTo(FondoPensiones::class, 'id_fp', 'id_fondo');
    }

    public function banco()
    {
        return $this->belongsTo(Banco::class, 'id_banco', 'id_banco');
    }

    public function condicion()
    {
        return $this->belongsTo(Condicion::class, 'id_condicion', 'id_condicion');
    }
}