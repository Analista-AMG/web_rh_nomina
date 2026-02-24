<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\EquipoDia;
use App\Models\EquipoSolicitud;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EquipoController extends Controller
{
    // Vista principal: el supervisor ve su equipo del día
    public function index(Request $request)
    {
        $fecha = $request->filled('fecha')
            ? Carbon::parse($request->fecha)->toDateString()
            : Carbon::today()->toDateString();

        $user = Auth::user();

        $with = ['contrato.persona', 'contrato.planilla', 'contrato.condicion', 'contrato.cargo', 'contrato.familia'];

        $equipo = EquipoDia::with($with)
            ->where('user_id', $user->id)
            ->where('fecha', $fecha)
            ->get();

        // Lazy carry: si se consulta hoy y no hay equipo, copiar del día anterior
        if ($equipo->isEmpty() && $fecha === Carbon::today()->toDateString()) {
            $ayer = Carbon::yesterday()->toDateString();
            $equipoAyer = EquipoDia::where('user_id', $user->id)
                ->where('fecha', $ayer)
                ->get();

            foreach ($equipoAyer as $asignacion) {
                EquipoDia::firstOrCreate(
                    ['fecha' => $fecha, 'id_contrato' => (int)$asignacion->id_contrato],
                    ['user_id' => $user->id]
                );
            }

            // Recargar con los datos recién copiados
            $equipo = EquipoDia::with($with)
                ->where('user_id', $user->id)
                ->where('fecha', $fecha)
                ->get();
        }

        // Mis solicitudes pendientes ese día (yo pedí, espero aprobación)
        $solicitudesPendientes = EquipoSolicitud::with(['contrato.persona', 'prevSupervisor'])
            ->where('user_id', $user->id)
            ->where('fecha', $fecha)
            ->pendientes()
            ->get();

        // Mis solicitudes procesadas ese día (yo pedí → aprobadas o rechazadas)
        $solicitudesProcesadas = EquipoSolicitud::with(['contrato.persona', 'aprobadoPor'])
            ->where('solicitado_por', $user->id)
            ->where('fecha', $fecha)
            ->whereIn('estado', [EquipoSolicitud::APROBADO, EquipoSolicitud::RECHAZADO])
            ->orderByDesc('updated_at')
            ->get();

        // Alertas: alguien está intentando quitarme una persona (pendiente de aprobación)
        $alertasEquipo = EquipoSolicitud::with(['contrato.persona', 'supervisor'])
            ->where('prev_user_id', $user->id)
            ->where('fecha', $fecha)
            ->pendientes()
            ->orderBy('created_at')
            ->get();

        // Movimientos de mi equipo: alguien solicitó a una persona mía y fue procesado
        $movimientosEquipo = EquipoSolicitud::with(['contrato.persona', 'supervisor', 'aprobadoPor'])
            ->where('prev_user_id', $user->id)
            ->where('fecha', $fecha)
            ->whereIn('estado', [EquipoSolicitud::APROBADO, EquipoSolicitud::RECHAZADO])
            ->orderByDesc('updated_at')
            ->get();

        // Badge para el aprobador: total pendientes sin importar fecha
        $totalPendientesAprobacion = 0;
        if ($user->can('equipos.approve')) {
            $totalPendientesAprobacion = EquipoSolicitud::pendientes()->count();
        }

        // IDs ya en mi equipo ese día
        $misIds = $equipo->pluck('id_contrato')->map(fn($v) => (int)$v);

        // Huérfanos: activos ese día sin ningún equipo asignado
        $huerfanos = $this->calcularHuerfanos($fecha)->reject(fn($c) => $misIds->contains((int)$c->id_contrato));

        // En otros equipos: asignados a otro supervisor ese día
        $enOtrosEquipos = EquipoDia::with(['contrato.persona', 'contrato.planilla', 'supervisor'])
            ->where('fecha', $fecha)
            ->where('user_id', '!=', $user->id)
            ->get();

        return view('equipos.index', compact(
            'fecha',
            'equipo',
            'solicitudesPendientes',
            'solicitudesProcesadas',
            'alertasEquipo',
            'movimientosEquipo',
            'totalPendientesAprobacion',
            'huerfanos',
            'enOtrosEquipos'
        ));
    }

    // Agregar un huérfano directamente al equipo (sin aprobación)
    public function agregar(Request $request): JsonResponse
    {
        $request->validate([
            'fecha'       => 'required|date',
            'id_contrato' => 'required|integer',
        ]);

        if (!Contrato::where('id_contrato', $request->id_contrato)->exists()) {
            return response()->json(['error' => 'Contrato no encontrado.'], 404);
        }

        $user = Auth::user();
        $fecha = $request->fecha;

        $asignacionExistente = EquipoDia::where('fecha', $fecha)
            ->where('id_contrato', $request->id_contrato)
            ->first();

        if ($asignacionExistente) {
            if ($asignacionExistente->user_id === $user->id) {
                return response()->json(['error' => 'Este colaborador ya está en tu equipo ese día.'], 422);
            }
            return response()->json([
                'error'              => 'Este colaborador ya pertenece al equipo de otro supervisor ese día.',
                'requiere_solicitud' => true,
            ], 422);
        }

        $asignacion = EquipoDia::create([
            'fecha'       => $fecha,
            'user_id'     => $user->id,
            'id_contrato' => $request->id_contrato,
        ]);

        return response()->json(['success' => true, 'id_asignacion' => $asignacion->id_asignacion]);
    }

    // Retirar a alguien del equipo (queda huérfano)
    public function retirar(EquipoDia $asignacion): JsonResponse
    {
        if ((int) $asignacion->user_id !== (int) Auth::id()) {
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
            'id_contrato' => 'required|integer',
        ]);

        $user = Auth::user();
        $fecha = $request->fecha;

        $asignacionExistente = EquipoDia::where('fecha', $fecha)
            ->where('id_contrato', $request->id_contrato)
            ->first();

        if ($asignacionExistente && $asignacionExistente->user_id === $user->id) {
            return response()->json(['error' => 'Este colaborador ya está en tu equipo.'], 422);
        }

        $solicitudExistente = EquipoSolicitud::where('fecha', $fecha)
            ->where('id_contrato', $request->id_contrato)
            ->where('user_id', $user->id)
            ->where('estado', EquipoSolicitud::PENDIENTE)
            ->exists();

        if ($solicitudExistente) {
            return response()->json(['error' => 'Ya tienes una solicitud pendiente para este colaborador en esa fecha.'], 422);
        }

        // Capturar quién tiene actualmente la persona (para notificarle después)
        $propietarioActual = EquipoDia::where('fecha', $fecha)
            ->where('id_contrato', $request->id_contrato)
            ->value('user_id');

        EquipoSolicitud::create([
            'fecha'          => $fecha,
            'user_id'        => $user->id,
            'id_contrato'    => $request->id_contrato,
            'estado'         => EquipoSolicitud::PENDIENTE,
            'solicitado_por' => $user->id,
            'prev_user_id'   => $propietarioActual,
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

        $qHistorial = EquipoSolicitud::with(['contrato.persona', 'supervisor', 'aprobadoPor'])
            ->whereIn('estado', [EquipoSolicitud::APROBADO, EquipoSolicitud::RECHAZADO])
            ->orderByDesc('updated_at');

        if ($fecha) {
            $qSolicitudes->where('fecha', $fecha);
            $qHistorial->where('fecha', $fecha);
        }

        $solicitudes = $qSolicitudes->get();

        $historialAll = $qHistorial->get();
        $historialPage = (int) $request->input('historial_page', 1);
        $historial = new \Illuminate\Pagination\LengthAwarePaginator(
            $historialAll->forPage($historialPage, 10),
            $historialAll->count(),
            10,
            $historialPage,
            ['pageName' => 'historial_page', 'path' => $request->url(), 'query' => $request->except('historial_page')]
        );

        $huerfanosAll = $this->calcularHuerfanos($fecha ?? Carbon::today()->toDateString())
            ->sortBy(fn($c) => ($c->persona->apellido_paterno ?? '') . ' ' . ($c->persona->apellido_materno ?? '') . ' ' . ($c->persona->nombres ?? ''))
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

    // Aprobar solicitud → crea/reemplaza asignación en fact_equipo_dia
    public function aprobar(EquipoSolicitud $solicitud): JsonResponse
    {
        if ($solicitud->estado !== EquipoSolicitud::PENDIENTE) {
            return response()->json(['error' => 'Esta solicitud ya fue procesada.'], 422);
        }

        // Eliminar asignación previa si existía (de otro equipo)
        EquipoDia::where('fecha', $solicitud->fecha)
            ->where('id_contrato', $solicitud->id_contrato)
            ->delete();

        EquipoDia::create([
            'fecha'       => $solicitud->fecha,
            'user_id'     => $solicitud->user_id,
            'id_contrato' => $solicitud->id_contrato,
        ]);

        $solicitud->update([
            'estado'      => EquipoSolicitud::APROBADO,
            'aprobado_por' => Auth::id(),
        ]);

        // Rechazar automáticamente otras solicitudes pendientes para el mismo contrato/fecha
        EquipoSolicitud::where('fecha', $solicitud->fecha)
            ->where('id_contrato', $solicitud->id_contrato)
            ->where('id_solicitud', '!=', $solicitud->id_solicitud)
            ->where('estado', EquipoSolicitud::PENDIENTE)
            ->update([
                'estado'         => EquipoSolicitud::RECHAZADO,
                'aprobado_por'   => Auth::id(),
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
            'aprobado_por'   => Auth::id(),
            'motivo_rechazo' => $request->motivo_rechazo,
        ]);

        return response()->json(['success' => true]);
    }

    // Auto-carry: copia equipo de 'fecha' a 'fecha+1' (llamado por comando artisan)
    public function autoCarry(Request $request): JsonResponse
    {
        $desde = $request->filled('fecha')
            ? Carbon::parse($request->fecha)->toDateString()
            : Carbon::today()->toDateString();
        $hasta = Carbon::parse($desde)->addDay()->toDateString();

        $yaAsignados = EquipoDia::where('fecha', $hasta)->pluck('id_contrato')->toArray();

        $asignacionesHoy = EquipoDia::where('fecha', $desde)
            ->whereNotIn('id_contrato', $yaAsignados)
            ->get();

        $count = 0;
        foreach ($asignacionesHoy as $asignacion) {
            EquipoDia::create([
                'fecha'       => $hasta,
                'user_id'     => $asignacion->user_id,
                'id_contrato' => $asignacion->id_contrato,
            ]);
            $count++;
        }

        return response()->json(['success' => true, 'creados' => $count]);
    }

    // Calcula huérfanos: contratos activos ese día sin equipo asignado ni solicitud pendiente
    private function calcularHuerfanos(string $fecha): \Illuminate\Support\Collection
    {
        $asignadosIds     = EquipoDia::where('fecha', $fecha)->pluck('id_contrato');
        $conSolicitudIds  = EquipoSolicitud::where('fecha', $fecha)->pendientes()->pluck('id_contrato');
        $excluidos        = $asignadosIds->merge($conSolicitudIds)->unique();

        return Contrato::with(['persona', 'condicion', 'familia'])
            ->whereNotIn('id_contrato', $excluidos)
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
}
