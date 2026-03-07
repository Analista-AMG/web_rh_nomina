<?php

namespace App\Console\Commands;

use App\Models\EquipoDia;
use App\Models\EquipoPrestamo;
use App\Models\User;
use App\Models\UserAsignacion;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EquipoAutoCarry extends Command
{
    protected $signature = 'equipo:auto-carry
                            {--fecha=      : Fecha destino Y-m-d}
                            {--supervisor= : Solo para un supervisor (on-demand al login, usa fecha=hoy)}';

    protected $description = 'Copia equipo_dia del día anterior a la fecha destino + aplica préstamos activos';

    public function handle(): int
    {
        $supervisorId = $this->option('supervisor') ? (int) $this->option('supervisor') : null;

        $fecha = match (true) {
            (bool) $this->option('fecha') => Carbon::parse($this->option('fecha'))->toDateString(),
            (bool) $supervisorId          => Carbon::today()->toDateString(),
            default                       => Carbon::tomorrow()->toDateString(),
        };

        $this->info("Auto-carry → {$fecha}" . ($supervisorId ? " [supervisor #{$supervisorId}]" : ' [todos]'));

        DB::transaction(function () use ($fecha, $supervisorId) {
            $this->carryDesdeEquipoDia($fecha, $supervisorId);
            $this->aplicarPrestamos($fecha, $supervisorId);
            $nuevos = $this->rellenarDesdeAsignaciones($fecha, $supervisorId);
            if ($nuevos > 0) {
                $this->line("  ✓ {$nuevos} colaborador(es) nuevos/faltantes añadidos desde asignaciones.");
            }
        });

        return self::SUCCESS;
    }

    // ─── Paso 1: copiar equipo_dia del día anterior con bubble-up ─────────────

    private function carryDesdeEquipoDia(string $fecha, ?int $supervisorId): void
    {
        if ($supervisorId) {
            // Modo supervisor: carry independiente para un solo supervisor
            $this->carryParaSupervisor($fecha, $supervisorId);
        } else {
            // Modo global: cada supervisor usa SU PROPIA fecha anterior
            $supervisores = DB::table('dbo.equipo_dia')
                ->where('fecha', '<', $fecha)
                ->where('origen', EquipoDia::ORIGEN_BASE)
                ->distinct()
                ->pluck('supervisor_id');

            if ($supervisores->isEmpty()) {
                $this->line("  ✗ Sin equipo_dia previo — construyendo desde asignaciones vigentes...");
                $this->construirDesdeAsignaciones($fecha, null);
                return;
            }

            $totalInserts = 0;
            foreach ($supervisores as $supId) {
                $totalInserts += $this->carryParaSupervisor($fecha, $supId);
            }

            $this->line("  ✓ {$totalInserts} registros copiados (carry global por supervisor).");
        }
    }

    private function carryParaSupervisor(string $fecha, ?int $supervisorId): int
    {
        // Fecha más reciente anterior con registros base para ESTE supervisor
        $fechaAnterior = DB::table('dbo.equipo_dia')
            ->where('fecha', '<', $fecha)
            ->where('origen', EquipoDia::ORIGEN_BASE)
            ->when($supervisorId, fn($q) => $q->where('supervisor_id', $supervisorId))
            ->max('fecha');

        if (!$fechaAnterior) {
            $this->line("  ✗ Sin historial previo para supervisor #{$supervisorId} — construyendo desde asignaciones...");
            $this->construirDesdeAsignaciones($fecha, $supervisorId);
            return 0;
        }

        $previos = DB::table('dbo.equipo_dia')
            ->where('fecha', $fechaAnterior)
            ->where('origen', EquipoDia::ORIGEN_BASE)
            ->when($supervisorId, fn($q) => $q->where('supervisor_id', $supervisorId))
            ->get();

        if ($previos->isEmpty()) {
            return 0;
        }

        // Empleados que ya tienen registro hoy (sin filtro supervisor — unique index es empleado+fecha)
        $yaExisten = DB::table('dbo.equipo_dia')
            ->where('fecha', $fecha)
            ->pluck('empleado_id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        $supervisorIds = $previos->pluck('supervisor_id')->filter()->unique()->values();
        $this->precargarCadenaSupervisores($supervisorIds->toArray());

        $now     = now();
        $inserts = [];

        foreach ($previos as $reg) {
            if (in_array((int) $reg->empleado_id, $yaExisten)) continue;

            $supIdResuelto = $this->resolverSupervisorActivo((int) $reg->supervisor_id);

            $inserts[] = [
                'supervisor_id' => $supIdResuelto,
                'empleado_id'   => $reg->empleado_id,
                'campana_id'    => $reg->campana_id,
                'fecha'         => $fecha,
                'origen'        => EquipoDia::ORIGEN_BASE,
                'prestamo_id'   => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];

            $yaExisten[] = (int) $reg->empleado_id; // evitar duplicados dentro del mismo loop
        }

        if (empty($inserts)) {
            return 0;
        }

        foreach (array_chunk($inserts, 100) as $chunk) {
            DB::table('dbo.equipo_dia')->insert($chunk);
        }

        return count($inserts);
    }

    // ─── Bubble-up: resolver supervisor activo ────────────────────────────────

    /** Cache: user_id → [superior_id, es_vigente] para evitar N+1 */
    private array $asignacionCache = [];

    /** Cache de resultado final: user_id → supervisor_id resuelto */
    private array $bubbleCache = [];

    /**
     * Pre-carga en bulk la cadena de asignaciones para los supervisores dados.
     * Evita N+1 al resolver bubble-up de muchos registros.
     */
    private function precargarCadenaSupervisores(array $userIds): void
    {
        $porCargar = array_filter($userIds, fn($id) => !isset($this->asignacionCache[$id]));
        if (empty($porCargar)) return;

        $asignaciones = UserAsignacion::whereIn('user_id', $porCargar)
            ->where('estado', UserAsignacion::ESTADO_APROBADO)
            ->whereNull('fecha_fin')
            ->get(['user_id', 'superior_id', 'activo']);

        // Agrupar por user_id: puede haber varias campañas; tomar la más permisiva
        foreach ($asignaciones as $a) {
            $uid = (int) $a->user_id;
            if (!isset($this->asignacionCache[$uid])) {
                $this->asignacionCache[$uid] = [
                    'superior_id' => $a->superior_id ? (int) $a->superior_id : null,
                    'vigente'     => (bool) $a->activo,
                ];
            } elseif ($a->activo) {
                // Si alguna asignación está activa, el usuario se considera vigente
                $this->asignacionCache[$uid]['vigente'] = true;
            }
        }

        // Usuarios sin asignacion aprobada → no vigentes
        foreach ($porCargar as $uid) {
            if (!isset($this->asignacionCache[$uid])) {
                $this->asignacionCache[$uid] = ['superior_id' => null, 'vigente' => false];
            }
        }

        // Pre-cargar superiores recursivamente (un nivel más)
        $superiores = array_filter(
            array_column($this->asignacionCache, 'superior_id'),
            fn($id) => $id && !isset($this->asignacionCache[$id])
        );
        if (!empty($superiores)) {
            $this->precargarCadenaSupervisores(array_unique(array_values($superiores)));
        }
    }

    /**
     * Devuelve el supervisor_id activo para el usuario dado.
     * Si está inactivo, sube por la cadena (bubble-up).
     * Último recurso: primer Administrador del sistema.
     */
    private function resolverSupervisorActivo(?int $userId, int $depth = 0): ?int
    {
        if (!$userId || $depth > 10) return null;

        if (isset($this->bubbleCache[$userId])) {
            return $this->bubbleCache[$userId];
        }

        $info = $this->asignacionCache[$userId] ?? ['superior_id' => null, 'vigente' => false];

        if ($info['vigente']) {
            $this->bubbleCache[$userId] = $userId;
            return $userId;
        }

        // Subir al superior
        if ($info['superior_id']) {
            $this->precargarCadenaSupervisores([$info['superior_id']]);
            $resuelto = $this->resolverSupervisorActivo($info['superior_id'], $depth + 1);
            $this->bubbleCache[$userId] = $resuelto;
            return $resuelto;
        }

        // Sin superior → último recurso: Admin
        $admin = User::role('Administrador')->first();
        $resuelto = $admin ? (int) $admin->id : null;
        $this->bubbleCache[$userId] = $resuelto;
        return $resuelto;
    }

    // ─── Fallback: construir equipo base desde asignaciones vigentes ──────────

    private function construirDesdeAsignaciones(string $fecha, ?int $supervisorId): void
    {
        $query = UserAsignacion::where('estado', UserAsignacion::ESTADO_APROBADO)
            ->where('activo', true)
            ->whereNull('fecha_fin')
            ->where('rol', UserAsignacion::ROL_COLABORADOR)
            ->when($supervisorId, fn($q) => $q->where('superior_id', $supervisorId));

        $asignaciones = $query->get(['user_id', 'superior_id', 'campana_id']);

        if ($asignaciones->isEmpty()) {
            $this->line("  ✗ Sin asignaciones vigentes de colaboradores.");
            return;
        }

        // Obtener numero_documento de los users colaboradores
        $userIds = $asignaciones->pluck('user_id')->unique();
        $docPorUser = DB::table('users')
            ->whereIn('id', $userIds)
            ->pluck('numero_documento', 'id');

        // Resolver persona_id desde dim_personas por numero_documento
        $documentos = $docPorUser->values()->filter();
        $personaIdPorDoc = DB::table('nomina.dim_personas')
            ->whereIn('numero_documento', $documentos)
            ->pluck('id', 'numero_documento');

        // Empleados que ya tienen registro hoy
        $yaExisten = DB::table('dbo.equipo_dia')
            ->where('fecha', $fecha)
            ->pluck('empleado_id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        $now     = now();
        $inserts = [];

        foreach ($asignaciones as $a) {
            $doc       = $docPorUser->get($a->user_id);
            $personaId = $doc ? $personaIdPorDoc->get($doc) : null;

            if (!$personaId || in_array((int) $personaId, $yaExisten)) continue;

            $inserts[] = [
                'supervisor_id' => $a->superior_id,
                'empleado_id'   => $personaId,
                'campana_id'    => $a->campana_id,
                'fecha'         => $fecha,
                'origen'        => EquipoDia::ORIGEN_BASE,
                'prestamo_id'   => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];

            $yaExisten[] = (int) $personaId;
        }

        if (empty($inserts)) {
            $this->line("  ✓ Sin registros nuevos desde asignaciones (todos ya existen).");
            return;
        }

        foreach (array_chunk($inserts, 100) as $chunk) {
            DB::table('dbo.equipo_dia')->insert($chunk);
        }

        $this->line("  ✓ " . count($inserts) . " registros construidos desde asignaciones vigentes.");
    }

    // ─── Paso 2: aplicar préstamos activos ────────────────────────────────────

    private function aplicarPrestamos(string $fecha, ?int $supervisorId): void
    {
        $query = EquipoPrestamo::where('tipo', EquipoPrestamo::TIPO_PRESTAMO)
            ->where('estado', EquipoPrestamo::ESTADO_APROBADO)
            ->where('fecha_inicio', '<=', $fecha)
            ->where('fecha_fin', '>=', $fecha);

        if ($supervisorId) {
            $query->where(function ($q) use ($supervisorId) {
                $q->where('supervisor_origen_id', $supervisorId)
                  ->orWhere('supervisor_destino_id', $supervisorId);
            });
        }

        $prestamos = $query->get();

        if ($prestamos->isEmpty()) {
            $this->line("  ✓ Sin préstamos activos para esta fecha.");
            return;
        }

        $now = now();

        foreach ($prestamos as $prestamo) {
            DB::table('dbo.equipo_dia')->updateOrInsert(
                ['empleado_id' => $prestamo->empleado_id, 'fecha' => $fecha],
                [
                    'supervisor_id' => $prestamo->supervisor_destino_id,
                    'campana_id'    => $prestamo->campana_destino_id,
                    'origen'        => EquipoDia::ORIGEN_PRESTAMO,
                    'prestamo_id'   => $prestamo->id,
                    'updated_at'    => $now,
                ]
            );
        }

        $this->line("  ✓ {$prestamos->count()} préstamos aplicados.");
    }

    // ─── Paso 3: rellenar colaboradores con asignación vigente sin cobertura ──

    private function rellenarDesdeAsignaciones(string $fecha, ?int $supervisorId): int
    {
        // Colaboradores con asignación vigente en esa fecha
        $asignaciones = UserAsignacion::where('estado', UserAsignacion::ESTADO_APROBADO)
            ->where('activo', true)
            ->where('rol', UserAsignacion::ROL_COLABORADOR)
            ->where('fecha_inicio', '<=', $fecha)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $fecha))
            ->when($supervisorId, fn($q) => $q->where('superior_id', $supervisorId))
            ->get(['user_id', 'superior_id', 'campana_id']);

        if ($asignaciones->isEmpty()) return 0;

        // Empleados que ya tienen registro para esa fecha
        $yaExisten = DB::table('dbo.equipo_dia')
            ->where('fecha', $fecha)
            ->pluck('empleado_id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        // Resolver user_id → persona_id via numero_documento
        $userIds      = $asignaciones->pluck('user_id')->unique();
        $docPorUser   = DB::table('users')->whereIn('id', $userIds)->pluck('numero_documento', 'id');
        $documentos   = $docPorUser->values()->filter();

        if ($documentos->isEmpty()) return 0;

        $personaIdPorDoc = DB::table('nomina.dim_personas')
            ->whereIn('numero_documento', $documentos)
            ->pluck('id', 'numero_documento');

        $now     = now();
        $inserts = [];

        foreach ($asignaciones as $a) {
            $doc       = $docPorUser->get($a->user_id);
            $personaId = $doc ? $personaIdPorDoc->get($doc) : null;

            if (!$personaId || in_array((int) $personaId, $yaExisten)) continue;

            $inserts[] = [
                'supervisor_id' => $a->superior_id,
                'empleado_id'   => $personaId,
                'campana_id'    => $a->campana_id,
                'fecha'         => $fecha,
                'origen'        => EquipoDia::ORIGEN_BASE,
                'prestamo_id'   => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];

            $yaExisten[] = (int) $personaId;
        }

        if (empty($inserts)) return 0;

        foreach (array_chunk($inserts, 100) as $chunk) {
            DB::table('dbo.equipo_dia')->insert($chunk);
        }

        return count($inserts);
    }
}
