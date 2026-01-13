<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Persona extends Model
{
    protected $table = 'bronze.dim_persona';
    protected $primaryKey = 'id_persona';
    public $timestamps = false;

    protected $fillable = [
        'numero_documento', 'apellido_paterno', 'apellido_materno', 'nombres',
        'tipo_documento', 'fecha_nacimiento', 'genero', 'nacionalidad',
        'correo_electronico_personal', 'correo_electronico_corporativo',
        'direccion', 'fecha_registro'
    ];

    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'id_persona', 'id_persona');
    }

    public function getContratoActivoAttribute()
    {
        return $this->contratos->first(function ($contrato) {
            $hoy = Carbon::now();
            $inicio = Carbon::parse($contrato->inicio_contrato);
            $fin = $contrato->fecha_renuncia 
                ? Carbon::parse($contrato->fecha_renuncia) 
                : ($contrato->fin_contrato ? Carbon::parse($contrato->fin_contrato) : null);

            if (!$fin) return $hoy->gte($inicio);
            return $hoy->between($inicio, $fin);
        });
    }

    public function getEstadoAttribute()
    {
        if ($this->contratos->isEmpty()) return 2;
        return $this->contrato_activo ? 1 : 0;
    }
    
    // NUEVA SINTAXIS DE ACCESSOR (LARAVEL 9/10/11)
    protected function nombreCompleto(): Attribute
    {
        return Attribute::make(
            get: function () {
                $pat = $this->attributes['apellido_paterno'] ?? '';
                $mat = $this->attributes['apellido_materno'] ?? '';
                $nom = $this->attributes['nombres'] ?? '';
                return trim("{$pat} {$mat} {$nom}");
            }
        );
    }
}