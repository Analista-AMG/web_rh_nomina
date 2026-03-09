<?php

namespace App\Models\Scopes;

use App\Services\JerarquiaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class AlcanceUsuarioScope implements Scope
{
    /**
     * The column to apply the scope on.
     */
    protected string $column;

    /**
     * Create a new scope instance.
     */
    public function __construct(string $column = 'persona_id')
    {
        $this->column = $column;
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // No aplicar el scope en consola (seeders, etc.) o si no hay usuario logueado
        if (app()->runningInConsole() || !Auth::hasUser()) {
            return;
        }

        $user = Auth::user();

        // Administrador, Recursos Humanos y Reclutamiento no tienen restricciones de alcance en Personas
        if ($user->hasRole('Administrador') || $user->hasRole('Recursos Humanos') || $user->hasRole('Reclutamiento')) {
            return;
        }

        $service = app(JerarquiaService::class);
        $personaIdsVisibles = $service->personaIdsVisibles($user);

        $service->aplicarFiltroPersonas($builder, $personaIdsVisibles, $this->column);
    }
}
