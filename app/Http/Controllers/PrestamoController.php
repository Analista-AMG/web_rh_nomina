<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\EquipoDia;
use App\Models\EquipoPrestamo;
use App\Models\Persona;
use App\Models\Scopes\AlcanceUsuarioScope;
use App\Models\User;
use App\Models\UserAsignacion;
use App\Services\JerarquiaService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PrestamoController extends Controller
{
    public function __construct(protected JerarquiaService $jerarquia) {}

    // ── Vista principal ───────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $user    = Auth::user();
        $esAdmin = $user->hasRole('Administrador');
        $userId  = (int) $user->id;
        $hoy     = Carbon::today()->toDateString();

        $nivelPropio  = $esAdmin ? 99 : UserAsignacion::where('user_id', $userId)
            ->where('estado', UserAsignacion::ESTADO_APROBADO)
            ->get()
            ->max(fn ($a) => UserAsignacion::NIVEL_ROL[$a->rol] ?? 0) ?? 0;
        $puedeAprobar = $esAdmin || $nivelPropio >= UserAsignacion::NIVEL_ROL[UserAsignacion::ROL_COORDINADOR];
        $verTodos     = $puedeAprobar;

        $baseQ = fn () => EquipoPrestamo::with([
            'empleado'           => fn ($q) => $q->withoutGlobalScope(AlcanceUsuarioScope::class),
            'supervisorOrigen', 'supervisorDestino',
            'campanaOrigen', 'campanaDestino', 'aprobadoPor', 'creadoPor',
        ])->where('tipo', EquipoPrestamo::TIPO_PRESTAMO)
          ->when(!$verTodos, fn ($q) => $q->where(function ($q2) use ($userId) {
              $q2->where('supervisor_origen_id', $userId)
                 ->orWhere('supervisor_destino_id', $userId)
                 ->orWhere('creado_por', $userId);
          }));

        $activos    = $baseQ()->aprobados()
                        ->where('fecha_inicio', '<=', $hoy)
                        ->where('fecha_fin', '>=', $hoy)
                        ->orderBy('fecha_fin')->get();

        $pendientes = $baseQ()->pendientes()->orderBy('fecha_inicio')->get();

        $proximos   = $baseQ()->aprobados()
                        ->where('fecha_inicio', '>', $hoy)
                        ->orderBy('fecha_inicio')->get();

        $historial  = $baseQ()->where(function ($q) use ($hoy) {
            $q->whereIn('estado', [EquipoPrestamo::ESTADO_RECHAZADO, EquipoPrestamo::ESTADO_ANULADO])
              ->orWhere(fn ($q2) => $q2->where('estado', EquipoPrestamo::ESTADO_APROBADO)
                                       ->where('fecha_fin', '<', $hoy));
        })->orderByDesc('updated_at')->limit(30)->get();

        // Modal "Prestar": mis colaboradores vía jerarquía
        $personaIds      = $this->jerarquia->personaIdsVisibles($user);
        $verTodosPrestar = $personaIds === null || $user->hasRole('Reclutamiento');

        if ($verTodosPrestar) {
            // Admin, RRHH, Reclutamiento: ven todos los colaboradores vigentes del sistema
            $colaboradorUserIds = UserAsignacion::where('rol', UserAsignacion::ROL_COLABORADOR)
                ->vigentes()
                ->pluck('user_id');
            $docs = User::whereIn('id', $colaboradorUserIds)
                ->whereNotNull('numero_documento')
                ->pluck('numero_documento');
            $misColaboradores = Persona::withoutGlobalScope(AlcanceUsuarioScope::class)
                ->whereIn('numero_documento', $docs)
                ->orderBy('apellido_paterno')->orderBy('nombres')->get();
        } else {
            $misColaboradores = !empty($personaIds)
                ? Persona::whereIn('id', $personaIds)->orderBy('apellido_paterno')->orderBy('nombres')->get()
                : collect();
        }

        $contratosFecha = $misColaboradores->isNotEmpty()
            ? Contrato::withoutGlobalScope(AlcanceUsuarioScope::class)
                ->whereIn('persona_id', $misColaboradores->pluck('id'))
                ->orderByDesc('inicio_contrato')
                ->get(['persona_id', 'inicio_contrato', 'fin_contrato', 'fecha_renuncia'])
                ->groupBy('persona_id')
                ->map(fn ($g) => $g->first())
            : collect();

        $supervisores = User::where('activo', true)
            ->whereHas('asignaciones', fn ($q) => $q
                ->where('estado', UserAsignacion::ESTADO_APROBADO)
                ->whereIn('rol', [
                    UserAsignacion::ROL_SUPERVISOR,
                    UserAsignacion::ROL_COORDINADOR,
                    UserAsignacion::ROL_JEFE_OPERACIONES,
                ])
            )
            ->with(['asignaciones' => fn ($q) => $q
                ->where('estado', UserAsignacion::ESTADO_APROBADO)
                ->whereIn('rol', [
                    UserAsignacion::ROL_SUPERVISOR,
                    UserAsignacion::ROL_COORDINADOR,
                    UserAsignacion::ROL_JEFE_OPERACIONES,
                ])
                ->whereNull('fecha_fin')
                ->with('campana:id,nombre')
                ->latest('id')
                ->limit(1)
            ])
            ->where('id', '!=', $userId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $totalPendientes = $puedeAprobar
            ? EquipoPrestamo::pendientes()->where('tipo', EquipoPrestamo::TIPO_PRESTAMO)->count()
            : 0;

        return view('equipos.index', compact(
            'activos', 'pendientes', 'proximos', 'historial',
            'misColaboradores', 'contratosFecha', 'supervisores',
            'puedeAprobar', 'esAdmin', 'totalPendientes', 'hoy', 'userId'
        ));
    }

    // ── Crear préstamo (prestar o solicitar) ──────────────────────────────────

    public function crearPrestamo(Request $request): JsonResponse
    {
        $request->validate([
            'accion'                => 'required|in:prestar,solicitar',
            'empleado_id'           => 'required|integer',
            'supervisor_destino_id' => 'required_if:accion,prestar|nullable|integer',
            'supervisor_origen_id'  => 'nullable|integer',
            'fecha_inicio'          => 'required|date',
            'fecha_fin'             => 'required|date|after_or_equal:fecha_inicio',
            'motivo'                => 'nullable|string|max:500',
        ]);

        $user       = Auth::user();
        $userId     = (int) $user->id;
        $empleadoId = (int) $request->empleado_id;

        if ($request->accion === 'prestar') {
            $supervisorDestinoId = (int) $request->supervisor_destino_id;

            $visibles        = $this->jerarquia->personaIdsVisibles($user);
            $puedeVerTodos   = $visibles === null || $user->hasRole('Reclutamiento');
            if (!$puedeVerTodos && !in_array($empleadoId, $visibles)) {
                return response()->json(['error' => 'Este colaborador no está en tu equipo.'], 422);
            }

            $tieneContrato = Contrato::withoutGlobalScope(AlcanceUsuarioScope::class)
                ->where('persona_id', $empleadoId)
                ->where('inicio_contrato', '<=', $request->fecha_inicio)
                ->where(fn ($q) => $q
                    ->whereRaw(Contrato::FIN_EFECTIVO . ' >= ?', [$request->fecha_fin])
                    ->orWhereRaw(Contrato::FIN_EFECTIVO . ' IS NULL')
                )
                ->exists();

            if (!$tieneContrato) {
                return response()->json(['error' => 'El colaborador no tiene contrato vigente para el período indicado.'], 422);
            }

            $supervisorOrigenId = $this->jerarquia->resolverSupervisorOrigen($empleadoId, $userId);

            if ($supervisorOrigenId === $supervisorDestinoId) {
                return response()->json(['success' => true, 'skipped' => true]);
            }
        } else {
            $supervisorDestinoId = $userId;
            $supervisorOrigenId  = $request->filled('supervisor_origen_id')
                ? (int) $request->supervisor_origen_id
                : $this->jerarquia->resolverSupervisorOrigen($empleadoId, $userId);

            if (!$supervisorOrigenId || $supervisorOrigenId === $userId) {
                return response()->json(['error' => 'No se pudo determinar el supervisor actual del colaborador.'], 422);
            }
        }

        if (EquipoPrestamo::tieneSolapamiento($empleadoId, $request->fecha_inicio, $request->fecha_fin)) {
            return response()->json(['error' => 'Ya existe un préstamo activo para este colaborador en ese rango de fechas.'], 422);
        }

        $campanaOrigenId  = $this->jerarquia->campanaDelEmpleado($empleadoId)
                         ?? $this->jerarquia->campanaDelSupervisor($supervisorOrigenId);
        $campanaDestinoId = $this->jerarquia->campanaDelSupervisor($supervisorDestinoId);

        if (!$campanaOrigenId || !$campanaDestinoId) {
            return response()->json(['error' => 'No se pudo determinar la campaña de uno de los supervisores.'], 422);
        }

        $prestamo = EquipoPrestamo::create([
            'empleado_id'           => $empleadoId,
            'supervisor_origen_id'  => $supervisorOrigenId,
            'supervisor_destino_id' => $supervisorDestinoId,
            'campana_origen_id'     => $campanaOrigenId,
            'campana_destino_id'    => $campanaDestinoId,
            'fecha_inicio'          => $request->fecha_inicio,
            'fecha_fin'             => $request->fecha_fin,
            'tipo'                  => EquipoPrestamo::TIPO_PRESTAMO,
            'estado'                => EquipoPrestamo::ESTADO_PENDIENTE,
            'motivo'                => $request->motivo,
            'creado_por'            => $userId,
        ]);

        return response()->json(['success' => true, 'id' => $prestamo->id]);
    }

    // ── Aprobar préstamo → aplica equipo_dia para todo el rango ──────────────

    public function aprobarPrestamo(EquipoPrestamo $prestamo): JsonResponse
    {
        if ($prestamo->estado !== EquipoPrestamo::ESTADO_PENDIENTE) {
            return response()->json(['error' => 'Este préstamo ya fue procesado.'], 422);
        }

        DB::transaction(function () use ($prestamo) {
            $prestamo->update([
                'estado'       => EquipoPrestamo::ESTADO_APROBADO,
                'aprobado_por' => (int) Auth::id(),
                'aprobado_en'  => now(),
            ]);

            foreach (CarbonPeriod::create($prestamo->fecha_inicio, $prestamo->fecha_fin) as $date) {
                EquipoDia::updateOrCreate(
                    ['empleado_id' => $prestamo->empleado_id, 'fecha' => $date->toDateString()],
                    [
                        'supervisor_id' => $prestamo->supervisor_destino_id,
                        'campana_id'    => $prestamo->campana_destino_id,
                        'origen'        => EquipoDia::ORIGEN_PRESTAMO,
                        'prestamo_id'   => $prestamo->id,
                    ]
                );
            }
        });

        return response()->json(['success' => true]);
    }

    // ── Anular préstamo aprobado (revierte equipo_dia de fechas futuras) ──────

    public function anularPrestamo(Request $request, EquipoPrestamo $prestamo): JsonResponse
    {
        $request->validate(['motivo_anulacion' => 'nullable|string|max:500']);

        if ($prestamo->estado !== EquipoPrestamo::ESTADO_APROBADO) {
            return response()->json(['error' => 'Solo se pueden anular préstamos aprobados.'], 422);
        }

        $authUser = Auth::user();
        if (!$authUser->hasRole('Administrador')) {
            $nivelCreador = UserAsignacion::where('user_id', $prestamo->creado_por)->vigentes()->get()
                ->max(fn ($a) => UserAsignacion::NIVEL_ROL[$a->rol] ?? 0);
            $nivelActual  = UserAsignacion::where('user_id', $authUser->id)->vigentes()->get()
                ->max(fn ($a) => UserAsignacion::NIVEL_ROL[$a->rol] ?? 0);

            if ($nivelActual <= $nivelCreador) {
                return response()->json(['error' => 'Solo un rango superior al creador puede anular este préstamo.'], 403);
            }
        }

        DB::transaction(function () use ($prestamo, $request) {
            foreach (CarbonPeriod::create($prestamo->fecha_inicio, $prestamo->fecha_fin) as $date) {
                EquipoDia::updateOrCreate(
                    ['empleado_id' => $prestamo->empleado_id, 'fecha' => $date->toDateString()],
                    [
                        'supervisor_id' => $prestamo->supervisor_origen_id,
                        'campana_id'    => $prestamo->campana_origen_id,
                        'origen'        => EquipoDia::ORIGEN_BASE,
                        'prestamo_id'   => null,
                    ]
                );
            }

            $prestamo->update([
                'estado'         => EquipoPrestamo::ESTADO_ANULADO,
                'motivo_rechazo' => $request->motivo_anulacion ?? null,
                'aprobado_por'   => (int) Auth::id(),
                'aprobado_en'    => now(),
            ]);
        });

        return response()->json(['success' => true]);
    }

    // ── Rechazar / Cancelar préstamo ──────────────────────────────────────────

    public function rechazarPrestamo(Request $request, EquipoPrestamo $prestamo): JsonResponse
    {
        $request->validate(['motivo_rechazo' => 'nullable|string|max:500']);

        if ($prestamo->estado !== EquipoPrestamo::ESTADO_PENDIENTE) {
            return response()->json(['error' => 'Este préstamo ya fue procesado.'], 422);
        }

        $prestamo->update([
            'estado'         => EquipoPrestamo::ESTADO_RECHAZADO,
            'aprobado_por'   => (int) Auth::id(),
            'aprobado_en'    => now(),
            'motivo_rechazo' => $request->motivo_rechazo,
        ]);

        return response()->json(['success' => true]);
    }

    public function cancelarPrestamo(EquipoPrestamo $prestamo): JsonResponse
    {
        $userId = (int) Auth::id();

        if ((int) $prestamo->creado_por !== $userId && !Auth::user()->hasRole('Administrador')) {
            return response()->json(['error' => 'Solo puedes cancelar préstamos que hayas creado.'], 403);
        }

        if ($prestamo->estado !== EquipoPrestamo::ESTADO_PENDIENTE) {
            return response()->json(['error' => 'Solo se pueden cancelar préstamos pendientes.'], 422);
        }

        $prestamo->update([
            'estado'         => EquipoPrestamo::ESTADO_RECHAZADO,
            'motivo_rechazo' => 'Cancelado por el solicitante.',
            'aprobado_por'   => $userId,
            'aprobado_en'    => now(),
        ]);

        return response()->json(['success' => true]);
    }

    // ── Búsqueda lazy-load para modal Solicitar ───────────────────────────────

    public function buscarParaSolicitar(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $user = Auth::user();
        if ($user->hasRole('Administrador')) {
            return response()->json([]);
        }

        $hoy         = Carbon::today()->toDateString();
        $fechaInicio = $request->input('fecha_inicio', $hoy);
        $fechaFin    = $request->input('fecha_fin', $hoy);

        $personaIds     = $this->jerarquia->personaIdsVisibles($user);
        $misEmpleadoIds = $personaIds ?? [];
        $terms          = array_values(array_filter(explode(' ', $q), fn ($t) => strlen($t) >= 1));

        $contratos = Contrato::withoutGlobalScope(AlcanceUsuarioScope::class)
            ->with(['persona' => fn ($pq) => $pq->withoutGlobalScope(AlcanceUsuarioScope::class)])
            ->whereNotIn('persona_id', $misEmpleadoIds)
            ->where('inicio_contrato', '<=', $fechaInicio)
            ->where(fn ($q) => $q
                ->whereRaw('COALESCE(fecha_renuncia, fin_contrato) >= ?', [$fechaFin])
                ->orWhereRaw('COALESCE(fecha_renuncia, fin_contrato) IS NULL')
            )
            ->whereHas('persona', fn ($pq) => $pq
                ->withoutGlobalScope(AlcanceUsuarioScope::class)
                ->where(function ($sq) use ($terms) {
                    foreach ($terms as $term) {
                        $sq->where(fn ($tq) => $tq
                            ->where('apellido_paterno', 'like', "%{$term}%")
                            ->orWhere('apellido_materno', 'like', "%{$term}%")
                            ->orWhere('nombres', 'like', "%{$term}%")
                        );
                    }
                })
            )
            ->limit(15)
            ->get();

        $personasMap    = $contratos->map(fn ($c) => $c->persona)->filter()->unique('id')->keyBy('id');
        $docs           = $personasMap->pluck('numero_documento')->filter()->values();
        $usuariosPorDoc = User::whereIn('numero_documento', $docs)
            ->get(['id', 'name', 'numero_documento'])
            ->keyBy('numero_documento');

        $asignacionesPorUser = UserAsignacion::with(['campana', 'superior'])
            ->whereIn('user_id', $usuariosPorDoc->pluck('id')->values())
            ->vigentes()
            ->get()
            ->groupBy('user_id')
            ->map(fn ($g) => $g->sortByDesc(fn ($a) => UserAsignacion::NIVEL_ROL[$a->rol] ?? 0)->first());

        return response()->json(
            $contratos->map(function ($c) use ($personasMap, $usuariosPorDoc, $asignacionesPorUser) {
                $persona = $personasMap->get((int) $c->persona_id);
                if (!$persona) return null;

                $empUser = $usuariosPorDoc->get($persona->numero_documento);
                $asig    = $empUser ? $asignacionesPorUser->get($empUser->id) : null;

                return [
                    'persona_id'        => (int) $c->persona_id,
                    'nombre'            => $persona->nombre_corto,
                    'supervisor_id'     => $asig?->superior_id ? (int) $asig->superior_id : null,
                    'supervisor_nombre' => $asig?->superior?->name ?? '—',
                    'campana'           => $asig?->campana?->nombre ?? '—',
                ];
            })->filter()->values()
        );
    }

    // ── Auto-carry (provisional) ──────────────────────────────────────────────

    public function autoCarry(Request $request): JsonResponse
    {
        $request->validate(['fecha' => 'required|date_format:Y-m-d']);

        Artisan::call('equipo:auto-carry', ['--fecha' => $request->fecha]);

        $output = trim(Artisan::output());

        return response()->json(['message' => $output ?: "Auto-carry ejecutado para {$request->fecha}."]);
    }
}
