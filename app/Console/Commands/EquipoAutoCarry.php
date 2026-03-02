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
        });

        return self::SUCCESS;
    }

    // ─── Paso 1: copiar equipo_dia del día anterior con bubble-up ─────────────

    private function carryDesdeEquipoDia(string $fecha, ?int $supervisorId): void
    {
        // Buscar la fecha más reciente anterior con registros base
        $fechaAnterior = DB::table('dbo.equipo_dia')
            ->where('fecha', '<', $fecha)
            ->where('origen', EquipoDia::ORIGEN_BASE)
            ->when($supervisorId, fn($q) => $q->where('supervisor_id', $supervisorId))
            ->max('fecha');

        if (!$fechaAnterior) {
            $this->line("  ✗ Sin equipo_dia previo para hacer carry.");
            return;
        }

        // Registros del día anterior a copiar
        $previos = DB::table('dbo.equipo_dia')
            ->where('fecha', $fechaAnterior)
            ->where('origen', EquipoDia::ORIGEN_BASE)
            ->when($supervisorId, fn($q) => $q->where('supervisor_id', $supervisorId))
            ->get();

        if ($previos->isEmpty()) {
            $this->line("  ✓ Sin registros previos para copiar.");
            return;
        }

        // Empleados que ya tienen registro hoy (no duplicar)
        $yaExisten = DB::table('dbo.equipo_dia')
            ->where('fecha', $fecha)
            ->when($supervisorId, fn($q) => $q->where('supervisor_id', $supervisorId))
            ->pluck('empleado_id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        // Pre-cargar supervisores activos + cadena de superiores para bubble-up
        $supervisorIds = $previos->pluck('supervisor_id')->filter()->unique()->values();
        $this->precargarCadenaSupervisores($supervisorIds->toArray());

        $now     = now();
        $inserts = [];

        foreach ($previos as $reg) {
            if (in_array((int) $reg->empleado_id, $yaExisten)) continue;

            // Resolver supervisor activo (bubble-up si está inactivo)
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
        }

        if (empty($inserts)) {
            $this->line("  ✓ Sin registros nuevos (todos ya existen en equipo_dia).");
            return;
        }

        foreach (array_chunk($inserts, 100) as $chunk) {
            DB::table('dbo.equipo_dia')->insertOrIgnore($chunk);
        }

        $this->line("  ✓ " . count($inserts) . " registros copiados desde {$fechaAnterior}.");
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
}
