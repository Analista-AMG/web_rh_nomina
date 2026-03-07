<?php

namespace App\Services;

use App\Models\Campana;
use App\Models\EquipoDia;
use App\Models\Persona;
use App\Models\Scopes\AlcanceUsuarioScope;
use App\Models\User;
use App\Models\UserAsignacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class JerarquiaService
{
    /** Caché por request: evita recalcular en cada query Eloquent del mismo request. */
    private array $cache = [];

    /**
     * Retorna los persona IDs visibles para el usuario.
     * null  = sin filtro (Administrador)
     * []    = ninguna persona visible
     * [ids] = filtrado por jerarquía
     */
    public function personaIdsVisibles(User $user): ?array
    {
        if (array_key_exists($user->id, $this->cache)) {
            return $this->cache[$user->id];
        }

        if ($user->hasRole('Administrador') || $user->hasRole('Recursos Humanos')) {
            return $this->cache[$user->id] = null;
        }

        $asignaciones = UserAsignacion::where('user_id', $user->id)
            ->vigentes()
            ->get();

        if ($asignaciones->isEmpty()) {
            return $this->cache[$user->id] = [];
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

        // Incluir al propio usuario (para que se vea a sí mismo en Personas/Contratos)
        $userIds = $userIds->push($user->id);

        // Resolver: user_id → numero_documento → persona_id (bulk, sin N+1)
        $docs = User::whereIn('id', $userIds->unique()->values())
            ->whereNotNull('numero_documento')
            ->pluck('numero_documento');

        if ($docs->isEmpty()) {
            return $this->cache[$user->id] = [];
        }

        return $this->cache[$user->id] = Persona::withoutGlobalScope(AlcanceUsuarioScope::class)
            ->whereIn('numero_documento', $docs)
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
        if ($user->hasRole('Administrador') || $user->hasRole('Recursos Humanos')) {
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

        // Incluir al propio usuario (para que se vea a sí mismo en Asistencia)
        $userIds = $userIds->push($user->id);

        if ($userIds->isEmpty()) {
            return [];
        }

        $docs = User::whereIn('id', $userIds->unique()->values())
            ->whereNotNull('numero_documento')
            ->pluck('numero_documento');

        if ($docs->isEmpty()) {
            return [];
        }

        return Persona::withoutGlobalScope(AlcanceUsuarioScope::class)
            ->whereIn('numero_documento', $docs)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->toArray();
    }

    /**
     * Campaña vigente de un supervisor (primera asignación aprobada activa).
     */
    public function campanaDelSupervisor(int $userId): ?int
    {
        return (int) UserAsignacion::where('user_id', $userId)
            ->vigentes()
            ->orderBy('id')
            ->value('campana_id') ?: null;
    }

    /**
     * Campaña vigente de un empleado vía numero_documento → User → UserAsignacion.
     */
    public function campanaDelEmpleado(int $personaId): ?int
    {
        $persona = Persona::withoutGlobalScope(AlcanceUsuarioScope::class)
            ->where('id', $personaId)
            ->whereNotNull('numero_documento')
            ->first();

        if (!$persona) return null;

        $empUser = User::where('numero_documento', $persona->numero_documento)->first();
        if (!$empUser) return null;

        return (int) UserAsignacion::where('user_id', $empUser->id)
            ->vigentes()
            ->orderBy('id')
            ->value('campana_id') ?: null;
    }

    /**
     * Resuelve el supervisor origen de un empleado:
     * 1. equipo_dia BASE más reciente
     * 2. asignación vigente del empleado (superior_id)
     * 3. fallback: el usuario que ejecuta la acción
     */
    public function resolverSupervisorOrigen(int $empleadoId, int $fallbackUserId): int
    {
        $supervisorId = (int) DB::table('dbo.equipo_dia')
            ->where('empleado_id', $empleadoId)
            ->where('origen', EquipoDia::ORIGEN_BASE)
            ->orderByDesc('fecha')
            ->value('supervisor_id') ?: null;

        if ($supervisorId) return $supervisorId;

        $persona = Persona::withoutGlobalScope(AlcanceUsuarioScope::class)
            ->where('id', $empleadoId)
            ->whereNotNull('numero_documento')
            ->first();

        if ($persona) {
            $empUser = User::where('numero_documento', $persona->numero_documento)->first();
            if ($empUser) {
                $asig = UserAsignacion::where('user_id', $empUser->id)
                    ->where('estado', UserAsignacion::ESTADO_APROBADO)
                    ->whereNull('fecha_fin')
                    ->first();
                if ($asig?->superior_id) {
                    return (int) $asig->superior_id;
                }
            }
        }

        return $fallbackUserId;
    }

    /**
     * Mapa bulk persona_id → nombre de campaña.
     * Ruta: persona.numero_documento → user → user_asignaciones → campana.
     */
    public function campanaMapPorPersonas(array $personaIds): array
    {
        if (empty($personaIds)) return [];

        $docs = Persona::withoutGlobalScope(AlcanceUsuarioScope::class)
            ->whereIn('id', $personaIds)
            ->whereNotNull('numero_documento')
            ->pluck('numero_documento', 'id');

        if ($docs->isEmpty()) return [];

        $usersByDoc = User::whereIn('numero_documento', $docs->values())
            ->whereNotNull('numero_documento')
            ->pluck('id', 'numero_documento');

        if ($usersByDoc->isEmpty()) return [];

        $asigPorUser = UserAsignacion::whereIn('user_id', $usersByDoc->values())
            ->where('estado', UserAsignacion::ESTADO_APROBADO)
            ->whereNull('fecha_fin')
            ->pluck('campana_id', 'user_id');

        $campanas = Campana::whereIn('id', $asigPorUser->values()->unique()->filter()->toArray())
            ->pluck('nombre', 'id');

        $map = [];
        foreach ($docs as $personaId => $numDoc) {
            $userId    = $usersByDoc[$numDoc] ?? null;
            $campanaId = $userId ? ($asigPorUser[(int) $userId] ?? null) : null;
            $map[(int) $personaId] = $campanaId ? ($campanas[$campanaId] ?? '-') : '-';
        }

        return $map;
    }

    /**
     * Resuelve el persona_id propio del usuario logueado vía numero_documento.
     * Retorna null si el usuario no tiene numero_documento o no hay Persona asociada.
     */
    public function propioPersonaId(User $user): ?int
    {
        if (!$user->numero_documento) return null;

        $id = Persona::withoutGlobalScope(AlcanceUsuarioScope::class)
            ->where('numero_documento', $user->numero_documento)
            ->value('id');

        return $id ? (int) $id : null;
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
