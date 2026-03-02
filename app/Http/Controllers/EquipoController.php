<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\EquipoDia;
use App\Models\EquipoPrestamo;
use App\Models\EquipoSolicitud;
use App\Models\Persona;
use App\Models\User;
use App\Models\UserAsignacion;
use App\Services\JerarquiaService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EquipoController extends Controller
{
    public function __construct(protected JerarquiaService $jerarquia) {}

    // ── Vista principal: gestión de préstamos ─────────────────────────────────

    public function index(Request $request)
    {
        $user         = Auth::user();
        $esAdmin      = $user->hasRole('Administrador');
        $puedeAprobar = $user->can('equipos.approve');
        $userId       = (int) $user->id;
        $hoy          = Carbon::today()->toDateString();

        $baseQ = fn () => EquipoPrestamo::with([
            'empleado', 'supervisorOrigen', 'supervisorDestino',
            'campanaOrigen', 'campanaDestino', 'aprobadoPor', 'creadoPor',
        ])->where('tipo', EquipoPrestamo::TIPO_PRESTAMO)
          ->when(!$esAdmin, fn ($q) => $q->where(function ($q2) use ($userId) {
              $q2->where('supervisor_origen_id', $userId)
                 ->orWhere('supervisor_destino_id', $userId)
                 ->orWhere('creado_por', $userId);
          }));

        $activos    = $baseQ()->aprobados()
            ->where('fecha_inicio', '<=', $hoy)
            ->where('fecha_fin', '>=', $hoy)
            ->orderBy('fecha_fin')
            ->get();

        $pendientes = $baseQ()->pendientes()
            ->orderBy('fecha_inicio')
            ->get();

        $proximos   = $baseQ()->aprobados()
            ->where('fecha_inicio', '>', $hoy)
            ->orderBy('fecha_inicio')
            ->get();

        $historial  = $baseQ()->where(function ($q) use ($hoy) {
            $q->where('estado', EquipoPrestamo::ESTADO_RECHAZADO)
              ->orWhere(fn ($q2) => $q2
                  ->where('estado', EquipoPrestamo::ESTADO_APROBADO)
                  ->where('fecha_fin', '<', $hoy));
        })->orderByDesc('updated_at')->limit(30)->get();

        // Modal "Prestar": mis colaboradores vía jerarquía de asignaciones
        $personaIds = $this->jerarquia->personaIdsVisibles($user);

        $misColaboradores = ($personaIds !== null && !empty($personaIds))
            ? Persona::whereIn('id', $personaIds)
                ->orderBy('apellido_paterno')
                ->orderBy('nombres')
                ->get()
            : collect();

        $misEmpleadoIds = $misColaboradores->pluck('id')->map(fn ($id) => (int) $id)->toArray();

        // Modal "Solicitar": personas con contrato activo excluidas las propias
        $personasActivas = Contrato::with('persona')
            ->whereNotIn('persona_id', $misEmpleadoIds)
            ->where('inicio_contrato', '<=', $hoy)
            ->whereRaw('COALESCE(fecha_renuncia, fin_contrato) > ?', [$hoy])
            ->get()
            ->map(fn ($c) => [
                'persona_id' => (int) $c->persona_id,
                'nombre'     => $c->persona?->nombre_corto ?? null,
            ])
            ->filter(fn ($p) => $p['nombre'])
            ->unique('persona_id')
            ->sortBy('nombre')
            ->values();

        // Todos los usuarios con rol operativo (Supervisor/Coord/JO) excepto yo
        $supervisores = User::where('activo', true)
            ->whereHas('asignaciones', fn ($q) => $q
                ->where('estado', UserAsignacion::ESTADO_APROBADO)
                ->whereIn('rol', [
                    UserAsignacion::ROL_SUPERVISOR,
                    UserAsignacion::ROL_COORDINADOR,
                    UserAsignacion::ROL_JEFE_OPERACIONES,
                ])
            )
            ->where('id', '!=', $userId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $totalPendientes = $puedeAprobar
            ? EquipoPrestamo::pendientes()->where('tipo', EquipoPrestamo::TIPO_PRESTAMO)->count()
            : 0;

        return view('equipos.index', compact(
            'activos', 'pendientes', 'proximos', 'historial',
            'misColaboradores', 'personasActivas', 'supervisores',
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
        $accion     = $request->accion;

        if ($accion === 'prestar') {
            $supervisorOrigenId  = $userId;
            $supervisorDestinoId = (int) $request->supervisor_destino_id;

            // El colaborador debe estar en mi equipo vía jerarquía
            $visibles = $this->jerarquia->personaIdsVisibles($user);
            $enMiEquipo = $visibles === null || in_array($empleadoId, $visibles);

            if (!$enMiEquipo) {
                return response()->json(['error' => 'Este colaborador no está en tu equipo.'], 422);
            }
        } else {
            // solicitar: yo soy el destino
            $supervisorDestinoId = $userId;

            // supervisor_origen: enviado o auto-detectado vía jerarquía de asignaciones
            $supervisorOrigenId = $request->filled('supervisor_origen_id')
                ? (int) $request->supervisor_origen_id
                : null;

            if (!$supervisorOrigenId) {
                $persona = Persona::find($empleadoId);
                if ($persona && $persona->numero_documento) {
                    $empUser = User::where('numero_documento', $persona->numero_documento)->first();
                    if ($empUser) {
                        $asig = UserAsignacion::where('user_id', $empUser->id)
                            ->where('estado', UserAsignacion::ESTADO_APROBADO)
                            ->whereNull('fecha_fin')
                            ->first();
                        $supervisorOrigenId = $asig?->superior_id ? (int) $asig->superior_id : null;
                    }
                }
            }

            if (!$supervisorOrigenId || $supervisorOrigenId === $userId) {
                return response()->json(['error' => 'No se pudo determinar el supervisor actual del colaborador.'], 422);
            }
        }

        if (EquipoPrestamo::tieneSolapamiento($empleadoId, $request->fecha_inicio, $request->fecha_fin)) {
            return response()->json(['error' => 'Ya existe un préstamo activo para este colaborador en ese rango de fechas.'], 422);
        }

        $campanaOrigenId  = $this->campanaDelSupervisor($supervisorOrigenId);
        $campanaDestinoId = $this->campanaDelSupervisor($supervisorDestinoId);

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

    // ── Aprobar préstamo → aplica en equipo_dia para todo el rango ───────────

    public function aprobarPrestamo(EquipoPrestamo $prestamo): JsonResponse
    {
        if ($prestamo->estado !== EquipoPrestamo::ESTADO_PENDIENTE) {
            return response()->json(['error' => 'Este préstamo ya fue procesado.'], 422);
        }

        DB::transaction(function () use ($prestamo) {
            $prestamo->update([
                'estado'      => EquipoPrestamo::ESTADO_APROBADO,
                'aprobado_por' => (int) Auth::id(),
                'aprobado_en'  => now(),
            ]);

            // Aplicar en equipo_dia para cada día del rango
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

    // ── Rechazar préstamo ─────────────────────────────────────────────────────

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

    // ── Cancelar préstamo pendiente (solo el creador) ─────────────────────────

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

    // Agregar un huérfano directamente al equipo (sin aprobación)
    public function agregar(Request $request): JsonResponse
    {
        $request->validate([
            'fecha'       => 'required|date',
            'contrato_id' => 'required|integer',
        ]);

        $contrato = Contrato::find($request->contrato_id);
        if (!$contrato) {
            return response()->json(['error' => 'Contrato no encontrado.'], 404);
        }

        $user      = Auth::user();
        $fecha     = $request->fecha;
        $personaId = (int) $contrato->persona_id;

        $existente = EquipoDia::where('fecha', $fecha)
            ->where('empleado_id', $personaId)
            ->first();

        if ($existente) {
            if ((int) $existente->supervisor_id === (int) $user->id) {
                return response()->json(['error' => 'Este colaborador ya está en tu equipo ese día.'], 422);
            }
            return response()->json([
                'error'              => 'Este colaborador ya pertenece al equipo de otro supervisor ese día.',
                'requiere_solicitud' => true,
            ], 422);
        }

        // Obtener campaña vigente del supervisor
        $campanaId = $this->campanaDelSupervisor((int) $user->id);
        if (!$campanaId) {
            return response()->json(['error' => 'No tienes campaña asignada vigente.'], 403);
        }

        $asignacion = EquipoDia::create([
            'fecha'         => $fecha,
            'supervisor_id' => (int) $user->id,
            'empleado_id'   => $personaId,
            'campana_id'    => $campanaId,
            'origen'        => EquipoDia::ORIGEN_BASE,
        ]);

        return response()->json(['success' => true, 'id_asignacion' => $asignacion->id]);
    }

    // Retirar a alguien del equipo (queda huérfano)
    public function retirar(EquipoDia $asignacion): JsonResponse
    {
        if ((int) $asignacion->supervisor_id !== (int) Auth::id()) {
            return response()->json(['error' => 'No puedes retirar a alguien de un equipo ajeno.'], 403);
        }

        $asignacion->delete();

        return response()->json(['success' => true]);
    }

    // Solicitar a alguien que está en otro equipo (genera solicitud pendiente)
    public function solicitar(Request $request): JsonResponse
    {
        $request->validate([
            'fecha'       => 'required|date',
            'contrato_id' => 'required|integer',
        ]);

        $contrato = Contrato::find($request->contrato_id);
        if (!$contrato) {
            return response()->json(['error' => 'Contrato no encontrado.'], 404);
        }

        $user      = Auth::user();
        $fecha     = $request->fecha;
        $personaId = (int) $contrato->persona_id;

        $existente = EquipoDia::where('fecha', $fecha)
            ->where('empleado_id', $personaId)
            ->first();

        if ($existente && (int) $existente->supervisor_id === (int) $user->id) {
            return response()->json(['error' => 'Este colaborador ya está en tu equipo.'], 422);
        }

        $solicitudExistente = EquipoSolicitud::where('fecha', $fecha)
            ->where('contrato_id', $request->contrato_id)
            ->where('user_id', (int) $user->id)
            ->where('estado', EquipoSolicitud::PENDIENTE)
            ->exists();

        if ($solicitudExistente) {
            return response()->json(['error' => 'Ya tienes una solicitud pendiente para este colaborador en esa fecha.'], 422);
        }

        $prevSupervisorId = $existente?->supervisor_id;

        EquipoSolicitud::create([
            'fecha'          => $fecha,
            'user_id'        => (int) $user->id,
            'contrato_id'    => $request->contrato_id,
            'estado'         => EquipoSolicitud::PENDIENTE,
            'solicitado_por' => (int) $user->id,
            'prev_user_id'   => $prevSupervisorId,
        ]);

        return response()->json(['success' => true]);
    }

    // Cola de aprobación: solicitudes pendientes + huérfanos del día
    public function pendientes(Request $request)
    {
        $fecha = $request->filled('fecha')
            ? Carbon::parse($request->fecha)->toDateString()
            : null;

        $qSolicitudes = EquipoSolicitud::with(['contrato.persona', 'supervisor', 'prevSupervisor'])
            ->pendientes()
            ->orderBy('fecha')
            ->orderBy('created_at');

        $qHistorial = EquipoSolicitud::with(['contrato.persona', 'supervisor', 'prevSupervisor', 'aprobadoPor'])
            ->whereIn('estado', [EquipoSolicitud::APROBADO, EquipoSolicitud::RECHAZADO])
            ->orderByDesc('updated_at');

        if ($fecha) {
            $qSolicitudes->where('fecha', $fecha);
            $qHistorial->where('fecha', $fecha);
        }

        $solicitudes  = $qSolicitudes->get();
        $historialAll = $qHistorial->get();

        $historialPage = (int) $request->input('historial_page', 1);
        $historial = new \Illuminate\Pagination\LengthAwarePaginator(
            $historialAll->forPage($historialPage, 10),
            $historialAll->count(),
            10,
            $historialPage,
            ['pageName' => 'historial_page', 'path' => $request->url(), 'query' => $request->except('historial_page')]
        );

        $fechaHuerfanos = $fecha ?? Carbon::today()->toDateString();
        $huerfanosAll   = $this->calcularHuerfanos($fechaHuerfanos)
            ->sortBy(fn ($c) => ($c->persona->apellido_paterno ?? '') . ' ' . ($c->persona->nombres ?? ''))
            ->values();

        $huerfanosPage = (int) $request->input('huerfanos_page', 1);
        $huerfanos = new \Illuminate\Pagination\LengthAwarePaginator(
            $huerfanosAll->forPage($huerfanosPage, 10),
            $huerfanosAll->count(),
            10,
            $huerfanosPage,
            ['pageName' => 'huerfanos_page', 'path' => $request->url(), 'query' => $request->except('huerfanos_page')]
        );

        return view('equipos.pendientes', compact('solicitudes', 'historial', 'huerfanos', 'fecha'));
    }

    // Aprobar solicitud → crea/reemplaza asignación en equipo_dia
    public function aprobar(EquipoSolicitud $solicitud): JsonResponse
    {
        if ($solicitud->estado !== EquipoSolicitud::PENDIENTE) {
            return response()->json(['error' => 'Esta solicitud ya fue procesada.'], 422);
        }

        $contrato = Contrato::find($solicitud->contrato_id);
        if (!$contrato) {
            return response()->json(['error' => 'Contrato no encontrado.'], 404);
        }

        $personaId = (int) $contrato->persona_id;

        // Campaña del supervisor solicitante
        $campanaId = $this->campanaDelSupervisor((int) $solicitud->user_id);
        if (!$campanaId) {
            // Fallback: campaña del supervisor actual (prev_user_id)
            $campanaId = EquipoDia::where('fecha', $solicitud->fecha)
                ->where('empleado_id', $personaId)
                ->value('campana_id');
        }

        // Reemplazar asignación existente
        EquipoDia::where('fecha', $solicitud->fecha)
            ->where('empleado_id', $personaId)
            ->delete();

        EquipoDia::create([
            'fecha'         => $solicitud->fecha,
            'supervisor_id' => (int) $solicitud->user_id,
            'empleado_id'   => $personaId,
            'campana_id'    => (int) $campanaId,
            'origen'        => EquipoDia::ORIGEN_BASE,
        ]);

        $solicitud->update([
            'estado'      => EquipoSolicitud::APROBADO,
            'aprobado_por' => (int) Auth::id(),
        ]);

        // Auto-rechazar otras solicitudes pendientes para el mismo (contrato, fecha)
        EquipoSolicitud::where('fecha', $solicitud->fecha)
            ->where('contrato_id', $solicitud->contrato_id)
            ->where('id', '!=', $solicitud->id)
            ->where('estado', EquipoSolicitud::PENDIENTE)
            ->update([
                'estado'         => EquipoSolicitud::RECHAZADO,
                'aprobado_por'   => (int) Auth::id(),
                'motivo_rechazo' => 'Se aprobó solicitud de otro supervisor.',
            ]);

        return response()->json(['success' => true]);
    }

    // Rechazar solicitud
    public function rechazar(Request $request, EquipoSolicitud $solicitud): JsonResponse
    {
        $request->validate([
            'motivo_rechazo' => 'nullable|string|max:255',
        ]);

        if ($solicitud->estado !== EquipoSolicitud::PENDIENTE) {
            return response()->json(['error' => 'Esta solicitud ya fue procesada.'], 422);
        }

        $solicitud->update([
            'estado'         => EquipoSolicitud::RECHAZADO,
            'aprobado_por'   => (int) Auth::id(),
            'motivo_rechazo' => $request->motivo_rechazo,
        ]);

        return response()->json(['success' => true]);
    }

    // Replicar equipo de una fecha a otra (solo libres)
    public function replicar(Request $request): JsonResponse
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|different:desde',
        ]);

        $user  = Auth::user();
        $desde = Carbon::parse($request->desde)->toDateString();
        $hasta = Carbon::parse($request->hasta)->toDateString();

        $equipoOrigen = EquipoDia::where('supervisor_id', (int) $user->id)
            ->where('fecha', $desde)
            ->get(['empleado_id', 'campana_id']);

        if ($equipoOrigen->isEmpty()) {
            return response()->json(['error' => 'No tienes equipo asignado en la fecha origen.'], 422);
        }

        $ocupadosDestino = EquipoDia::where('fecha', $hasta)
            ->pluck('empleado_id')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $copiados = 0;
        $omitidos = [];

        foreach ($equipoOrigen as $item) {
            $empleadoId = (int) $item->empleado_id;

            if (in_array($empleadoId, $ocupadosDestino)) {
                $persona   = Persona::find($empleadoId);
                $omitidos[] = $persona?->nombre_corto ?? "Empleado #{$empleadoId}";
                continue;
            }

            EquipoDia::firstOrCreate(
                ['fecha' => $hasta, 'empleado_id' => $empleadoId],
                [
                    'supervisor_id' => (int) $user->id,
                    'campana_id'    => (int) $item->campana_id,
                    'origen'        => EquipoDia::ORIGEN_BASE,
                ]
            );
            $copiados++;
        }

        return response()->json([
            'success'  => true,
            'copiados' => $copiados,
            'omitidos' => $omitidos,
        ]);
    }

    // Auto-carry manual (vía HTTP — el comando artisan hace lo mismo más completo)
    public function autoCarry(Request $request): JsonResponse
    {
        $desde = $request->filled('fecha')
            ? Carbon::parse($request->fecha)->toDateString()
            : Carbon::today()->toDateString();
        $hasta = Carbon::parse($desde)->addDay()->toDateString();

        $yaAsignados = EquipoDia::where('fecha', $hasta)->pluck('empleado_id')->toArray();

        $asignacionesHoy = EquipoDia::where('fecha', $desde)
            ->whereNotIn('empleado_id', $yaAsignados)
            ->get();

        $count = 0;
        foreach ($asignacionesHoy as $asignacion) {
            EquipoDia::create([
                'fecha'         => $hasta,
                'supervisor_id' => $asignacion->supervisor_id,
                'empleado_id'   => $asignacion->empleado_id,
                'campana_id'    => $asignacion->campana_id,
                'origen'        => $asignacion->origen,
            ]);
            $count++;
        }

        return response()->json(['success' => true, 'creados' => $count]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Adjunta la relación 'contrato' (contrato activo en $fecha para esa persona)
     * a cada registro de EquipoDia, para que la vista pueda hacer $ed->contrato->persona.
     */
    private function attachContratos(\Illuminate\Support\Collection $equipoDia, string $fecha): \Illuminate\Support\Collection
    {
        $personaIds = $equipoDia->pluck('empleado_id')->unique()->filter()->values();
        if ($personaIds->isEmpty()) {
            return $equipoDia;
        }

        $contratos = Contrato::with(['persona', 'condicion', 'cargo', 'planilla', 'familia'])
            ->whereIn('persona_id', $personaIds)
            ->where('inicio_contrato', '<=', $fecha)
            ->whereRaw('COALESCE(fecha_renuncia, fin_contrato) > ?', [$fecha])
            ->get()
            ->keyBy('persona_id');

        return $equipoDia->map(function ($ed) use ($contratos) {
            $ed->setAttribute('contrato', $contratos->get((int) $ed->empleado_id));
            return $ed;
        });
    }

    /**
     * Calcula huérfanos: personas con contrato activo ese día sin equipo_dia asignado.
     */
    private function calcularHuerfanos(string $fecha): \Illuminate\Support\Collection
    {
        $asignadosEmpleadoIds = EquipoDia::where('fecha', $fecha)->pluck('empleado_id');

        // Personas con solicitud pendiente (tienen contrato_id → resolvemos persona_id)
        $solicitudContratoIds  = EquipoSolicitud::where('fecha', $fecha)->pendientes()->pluck('contrato_id');
        $solicitudPersonaIds   = Contrato::whereIn('id', $solicitudContratoIds)->pluck('persona_id');

        $excluidos = $asignadosEmpleadoIds->merge($solicitudPersonaIds)->unique();

        return Contrato::with(['persona', 'condicion', 'familia'])
            ->whereNotIn('persona_id', $excluidos)
            ->where('inicio_contrato', '<=', $fecha)
            ->where(function ($q) use ($fecha) {
                $q->where(function ($q2) {
                    $q2->whereNull('fin_contrato')->whereNull('fecha_renuncia');
                })->orWhereRaw("
                    CASE
                        WHEN fecha_renuncia IS NOT NULL THEN fecha_renuncia
                        ELSE fin_contrato
                    END > ?
                ", [$fecha]);
            })
            ->get();
    }

    /**
     * Devuelve la campana_id vigente del supervisor (primera aprobada + activa).
     */
    private function campanaDelSupervisor(int $userId): ?int
    {
        return (int) UserAsignacion::where('user_id', $userId)
            ->where('estado', UserAsignacion::ESTADO_APROBADO)
            ->whereNull('fecha_fin')
            ->value('campana_id') ?: null;
    }
}
