<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Calendario;
use App\Models\Contrato;
use App\Models\EquipoPrestamo;
use App\Models\ItemAsistencia;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\Scopes\AlcanceUsuarioScope;
use App\Models\User;
use App\Models\UserAsignacion;
use App\Services\JerarquiaService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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
        $mostrarFiltroDirectos = false;
        $directosPersonaIds    = [];

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
            $editablePersonaIds  = $personaIds;
            $propioIdRRHH        = null;
            $puedeAutoEditarRRHH = false;

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
            } elseif ($esRRHH) {
                $puedeAutoEditarRRHH = UserAsignacion::where('user_id', $userId)
                    ->vigentes()
                    ->where('puede_editar_propia_asistencia', true)
                    ->exists();
            }

            $allPersonaIds = $personaIds !== null
                ? array_unique(array_merge($personaIds, $prestamosInPersonaIds))
                : null;

            if ($allPersonaIds !== null && empty($allPersonaIds)) {
                return view('asistencia.index', compact(
                    'pagos', 'pagoSeleccionado', 'filas', 'fechas',
                    'itemsAsistencia', 'feriados', 'esAdmin', 'hoy', 'diaActual', 'mesActual', 'bloquearAntesDe', 'userFechaInicioStr',
                    'equipoDiaSupervisores', 'mostrarFiltroDirectos', 'directosPersonaIds'
                ));
            }

            $contratos = $this->cargarContratos($allPersonaIds, $inicioStr, $finStr);

            // Para RRHH con el flag: resolver el persona_id propio desde los contratos ya cargados
            // (evita una query separada y funciona aunque propioPersonaId() no resuelva via numero_documento)
            if ($puedeAutoEditarRRHH && $user->numero_documento) {
                $propioContrato = $contratos->first(
                    fn($c) => ($c->persona->numero_documento ?? null) === $user->numero_documento
                );
                $propioIdRRHH = $propioContrato ? (int) $propioContrato->persona_id : null;
            }

            $empleadoEnEquipo = $this->construirMapaEnEquipo($esAdmin, $esRRHH, $contratos, $editablePersonaIds, $prestamosOut, $prestamosIn, $fechas, $inicioStr, $finStr, $userId, $propioIdRRHH);
            $campanaMap       = $this->jerarquia->campanaMapPorPersonas(
                $contratos->pluck('persona_id')->map(fn ($id) => (int) $id)->unique()->toArray()
            );
            $asistencias = Asistencia::with('itemAsistencia')
                ->whereIn('contrato_id', $contratos->pluck('id'))
                ->whereBetween('fecha', [$inicioStr, $finStr])
                ->get()
                ->keyBy(fn ($a) => $a->contrato_id . '_' . $a->fecha->format('Y-m-d'));

            $filas = $this->construirFilas($contratos, $empleadoEnEquipo, $campanaMap, $asistencias, $fechas, $esAdmin, $esRRHH, $personaIds);

            $equipoDiaSupervisores = $this->cargarSupervisoresPorDia($prestamosOut, $prestamosIn, $inicioStr, $finStr);

            // Filtro "Mis directos": visible si tiene asignación de Supervisor, Coordinador o JO vigente
            if (!$esAdmin && !$esRRHH) {
                $miMaxNivel = UserAsignacion::where('user_id', $userId)
                    ->where('estado', UserAsignacion::ESTADO_APROBADO)
                    ->where('fecha_inicio', '<=', $finStr)
                    ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicioStr))
                    ->get('rol')
                    ->map(fn ($a) => UserAsignacion::NIVEL_ROL[$a->rol] ?? 0)
                    ->max() ?? 0;

                if ($miMaxNivel >= 2) {
                    $mostrarFiltroDirectos = true;

                    [$personaToUser, $userToPersona] = $this->resolverPersonaUserMap($allPersonaIds ?? []);
                    $directUserIds = UserAsignacion::where('superior_id', $userId)
                        ->where('estado', UserAsignacion::ESTADO_APROBADO)
                        ->where('fecha_inicio', '<=', $finStr)
                        ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicioStr))
                        ->pluck('user_id')->unique()->toArray();
                    $propioPersonaId    = $userToPersona[$userId] ?? null;
                    $directosPersonaIds = array_values(array_unique(array_filter(
                        array_merge(
                            $propioPersonaId ? [$propioPersonaId] : [],
                            array_map(fn ($uid) => $userToPersona[$uid] ?? null, $directUserIds)
                        )
                    )));
                }
            }
        }

        return view('asistencia.index', compact(
            'pagos', 'pagoSeleccionado', 'filas', 'fechas',
            'itemsAsistencia', 'feriados', 'esAdmin', 'hoy', 'diaActual', 'mesActual', 'bloquearAntesDe',
            'equipoDiaSupervisores', 'userFechaInicioStr',
            'mostrarFiltroDirectos', 'directosPersonaIds'
        ));
    }

    public function guardar(Request $request): JsonResponse
    {
        $request->validate([
            'contrato_id'        => 'required|integer',
            'fecha'              => 'required|date',
            'item_asistencia_id' => 'nullable|integer',
            'tardanza'           => 'boolean',
            'min_tardanza'       => 'nullable|integer|min:1|max:999',
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
            // Período del mes calendario actual (no el pago que cubre la fecha exacta).
            $periodoMesActual = $hoy->format('Y-m');
            $pagoActual = Pago::where('periodo', $periodoMesActual)
                ->orderBy('quincena')
                ->first();

            $pagoFecha = Pago::where('inicio', '<=', $fechaStr)
                ->where('fin', '>=', $fechaStr)
                ->first();

            if ($pagoFecha && $pagoActual && $pagoFecha->periodo < $pagoActual->periodo && $hoy->day > 3) {
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
            $tardanza    = (bool) $request->input('tardanza', false);
            $minTardanza = $tardanza ? $request->input('min_tardanza') : null;

            $payload = [
                'item_asistencia_id' => $request->item_asistencia_id,
                'tardanza'           => $tardanza,
                'min_tardanza'       => $minTardanza,
            ];

            if ($asistencia) {
                $asistencia->update($payload);
            } else {
                Asistencia::create(array_merge([
                    'contrato_id' => $request->contrato_id,
                    'persona_id'  => $contrato->persona_id,
                    'fecha'       => $request->fecha,
                ], $payload));
            }
        } elseif ($asistencia) {
            $asistencia->delete();
        }

        return response()->json(['success' => true]);
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    /**
     * Fecha más antigua editable según el período de pago activo.
     * Ambas quincenas del período actual siempre son editables.
     * Día 1-3 del mes: el período anterior también es editable (gracia).
     * Día 4+: solo el período actual.
     */
    private function resolverBloqueo(Carbon $hoy): ?string
    {
        // Usar el mes calendario actual, no el pago que cubre la fecha exacta.
        // Evita que un período nuevo que arranca a fin de mes (ej: abril inicia 29-mar)
        // bloquee el mes en curso (marzo) antes de que termine.
        $periodoActual = $hoy->format('Y-m');
        $q1Actual = Pago::where('periodo', $periodoActual)
            ->orderBy('quincena')
            ->first();

        if (!$q1Actual) return null;

        $inicioEditable = $q1Actual->inicio->format('Y-m-d');

        if ($hoy->day > 3) {
            return $inicioEditable;
        }

        // Gracia días 1-3: permitir también el período anterior completo
        $q1Previo = Pago::where('periodo', '<', $periodoActual)
            ->orderByDesc('periodo')
            ->orderBy('quincena')
            ->first();

        return $q1Previo
            ? $q1Previo->inicio->format('Y-m-d')
            : $inicioEditable;
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
            ->get(['empleado_id', 'supervisor_destino_id', 'fecha_inicio', 'fecha_fin']);

        $prestamosIn = (clone $base())->where('supervisor_destino_id', $userId)
            ->get(['empleado_id', 'supervisor_origen_id', 'fecha_inicio', 'fecha_fin']);

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
     * pertenece al equipo del usuario (base desde UserAsignacion + ajuste por préstamos).
     *
     * Fuente única de verdad: UserAsignacion + EquipoPrestamo.
     */
    private function construirMapaEnEquipo(
        bool $esAdmin,
        bool $esRRHH,
        Collection $contratos,
        ?array $editablePersonaIds,
        Collection $prestamosOut,
        Collection $prestamosIn,
        array $fechas,
        string $inicioStr,
        string $finStr,
        int $userId = 0,
        ?int $propioIdRRHH = null
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

        $allPersonaIds = $contratos->pluck('persona_id')->unique()->map(fn ($id) => (int) $id)->toArray();
        [$personaToUser, $userToPersona] = $this->resolverPersonaUserMap($allPersonaIds);

        if ($esRRHH) {
            // RRHH editable: personas con asignación donde superior_id = este usuario
            $asigs = UserAsignacion::where('superior_id', $userId)
                ->where('estado', UserAsignacion::ESTADO_APROBADO)
                ->where('fecha_inicio', '<=', $finStr)
                ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicioStr))
                ->get(['user_id', 'fecha_inicio', 'fecha_fin']);

            foreach ($asigs as $a) {
                $pid = $userToPersona[(int) $a->user_id] ?? null;
                if (!$pid) continue;
                $desde = max($a->fecha_inicio->format('Y-m-d'), $inicioStr);
                $hasta = min($a->fecha_fin?->format('Y-m-d') ?? $finStr, $finStr);
                if ($desde > $hasta) continue;
                foreach (CarbonPeriod::create($desde, $hasta) as $d) {
                    $mapa[$pid][$d->format('Y-m-d')] = true;
                }
            }

            if ($propioIdRRHH !== null) {
                foreach ($fechas as $f) {
                    $mapa[$propioIdRRHH][$f->format('Y-m-d')] = true;
                }
            }
        } else {
            // Supervisor: días exactos en que fue superior directo (superior_id = userId)
            $directos = UserAsignacion::where('superior_id', $userId)
                ->where('estado', UserAsignacion::ESTADO_APROBADO)
                ->where('fecha_inicio', '<=', $finStr)
                ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicioStr))
                ->get(['user_id', 'fecha_inicio', 'fecha_fin']);

            $personasDirectas = [];
            foreach ($directos as $a) {
                $pid = $userToPersona[(int) $a->user_id] ?? null;
                if (!$pid) continue;
                $desde = max($a->fecha_inicio->format('Y-m-d'), $inicioStr);
                $hasta = min($a->fecha_fin?->format('Y-m-d') ?? $finStr, $finStr);
                if ($desde > $hasta) continue;
                foreach (CarbonPeriod::create($desde, $hasta) as $d) {
                    $mapa[$pid][$d->format('Y-m-d')] = true;
                }
                $personasDirectas[] = $pid;
            }

            // Coordinador/JO: personas visibles que no son subordinados directos.
            // Se usan sus propias fechas de asignación activa en el período.
            $noDirectas = array_values(array_diff($editablePersonaIds ?? [], $personasDirectas));
            if (!empty($noDirectas)) {
                $userIdsNd = array_values(array_filter(
                    array_map(fn ($pid) => $personaToUser[$pid] ?? null, $noDirectas)
                ));
                if (!empty($userIdsNd)) {
                    $asigs = UserAsignacion::whereIn('user_id', $userIdsNd)
                        ->where('estado', UserAsignacion::ESTADO_APROBADO)
                        ->where('fecha_inicio', '<=', $finStr)
                        ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicioStr))
                        ->get(['user_id', 'fecha_inicio', 'fecha_fin']);

                    foreach ($asigs as $a) {
                        $pid = $userToPersona[(int) $a->user_id] ?? null;
                        if (!$pid) continue;
                        $desde = max($a->fecha_inicio->format('Y-m-d'), $inicioStr);
                        $hasta = min($a->fecha_fin?->format('Y-m-d') ?? $finStr, $finStr);
                        if ($desde > $hasta) continue;
                        foreach (CarbonPeriod::create($desde, $hasta) as $d) {
                            $mapa[$pid][$d->format('Y-m-d')] = true;
                        }
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
     * Resuelve mapa bidireccional persona_id ↔ user_id para un conjunto de personas.
     * Ruta: persona.numero_documento → users.numero_documento.
     */
    private function resolverPersonaUserMap(array $personaIds): array
    {
        if (empty($personaIds)) return [[], []];

        $docs = Persona::withoutGlobalScope(AlcanceUsuarioScope::class)
            ->whereIn('id', $personaIds)
            ->whereNotNull('numero_documento')
            ->pluck('numero_documento', 'id');

        if ($docs->isEmpty()) return [[], []];

        $users = User::whereIn('numero_documento', $docs->values())
            ->pluck('id', 'numero_documento');

        $personaToUser = [];
        $userToPersona = [];
        foreach ($docs as $personaId => $doc) {
            $uid = $users[$doc] ?? null;
            if ($uid) {
                $personaToUser[(int) $personaId] = (int) $uid;
                $userToPersona[(int) $uid]        = (int) $personaId;
            }
        }

        return [$personaToUser, $userToPersona];
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

        // Una fila por persona, agrupando todos sus contratos del período
        $contratosPorPersona = $contratos->groupBy(fn ($c) => (int) $c->persona_id);

        foreach ($contratosPorPersona as $personaId => $personaContratos) {
            $enEquipo = $empleadoEnEquipo[$personaId] ?? [];

            if (!$esAdmin && !$esRRHH && empty($enEquipo)
                && ($visiblePersonaIds === null || !in_array($personaId, $visiblePersonaIds))) {
                continue;
            }

            // Ordenar contratos por fecha de inicio para iterar en orden
            $sortedContratos = $personaContratos->sortBy('inicio_contrato');

            // Mapa fecha → contrato activo en ese día
            $contratoPorFecha   = [];
            $asistenciasPeriodo = [];

            foreach ($fechas as $fecha) {
                $fStr = $fecha->format('Y-m-d');
                foreach ($sortedContratos as $c) {
                    $inicioC     = Carbon::parse($c->inicio_contrato);
                    $finEfectivo = $c->fecha_renuncia
                        ? Carbon::parse($c->fecha_renuncia)
                        : ($c->fin_contrato ? Carbon::parse($c->fin_contrato) : null);

                    if ($fecha->gte($inicioC) && (!$finEfectivo || $fecha->lte($finEfectivo))) {
                        $contratoPorFecha[$fStr]   = $c;
                        $asistenciasPeriodo[$fStr] = $asistencias->get($c->id . '_' . $fStr);
                        break;
                    }
                }
            }

            $contratoBase   = $sortedContratos->last();  // más reciente para datos de display
            $inicioContrato = Carbon::parse($sortedContratos->first()->inicio_contrato);

            $filas->push([
                'contrato'            => $contratoBase,
                'contrato_por_fecha'  => $contratoPorFecha,
                'persona'             => $contratoBase->persona,
                'inicio_contrato'     => $inicioContrato,
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
     * Mapa [persona_id][fecha] => nombre del supervisor.
     * Derivado de UserAsignacion (base) + EquipoPrestamo (override).
     */
    /**
     * Mapa [persona_id][fecha] => nombre del supervisor "del otro lado" del préstamo.
     *
     * - Préstamos salientes (supervisor_origen = yo): loan days → nombre supervisor_destino.
     *   Visible para mí en esas celdas donde el colaborador ya no está en mi equipo.
     *
     * - Préstamos entrantes (supervisor_destino = yo): días fuera del rango del préstamo → nombre supervisor_origen.
     *   Visible para mí en celdas donde el colaborador no es editable por mí.
     *   Los días del préstamo en sí (editables para mí) no tienen tooltip.
     */
    private function cargarSupervisoresPorDia(Collection $prestamosOut, Collection $prestamosIn, string $ini, string $fin): array
    {
        if ($prestamosOut->isEmpty() && $prestamosIn->isEmpty()) return [];

        $userIds = collect()
            ->merge($prestamosOut->pluck('supervisor_destino_id'))
            ->merge($prestamosIn->pluck('supervisor_origen_id'))
            ->filter()->unique()->values()->toArray();

        if (empty($userIds)) return [];

        $names = User::whereIn('id', $userIds)->pluck('name', 'id');
        $map   = [];

        // Salientes: días del préstamo → mostrar a dónde fue el colaborador
        foreach ($prestamosOut as $p) {
            $supName = $names[$p->supervisor_destino_id] ?? null;
            if (!$supName) continue;
            $desde = max($p->fecha_inicio->format('Y-m-d'), $ini);
            $hasta = min($p->fecha_fin->format('Y-m-d'), $fin);
            if ($desde > $hasta) continue;
            foreach (CarbonPeriod::create($desde, $hasta) as $d) {
                $map[(int) $p->empleado_id][$d->format('Y-m-d')] = $supName;
            }
        }

        // Entrantes: días FUERA del préstamo → mostrar de dónde viene el colaborador
        foreach ($prestamosIn as $p) {
            $supName   = $names[$p->supervisor_origen_id] ?? null;
            if (!$supName) continue;
            $loanDesde = max($p->fecha_inicio->format('Y-m-d'), $ini);
            $loanHasta = min($p->fecha_fin->format('Y-m-d'), $fin);
            foreach (CarbonPeriod::create($ini, $fin) as $d) {
                $dStr = $d->format('Y-m-d');
                if ($dStr >= $loanDesde && $dStr <= $loanHasta) continue; // skip loan days
                if (!isset($map[(int) $p->empleado_id][$dStr])) {
                    $map[(int) $p->empleado_id][$dStr] = $supName;
                }
            }
        }

        return $map;
    }
}
