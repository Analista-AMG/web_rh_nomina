<?php

namespace App\Services;

use App\Models\Campana;
use App\Models\Persona;
use App\Models\User;
use App\Models\UserAsignacion;
use Illuminate\Database\Eloquent\Builder;

class JerarquiaService
{
    /**
     * Retorna los persona IDs visibles para el usuario.
     * null  = sin filtro (Administrador)
     * []    = ninguna persona visible
     * [ids] = filtrado por jerarquía
     */
    public function personaIdsVisibles(User $user): ?array
    {
        if ($user->hasRole('Administrador')) {
            return null;
        }

        $asignaciones = UserAsignacion::where('user_id', $user->id)
            ->vigentes()
            ->get();

        if ($asignaciones->isEmpty()) {
            return [];
        }

        $userIds = collect();

        // Path JO / Coordinador: colaboradores de sus campañas + sub-campañas
        $joCoord = $asignaciones->whereIn('rol', [
            UserAsignacion::ROL_COORDINADOR,
            UserAsignacion::ROL_JEFE_OPERACIONES,
        ]);

        if ($joCoord->isNotEmpty()) {
            $campanaIds = [];
            foreach ($joCoord as $asig) {
                $campana = Campana::with('subcampanas')->find($asig->campana_id);
                if ($campana) {
                    $campanaIds = array_merge($campanaIds, $campana->todosLosIds());
                }
            }
            $campanaIds = array_unique($campanaIds);

            if (!empty($campanaIds)) {
                // Ver todos los roles por debajo del nivel máximo del usuario
                $maxNivel       = $joCoord->max(fn($a) => UserAsignacion::NIVEL_ROL[$a->rol] ?? 0);
                $rolesInferiores = array_keys(array_filter(
                    UserAsignacion::NIVEL_ROL,
                    fn($n) => $n < $maxNivel
                ));

                $ids = UserAsignacion::whereIn('campana_id', $campanaIds)
                    ->whereIn('rol', $rolesInferiores)
                    ->vigentes()
                    ->pluck('user_id');

                $userIds = $userIds->merge($ids);
            }
        }

        // Path Supervisor: subordinados directos (cualquier rol inferior)
        if ($asignaciones->contains('rol', UserAsignacion::ROL_SUPERVISOR)) {
            $ids = UserAsignacion::where('superior_id', $user->id)
                ->vigentes()
                ->pluck('user_id');

            $userIds = $userIds->merge($ids);
        }

        if ($userIds->isEmpty()) {
            return [];
        }

        // Resolver: user_id → numero_documento → persona_id (bulk, sin N+1)
        $docs = User::whereIn('id', $userIds->unique()->values())
            ->whereNotNull('numero_documento')
            ->pluck('numero_documento');

        if ($docs->isEmpty()) {
            return [];
        }

        return Persona::whereIn('numero_documento', $docs)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->toArray();
    }

    /**
     * Igual que personaIdsVisibles() pero considera asignaciones que estuvieron
     * activas en cualquier momento del período [inicio, fin] — incluyendo cerradas.
     * Usado en Asistencia para que ex-supervisores puedan ver/editar su período.
     */
    public function personaIdsVisiblesEnPeriodo(User $user, string $inicio, string $fin): ?array
    {
        if ($user->hasRole('Administrador')) {
            return null;
        }

        // Asignaciones del usuario activas en algún momento del período (sin filtrar activo)
        $asignaciones = UserAsignacion::where('user_id', $user->id)
            ->where('estado', UserAsignacion::ESTADO_APROBADO)
            ->where('fecha_inicio', '<=', $fin)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicio))
            ->get();

        if ($asignaciones->isEmpty()) {
            return [];
        }

        $userIds = collect();

        // Path JO / Coordinador
        $joCoord = $asignaciones->whereIn('rol', [
            UserAsignacion::ROL_COORDINADOR,
            UserAsignacion::ROL_JEFE_OPERACIONES,
        ]);

        if ($joCoord->isNotEmpty()) {
            $campanaIds = [];
            foreach ($joCoord as $asig) {
                $campana = Campana::with('subcampanas')->find($asig->campana_id);
                if ($campana) {
                    $campanaIds = array_merge($campanaIds, $campana->todosLosIds());
                }
            }
            $campanaIds = array_unique($campanaIds);

            if (!empty($campanaIds)) {
                $maxNivel        = $joCoord->max(fn($a) => UserAsignacion::NIVEL_ROL[$a->rol] ?? 0);
                $rolesInferiores = array_keys(array_filter(
                    UserAsignacion::NIVEL_ROL,
                    fn($n) => $n < $maxNivel
                ));

                $ids = UserAsignacion::whereIn('campana_id', $campanaIds)
                    ->whereIn('rol', $rolesInferiores)
                    ->where('estado', UserAsignacion::ESTADO_APROBADO)
                    ->where('fecha_inicio', '<=', $fin)
                    ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicio))
                    ->pluck('user_id');

                $userIds = $userIds->merge($ids);
            }
        }

        // Path Supervisor: subordinados directos durante el período
        if ($asignaciones->contains('rol', UserAsignacion::ROL_SUPERVISOR)) {
            $ids = UserAsignacion::where('superior_id', $user->id)
                ->where('estado', UserAsignacion::ESTADO_APROBADO)
                ->where('fecha_inicio', '<=', $fin)
                ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicio))
                ->pluck('user_id');

            $userIds = $userIds->merge($ids);
        }

        if ($userIds->isEmpty()) {
            return [];
        }

        $docs = User::whereIn('id', $userIds->unique()->values())
            ->whereNotNull('numero_documento')
            ->pluck('numero_documento');

        if ($docs->isEmpty()) {
            return [];
        }

        return Persona::whereIn('numero_documento', $docs)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->toArray();
    }

    /**
     * Aplica el filtro de jerarquía a un Builder.
     */
    public function aplicarFiltroPersonas(Builder $query, ?array $personaIds, string $column = 'persona_id'): Builder
    {
        if ($personaIds === null) {
            return $query;
        }

        if (empty($personaIds)) {
            return $query->whereRaw('1=0');
        }

        return $query->whereIn($column, $personaIds);
    }
}
