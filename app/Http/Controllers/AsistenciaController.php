<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Calendario;
use App\Models\Contrato;
use App\Models\EquipoPrestamo;
use App\Models\ItemAsistencia;
use App\Models\Pago;
use App\Models\Scopes\AlcanceUsuarioScope;
use App\Models\UserAsignacion;
use App\Services\JerarquiaService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AsistenciaController extends Controller
{
    public function __construct(protected JerarquiaService $jerarquia) {}

    public function index(Request $request)
    {
        $user    = Auth::user();
        $esAdmin = $user->hasRole('Administrador');
        $userId  = (int) $user->id;
        $hoy     = Carbon::now();
        $hoyStr  = $hoy->toDateString();

        $pagos           = Pago::orderBy('periodo', 'desc')->orderBy('quincena', 'desc')->get(['id', 'periodo', 'quincena', 'inicio', 'fin']);
        $itemsAsistencia = ItemAsistencia::orderBy('codigo_asistencia')->get();
        $bloquearAntesDe = $esAdmin ? null : $this->resolverBloqueo($hoy);
        $diaActual       = $hoy->day;
        $mesActual       = $hoy->format('Y-m');

        $pagoId = $request->input('pago_id')
            ?: ($pagos->first(fn ($p) => $p->inicio->format('Y-m-d') <= $hoyStr && $p->fin->format('Y-m-d') >= $hoyStr)?->id
                ?? $pagos->first()?->id);
        $pagoSeleccionado = $pagoId ? $pagos->firstWhere('id', $pagoId) : null;

        $filas                 = collect();
        $fechas                = [];
        $feriados              = [];
        $equipoDiaSupervisores = [];
        $userFechaInicioStr    = null;

        if ($pagoSeleccionado) {
            $inicioConsulta = Carbon::parse($pagoSeleccionado->inicio)->startOfDay();
            $finConsulta    = Carbon::parse($pagoSeleccionado->fin)->min(Carbon::today())->endOfDay();
            $inicioStr      = $inicioConsulta->toDateString();
            $finStr         = $finConsulta->toDateString();

            foreach (CarbonPeriod::create($inicioConsulta, $finConsulta) as $f) {
                $fechas[] = $f->copy();
            }

            $feriados = Calendario::whereBetween('fecha', [$inicioStr, $finStr])
                ->where('tipo_dia', 'Feriado')
                ->pluck('fecha')
                ->map(fn ($f) => $f->format('Y-m-d'))
                ->flip()
                ->all();

            $esRRHH     = !$esAdmin && $user->hasRole('Recursos Humanos');
            $personaIds = $esAdmin ? null : $this->jerarquia->personaIdsVisiblesEnPeriodo($user, $inicioStr, $finStr);

            // Fecha mínima editable del usuario en el período (bloquea celdas anteriores en el front).
            // Aplica a todos los roles: nadie puede editar antes del inicio de su propia asignación.
            // Si no tiene ninguna asignación activa en el período, se bloquean todas las celdas.
            $userFechaInicioStr = null;
            if (!$esAdmin && !$esRRHH) {
                $raw = UserAsignacion::where('user_id', $userId)
                    ->where('estado', UserAsignacion::ESTADO_APROBADO)
                    ->where('fecha_inicio', '<=', $finStr)
                    ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicioStr))
                    ->min('fecha_inicio');
                $userFechaInicioStr = $raw
                    ? Carbon::parse($raw)->format('Y-m-d')
                    : Carbon::parse($finStr)->addDay()->format('Y-m-d'); // sin asignación → bloquear todo
            }

            [$prestamosOut, $prestamosIn, $prestamosInPersonaIds] = $esAdmin
                ? [collect(), collect(), []]
                : $this->cargarPrestamos($userId, $inicioStr, $finStr);

            // Editable = visible sin el propio usuario si no tiene el flag habilitado
            $editablePersonaIds = $personaIds;
            if ($personaIds !== null && !$esRRHH) {
                $propioId = $this->jerarquia->propioPersonaId($user);
                if ($propioId) {
                    $puedeAutoEditar = UserAsignacion::where('user_id', $userId)
                        ->vigentes()
                        ->where('puede_editar_propia_asistencia', true)
                        ->exists();
                    if (!$puedeAutoEditar) {
                        $editablePersonaIds = array_values(array_diff($personaIds, [$propioId]));
                    }
                }
            }

            $allPersonaIds = $personaIds !== null
                ? array_unique(array_merge($personaIds, $prestamosInPersonaIds))
                : null;

            if ($allPersonaIds !== null && empty($allPersonaIds)) {
                return view('asistencia.index', compact(
                    'pagos', 'pagoSeleccionado', 'filas', 'fechas',
                    'itemsAsistencia', 'feriados', 'esAdmin', 'hoy', 'diaActual', 'mesActual', 'bloquearAntesDe', 'userFechaInicioStr'
                ));
            }

            $contratos        = $this->cargarContratos($allPersonaIds, $inicioStr, $finStr);

            $empleadoEnEquipo = $this->construirMapaEnEquipo($esAdmin, $esRRHH, $contratos, $editablePersonaIds, $prestamosOut, $prestamosIn, $fechas, $inicioStr, $finStr, $userId);
            $campanaMap       = $this->jerarquia->campanaMapPorPersonas(
                $contratos->pluck('persona_id')->map(fn ($id) => (int) $id)->unique()->toArray()
            );
            $asistencias = Asistencia::with('itemAsistencia')
                ->whereIn('contrato_id', $contratos->pluck('id'))
                ->whereBetween('fecha', [$inicioStr, $finStr])
                ->get()
                ->keyBy(fn ($a) => $a->contrato_id . '_' . $a->fecha->format('Y-m-d'));

            $filas = $this->construirFilas($contratos, $empleadoEnEquipo, $campanaMap, $asistencias, $fechas, $esAdmin, $esRRHH, $personaIds);

            $equipoDiaSupervisores = $this->cargarEquipoDiaSupervisores($allPersonaIds, $inicioStr, $finStr);
        }

        return view('asistencia.index', compact(
            'pagos', 'pagoSeleccionado', 'filas', 'fechas',
            'itemsAsistencia', 'feriados', 'esAdmin', 'hoy', 'diaActual', 'mesActual', 'bloquearAntesDe',
            'equipoDiaSupervisores', 'userFechaInicioStr'
        ));
    }

    public function guardar(Request $request): JsonResponse
    {
        $request->validate([
            'contrato_id'        => 'required|integer',
            'fecha'              => 'required|date',
            'item_asistencia_id' => 'nullable|integer',
        ]);

        $contrato = Contrato::withoutGlobalScope(AlcanceUsuarioScope::class)->find($request->contrato_id);
        if (!$contrato) {
            return response()->json(['error' => 'Contrato no encontrado'], 404);
        }

        $user    = Auth::user();
        $esAdmin = $user->hasRole('Administrador');
        $userId  = (int) $user->id;
        $fecha   = Carbon::parse($request->fecha);
        $hoy     = Carbon::now();
        $fechaStr = $request->fecha;

        if ($fecha->isAfter(Carbon::today())) {
            return response()->json(['error' => 'No se puede registrar asistencia en fechas futuras.'], 403);
        }

        if (!$esAdmin) {
            $pagoActual = Pago::where('inicio', '<=', $hoy->toDateString())
                ->where('fin', '>=', $hoy->toDateString())
                ->first();

            $pagoFecha = Pago::where('inicio', '<=', $fechaStr)
                ->where('fin', '>=', $fechaStr)
                ->first();

            if ($pagoFecha && $pagoActual && $pagoFecha->id < $pagoActual->id && $hoy->day > 3) {
                return response()->json([
                    'error' => 'El período está cerrado. Solo se puede editar el período anterior los primeros 3 días del mes.',
                ], 403);
            }
        }

        if (!$esAdmin) {
            // Verificar que el usuario tenía asignación activa en la fecha a editar.
            $userTeniaAsignacion = UserAsignacion::where('user_id', $userId)
                ->where('estado', UserAsignacion::ESTADO_APROBADO)
                ->where('fecha_inicio', '<=', $fechaStr)
                ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $fechaStr))
                ->exists();

            if (!$userTeniaAsignacion) {
                return response()->json([
                    'error' => 'No tienes asignación activa para esta fecha.',
                ], 403);
            }
        }

        if (!$esAdmin) {
            $empleadoId = (int) $contrato->persona_id;

            // Validar con el período completo del pago, igual que la vista.
            // Evita rechazar fechas anteriores al fecha_inicio del colaborador
            // cuando su asignación cubre el período (JO/Coordinador usan fallback de todos los días).
            $pagoDelDia = Pago::where('inicio', '<=', $fechaStr)
                ->where('fin', '>=', $fechaStr)
                ->first();
            $periodoIni = $pagoDelDia ? $pagoDelDia->inicio->toDateString() : $fechaStr;
            $periodoFin = $pagoDelDia ? $pagoDelDia->fin->toDateString()   : $fechaStr;

            $personaIds = $this->jerarquia->personaIdsVisiblesEnPeriodo($user, $periodoIni, $periodoFin);
            $esMio      = $personaIds === null || in_array($empleadoId, $personaIds);

            // Bloquear edición de la propia asistencia si el flag no está habilitado
            if ($personaIds !== null && $esMio) {
                $propioId = $this->jerarquia->propioPersonaId($user);
                if ($propioId === $empleadoId) {
                    $puedeAutoEditar = UserAsignacion::where('user_id', $userId)
                        ->vigentes()
                        ->where('puede_editar_propia_asistencia', true)
                        ->exists();
                    if (!$puedeAutoEditar) {
                        return response()->json([
                            'error' => 'No tienes autorización para editar tu propia asistencia.',
                        ], 403);
                    }
                }
            }

            $prestadoFuera = $esMio && EquipoPrestamo::where('supervisor_origen_id', $userId)
                ->where('empleado_id', $empleadoId)
                ->where('estado', EquipoPrestamo::ESTADO_APROBADO)
                ->where('fecha_inicio', '<=', $fechaStr)
                ->where('fecha_fin', '>=', $fechaStr)
                ->exists();

            $prestadoDentro = !$esMio && EquipoPrestamo::where('supervisor_destino_id', $userId)
                ->where('empleado_id', $empleadoId)
                ->where('estado', EquipoPrestamo::ESTADO_APROBADO)
                ->where('fecha_inicio', '<=', $fechaStr)
                ->where('fecha_fin', '>=', $fechaStr)
                ->exists();

            if ((!$esMio || $prestadoFuera) && !$prestadoDentro) {
                return response()->json([
                    'error' => 'No tienes autorización para marcar asistencia a este colaborador en esta fecha.',
                ], 403);
            }
        }

        $inicioC     = Carbon::parse($contrato->inicio_contrato);
        $finEfectivo = $contrato->fecha_renuncia
            ? Carbon::parse($contrato->fecha_renuncia)
            : ($contrato->fin_contrato ? Carbon::parse($contrato->fin_contrato) : null);

        if ($fecha->lt($inicioC) || ($finEfectivo && $fecha->gt($finEfectivo))) {
            return response()->json(['error' => 'Fecha fuera del rango del contrato'], 400);
        }

        $asistencia = Asistencia::where('contrato_id', $request->contrato_id)
            ->where('fecha', $request->fecha)
            ->first();

        if ($request->item_asistencia_id) {
            if ($asistencia) {
                $asistencia->update(['item_asistencia_id' => $request->item_asistencia_id]);
            } else {
                Asistencia::create([
                    'contrato_id'        => $request->contrato_id,
                    'fecha'              => $request->fecha,
                    'item_asistencia_id' => $request->item_asistencia_id,
                ]);
            }
        } elseif ($asistencia) {
            $asistencia->delete();
        }

        return response()->json(['success' => true]);
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    /**
     * Fecha más antigua editable según el período de pago actual.
     * Día 1-3: período anterior sigue editable.
     * Día 4+:  solo el período actual y futuros.
     */
    private function resolverBloqueo(Carbon $hoy): ?string
    {
        $pagoActual = Pago::where('inicio', '<=', $hoy->toDateString())
            ->where('fin', '>=', $hoy->toDateString())
            ->first();

        if (!$pagoActual) return null;

        if ($hoy->day > 3) {
            return $pagoActual->inicio->format('Y-m-d');
        }

        $pagoPrevio = Pago::where('id', '<', $pagoActual->id)->orderByDesc('id')->first();
        return $pagoPrevio
            ? $pagoPrevio->inicio->format('Y-m-d')
            : $pagoActual->inicio->format('Y-m-d');
    }

    /**
     * Préstamos del período para un supervisor: salientes, entrantes e IDs de personas entrantes.
     */
    private function cargarPrestamos(int $userId, string $ini, string $fin): array
    {
        $base = fn () => EquipoPrestamo::where('estado', EquipoPrestamo::ESTADO_APROBADO)
            ->where('fecha_inicio', '<=', $fin)
            ->where('fecha_fin', '>=', $ini);

        $prestamosOut = (clone $base())->where('supervisor_origen_id', $userId)
            ->get(['empleado_id', 'fecha_inicio', 'fecha_fin']);

        $prestamosIn = (clone $base())->where('supervisor_destino_id', $userId)
            ->get(['empleado_id', 'fecha_inicio', 'fecha_fin']);

        $prestamosInPersonaIds = $prestamosIn->pluck('empleado_id')->map(fn ($id) => (int) $id)->unique()->toArray();

        return [$prestamosOut, $prestamosIn, $prestamosInPersonaIds];
    }

    /**
     * Contratos activos en el período con relaciones necesarias para la vista.
     */
    private function cargarContratos(?array $personaIds, string $ini, string $fin): Collection
    {
        $q = Contrato::withoutGlobalScope(AlcanceUsuarioScope::class)
            ->with(['persona' => fn ($q) => $q->withoutGlobalScope(AlcanceUsuarioScope::class), 'condicion', 'centroCosto', 'familia'])
            ->where('inicio_contrato', '<=', $fin)
            ->where(function ($q) use ($ini) {
                $q->whereNull('fin_contrato')->whereNull('fecha_renuncia')
                  ->orWhereRaw('COALESCE(fecha_renuncia, fin_contrato) >= ?', [$ini]);
            });

        if ($personaIds !== null) {
            $q->whereIn('persona_id', $personaIds);
        }

        return $q->get();
    }

    /**
     * Mapa [persona_id][fecha] => true indicando en qué días cada persona
     * pertenece al equipo del supervisor (base + préstamos in/out).
     */
    private function construirMapaEnEquipo(
        bool $esAdmin,
        bool $esRRHH,
        Collection $contratos,
        ?array $personaIds,
        Collection $prestamosOut,
        Collection $prestamosIn,
        array $fechas,
        string $inicioStr,
        string $finStr,
        int $userId = 0
    ): array {
        $mapa = [];

        if ($esAdmin) {
            foreach ($contratos as $c) {
                foreach ($fechas as $fecha) {
                    $mapa[(int) $c->persona_id][$fecha->format('Y-m-d')] = true;
                }
            }
            return $mapa;
        }

        if ($esRRHH) {
            // RRHH ve todos pero solo edita su equipo directo (equipo_dia)
            $equipoDia = \Illuminate\Support\Facades\DB::table('dbo.equipo_dia')
                ->where('supervisor_id', $userId)
                ->whereBetween('fecha', [$inicioStr, $finStr])
                ->get(['empleado_id', 'fecha']);

            foreach ($equipoDia as $row) {
                $mapa[(int) $row->empleado_id][$row->fecha] = true;
            }
        } else {
            // Usar equipo_dia para respetar la fecha de inicio real de cada colaborador.
            // Supervisores: tienen registros directos → mapa preciso desde su fecha_inicio.
            // Coordinadores/JO: no tienen registros directos → fallback a todos los días.
            $personasConEquipo = [];

            if ($userId && !empty($personaIds)) {
                $rows = \Illuminate\Support\Facades\DB::table('dbo.equipo_dia')
                    ->where('supervisor_id', $userId)
                    ->whereIn('empleado_id', $personaIds)
                    ->whereBetween('fecha', [$inicioStr, $finStr])
                    ->get(['empleado_id', 'fecha']);

                foreach ($rows as $row) {
                    $mapa[(int) $row->empleado_id][$row->fecha] = true;
                }

                $personasConEquipo = collect($rows)->pluck('empleado_id')
                    ->unique()->map(fn ($id) => (int) $id)->toArray();
            }

            // Coordinador/JO: no tienen registros directos en equipo_dia.
            // - Colaboradores: tienen equipo_dia → heredar esos registros (misma restricción que su supervisor).
            // - Supervisores/Coordinadores visibles: no tienen equipo_dia → fallback todos los días.
            $sinEquipo = array_values(array_diff($personaIds ?? [], $personasConEquipo));
            if (!empty($sinEquipo)) {
                $rowsFallback = \Illuminate\Support\Facades\DB::table('dbo.equipo_dia')
                    ->whereIn('empleado_id', $sinEquipo)
                    ->whereBetween('fecha', [$inicioStr, $finStr])
                    ->get(['empleado_id', 'fecha']);

                $conRegistros = collect($rowsFallback)
                    ->pluck('empleado_id')->unique()->map(fn ($id) => (int) $id)->toArray();

                // Personas con equipo_dia en el período → solo esas fechas
                foreach ($rowsFallback as $row) {
                    $mapa[(int) $row->empleado_id][$row->fecha] = true;
                }

                // Personas sin equipo_dia en el período (supervisores, coordinadores) → todos los días
                foreach (array_diff($sinEquipo, $conRegistros) as $pid) {
                    foreach ($fechas as $fecha) {
                        $mapa[$pid][$fecha->format('Y-m-d')] = true;
                    }
                }
            }
        }

        // Quitar días prestados hacia fuera
        foreach ($prestamosOut as $p) {
            $pIni = max($p->fecha_inicio->format('Y-m-d'), $inicioStr);
            $pFin = min($p->fecha_fin->format('Y-m-d'), $finStr);
            foreach (CarbonPeriod::create($pIni, $pFin) as $date) {
                unset($mapa[(int) $p->empleado_id][$date->format('Y-m-d')]);
            }
        }

        // Agregar días prestados hacia adentro
        foreach ($prestamosIn as $p) {
            $pid  = (int) $p->empleado_id;
            $pIni = max($p->fecha_inicio->format('Y-m-d'), $inicioStr);
            $pFin = min($p->fecha_fin->format('Y-m-d'), $finStr);
            foreach (CarbonPeriod::create($pIni, $pFin) as $date) {
                $mapa[$pid][$date->format('Y-m-d')] = true;
            }
        }

        return $mapa;
    }

    /**
     * Construye y ordena las filas para la vista de asistencia.
     */
    private function construirFilas(
        Collection $contratos,
        array $empleadoEnEquipo,
        array $campanaMap,
        Collection $asistencias,
        array $fechas,
        bool $esAdmin,
        bool $esRRHH = false,
        ?array $visiblePersonaIds = null
    ): Collection {
        $filas = collect();

        foreach ($contratos as $contrato) {
            $personaId = (int) $contrato->persona_id;
            $enEquipo  = $empleadoEnEquipo[$personaId] ?? [];

            // Mostrar la fila si es editable, o si está en visiblePersonaIds (read-only)
            if (!$esAdmin && !$esRRHH && empty($enEquipo)
                && ($visiblePersonaIds === null || !in_array($personaId, $visiblePersonaIds))) {
                continue;
            }

            $inicioC     = Carbon::parse($contrato->inicio_contrato);
            $finEfectivo = $contrato->fecha_renuncia
                ? Carbon::parse($contrato->fecha_renuncia)
                : ($contrato->fin_contrato ? Carbon::parse($contrato->fin_contrato) : null);

            $asistenciasPeriodo = [];
            foreach ($fechas as $fecha) {
                $fStr = $fecha->format('Y-m-d');
                $asistenciasPeriodo[$fStr] = $asistencias->get($contrato->id . '_' . $fStr);
            }

            $filas->push([
                'contrato'            => $contrato,
                'persona'             => $contrato->persona,
                'inicio_contrato'     => $inicioC,
                'fin_efectivo'        => $finEfectivo,
                'campana'             => $campanaMap[$personaId] ?? '-',
                'en_equipo'           => $enEquipo,
                'asistencias_periodo' => $asistenciasPeriodo,
            ]);
        }

        return $filas->sortBy(
            fn ($f) => ($f['persona']->apellido_paterno ?? '') . ($f['persona']->nombres ?? '')
        )->values();
    }

    /**
     * Mapa [persona_id][fecha] => nombre del supervisor según equipo_dia.
     */
    private function cargarEquipoDiaSupervisores(?array $personaIds, string $ini, string $fin): array
    {
        $query = DB::table('dbo.equipo_dia')
            ->whereBetween('fecha', [$ini, $fin]);

        if ($personaIds !== null) {
            if (empty($personaIds)) return [];
            $query->whereIn('empleado_id', $personaIds);
        }

        $recs = $query->get(['empleado_id', 'fecha', 'supervisor_id']);

        $names = \App\Models\User::whereIn('id', $recs->pluck('supervisor_id')->filter()->unique()->toArray())
            ->pluck('name', 'id');

        $map = [];
        foreach ($recs as $rec) {
            $name = $names[$rec->supervisor_id] ?? null;
            if ($name) {
                $map[(int) $rec->empleado_id][$rec->fecha] = $name;
            }
        }

        return $map;
    }
}
