<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, LogsActivity, CausesActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['password', 'remember_token'])
            ->logOnlyDirty()
            ->useLogName('usuarios');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'numero_documento',
        'email',
        'password',
        'activo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    // Relaciones con sistema de jerarquía
    public function asignaciones()
    {
        return $this->hasMany(UserAsignacion::class, 'user_id');
    }

    public function asignacionVigente()
    {
        return $this->hasOne(UserAsignacion::class, 'user_id')
                    ->where('estado', UserAsignacion::ESTADO_APROBADO)
                    ->where('activo', true)
                    ->whereNull('fecha_fin');
    }

    public function subordinados()
    {
        return $this->hasMany(UserAsignacion::class, 'superior_id')
                    ->where('estado', UserAsignacion::ESTADO_APROBADO)
                    ->where('activo', true)
                    ->whereNull('fecha_fin');
    }

    public function delegacionesComoDelgante()
    {
        return $this->hasMany(Delegacion::class, 'delegante_id');
    }

    public function delegacionesComodelegado()
    {
        return $this->hasMany(Delegacion::class, 'delegado_id');
    }

    public function delegacionVigente()
    {
        $hoy = now()->toDateString();
        return $this->hasMany(Delegacion::class, 'delegado_id')
                    ->where('fecha_inicio', '<=', $hoy)
                    ->where('fecha_fin', '>=', $hoy);
    }

}
