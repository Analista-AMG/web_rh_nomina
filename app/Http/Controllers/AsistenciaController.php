<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Calendario;
use App\Models\Campana;
use App\Models\Contrato;
use App\Models\EquipoPrestamo;
use App\Models\ItemAsistencia;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\User;
use App\Models\UserAsignacion;
use App\Services\JerarquiaService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AsistenciaController extends Controller
{
    public function __construct(protected JerarquiaService $jerarquia) {}

    public function index(Request $request)
    {
        $user      = Auth::user();
        $esAdmin   = $user->hasRole('Administrador');
        $userId    = (int) $user->id;

        $pagos           = Pago::orderBy('periodo', 'desc')->orderBy('quincena', 'desc')->get(['id', 'periodo', 'quincena', 'inicio', 'fin']);
        $itemsAsistencia = ItemAsistencia::orderBy('codigo_asistencia')->get();
        $hoy             = Carbon::now();
        $diaActual       = $hoy->day;
        $mesActual       = $hoy->format('Y-m');

        $pagoId           = $request->input('pago_id') ?: ($pagos->first()?->id);
        $pagoSeleccionado = $pagoId ? $pagos->firstWhere('id', $pagoId) : null;

        $filas    = collect();
        $fechas   = [];
        $feriados = [];

        if ($pagoSeleccionado) {
            $inicioConsulta = Carbon::parse($pagoSeleccionado->inicio)->startOfDay();
            $finConsulta    = Carbon::parse($pagoSeleccionado->fin)->endOfDay();
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

            // ── Personas visibles durante el período (incluye ex-asignaciones) ──
            $personaIds = $esAdmin ? null : $this->jerarquia->personaIdsVisiblesEnPeriodo($user, $inicioStr, $finStr);

            // ── Préstamos del período (solo para no-admin) ─────────────────
            $prestamosOut          = collect();
            $prestamosIn           = collect();
            $prestamosInPersonaIds = [];

            if (!$esAdmin) {
                $prestamosOut = EquipoPrestamo::where('supervisor_origen_id', $userId)
                    ->where('estado', EquipoPrestamo::ESTADO_APROBADO)
                    ->where('fecha_inicio', '<=', $finStr)
                    ->where('fecha_fin', '>=', $inicioStr)
                    ->get(['empleado_id', 'fecha_inicio', 'fecha_fin']);

                $prestamosIn = EquipoPrestamo::where('supervisor_destino_id', $userId)
                    ->where('estado', EquipoPrestamo::ESTADO_APROBADO)
                    ->where('fecha_inicio', '<=', $finStr)
                    ->where('fecha_fin', '>=', $inicioStr)
                    ->get(['empleado_id', 'fecha_inicio', 'fecha_fin']);

                $prestamosInPersonaIds = $prestamosIn->pluck('empleado_id')->map(fn ($id) => (int) $id)->unique()->toArray();
            }

            // ── Construir mapa en_equipo[persona_id][fecha] ────────────────
            $empleadoEnEquipo = [];

            if (!$esAdmin && $personaIds !== null) {
                // Base: todos los días del período para cada persona visible
                foreach ($personaIds as $pid) {
                    foreach ($fechas as $fecha) {
                        $empleadoEnEquipo[$pid][$fecha->format('Y-m-d')] = true;
                    }
                }

                // Quitar días prestados hacia fuera
                foreach ($prestamosOut as $p) {
                    $pIni = max($p->fecha_inicio->format('Y-m-d'), $inicioStr);
                    $pFin = min($p->fecha_fin->format('Y-m-d'), $finStr);
                    foreach (CarbonPeriod::create($pIni, $pFin) as $date) {
                        unset($empleadoEnEquipo[(int) $p->empleado_id][$date->format('Y-m-d')]);
                    }
                }

                // Agregar días prestados hacia adentro
                foreach ($prestamosIn as $p) {
                    $pid  = (int) $p->empleado_id;
                    $pIni = max($p->fecha_inicio->format('Y-m-d'), $inicioStr);
                    $pFin = min($p->fecha_fin->format('Y-m-d'), $finStr);
                    foreach (CarbonPeriod::create($pIni, $pFin) as $date) {
                        $empleadoEnEquipo[$pid][$date->format('Y-m-d')] = true;
                    }
                }
            }

            // ── Contratos a mostrar ─────────────────────────────────────────
            $allPersonaIds = $personaIds !== null
                ? array_unique(array_merge($personaIds, $prestamosInPersonaIds))
                : null;

            $contratoQuery = Contrato::with(['persona', 'condicion', 'planilla'])
                ->where('inicio_contrato', '<=', $finStr)
                ->whereRaw('COALESCE(fecha_renuncia, fin_contrato) >= ?', [$inicioStr]);

            if ($allPersonaIds !== null) {
                if (empty($allPersonaIds)) {
                    // Sin personas visibles y sin préstamos entrantes: nada que mostrar
                    return view('asistencia.index', compact(
                        'pagos', 'pagoSeleccionado', 'filas', 'fechas',
                        'itemsAsistencia', 'feriados', 'esAdmin', 'hoy', 'diaActual', 'mesActual'
                    ));
                }
                $contratoQuery->whereIn('persona_id', $allPersonaIds);
            }

            $contratos = $contratoQuery->get();

            // Para admin: poblar en_equipo con todas las fechas (la vista usa $esAdmin como bypass)
            if ($esAdmin) {
                foreach ($contratos as $c) {
                    foreach ($fechas as $fecha) {
                        $empleadoEnEquipo[(int) $c->persona_id][$fecha->format('Y-m-d')] = true;
                    }
                }
            }

            // ── Campaña por persona ─────────────────────────────────────────
            $campanaMap = $this->buildCampanaMap(
                $contratos->pluck('persona_id')->map(fn ($id) => (int) $id)->unique()->toArray()
            );

            // ── Asistencias del período ────────────────────────────────────
            $asistencias = Asistencia::with('itemAsistencia')
                ->whereIn('contrato_id', $contratos->pluck('id'))
                ->whereBetween('fecha', [$inicioStr, $finStr])
                ->get()
                ->keyBy(fn ($a) => $a->contrato_id . '_' . $a->fecha->format('Y-m-d'));

            // ── Construir filas ─────────────────────────────────────────────
            foreach ($contratos as $contrato) {
                $personaId = (int) $contrato->persona_id;
                $enEquipo  = $empleadoEnEquipo[$personaId] ?? [];

                // No-admin: omitir personas sin ningún día activo
                if (!$esAdmin && empty($enEquipo)) {
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

            $filas = $filas->sortBy(
                fn ($f) => ($f['persona']->apellido_paterno ?? '') . ($f['persona']->nombres ?? '')
            )->values();
        }

        return view('asistencia.index', compact(
            'pagos', 'pagoSeleccionado', 'filas', 'fechas',
            'itemsAsistencia', 'feriados', 'esAdmin', 'hoy', 'diaActual', 'mesActual'
        ));
    }

    public function guardar(Request $request): JsonResponse
    {
        $request->validate([
            'contrato_id'        => 'required|integer',
            'fecha'              => 'required|date',
            'item_asistencia_id' => 'nullable|integer',
        ]);

        $contrato = Contrato::find($request->contrato_id);
        if (!$contrato) {
            return response()->json(['error' => 'Contrato no encontrado'], 404);
        }

        $user    = Auth::user();
        $esAdmin = $user->hasRole('Administrador');
        $userId  = (int) $user->id;
        $fecha   = Carbon::parse($request->fecha);
        $hoy     = Carbon::now();

        // Lock temporal: no-admin no puede editar meses anteriores si hoy >= día 3
        if (!$esAdmin && $hoy->day >= 3 && $fecha->format('Y-m') < $hoy->format('Y-m')) {
            return response()->json([
                'error' => 'El periodo está cerrado. Solo se puede editar el mes anterior los primeros 2 días del mes.',
            ], 403);
        }

        // Validación de autorización vía jerarquía histórica + préstamos
        if (!$esAdmin) {
            $empleadoId = (int) $contrato->persona_id;
            $fechaStr   = $request->fecha;

            // Usar visibilidad del período exacto (permite al ex-supervisor editar su época)
            $personaIds = $this->jerarquia->personaIdsVisiblesEnPeriodo($user, $fechaStr, $fechaStr);
            $esMio      = $personaIds === null || in_array($empleadoId, $personaIds);

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

        // Fecha dentro del rango del contrato
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

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Construye el mapa persona_id → nombre de campaña.
     * Ruta: persona.numero_documento → user.id → user_asignaciones.campana_id → campana.nombre
     */
    private function buildCampanaMap(array $personaIds): array
    {
        if (empty($personaIds)) return [];

        $docs = Persona::whereIn('id', $personaIds)
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

        $campanaIds = $asigPorUser->values()->unique()->filter()->toArray();
        $campanas   = Campana::whereIn('id', $campanaIds)->pluck('nombre', 'id');

        $map = [];
        foreach ($docs as $personaId => $numDoc) {
            $userId    = $usersByDoc[$numDoc] ?? null;
            $campanaId = $userId ? ($asigPorUser[(int) $userId] ?? null) : null;
            $map[(int) $personaId] = $campanaId ? ($campanas[$campanaId] ?? '-') : '-';
        }

        return $map;
    }
}
