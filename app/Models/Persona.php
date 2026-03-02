<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Persona extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('personas');
    }

    protected $table = 'nomina.dim_personas';

    protected $fillable = [
        'numero_documento', 'apellido_paterno', 'apellido_materno', 'nombres',
        'tipo_documento', 'fecha_nacimiento', 'genero', 'pais', 'departamento',
        'provincia', 'distrito', 'numero_telefonico', 'correo_electronico_personal',
        'correo_electronico_corporativo', 'direccion',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'genero'           => 'integer',
    ];

    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'persona_id');
    }

    protected function contratoActivo(): Attribute
    {
        return Attribute::make(
            get: function () {
                $hoy = now()->startOfDay();
                return $this->contratos->first(function ($contrato) use ($hoy) {
                    $finEfectivo = $contrato->fecha_renuncia ?? $contrato->fin_contrato;
                    return $finEfectivo ? $hoy->between($contrato->inicio_contrato, $finEfectivo) : $hoy->gte($contrato->inicio_contrato);
                });
            }
        );
    }

    protected function estado(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->contratos->isEmpty()) return 2;
                return $this->contrato_activo ? 1 : 0;
            }
        );
    }

    protected function estadoLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => match($this->estado) {
                1 => 'Activo',
                2 => 'Pendiente',
                default => 'Inactivo',
            }
        );
    }

    protected function estadoBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn() => match($this->estado) {
                1 => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                2 => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                default => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            }
        );
    }

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

    // Apellidos + primer nombre únicamente
    protected function nombreCorto(): Attribute
    {
        return Attribute::make(
            get: function () {
                $pat       = $this->attributes['apellido_paterno'] ?? '';
                $mat       = $this->attributes['apellido_materno'] ?? '';
                $primerNom = explode(' ', trim($this->attributes['nombres'] ?? ''))[0] ?? '';
                return trim("{$pat} {$mat} {$primerNom}");
            }
        );
    }
}