<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campana;
use App\Models\User;
use App\Models\UserAsignacion;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AsignacionController extends Controller
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function esAdmin(): bool
    {
        return auth()->user()->hasRole('Administrador');
    }

    private function miNivelMax(): int
    {
        if ($this->esAdmin()) return 99;

        $max = UserAsignacion::where('user_id', (int) auth()->id())
            ->vigentes()
            ->get()
            ->max(fn($a) => UserAsignacion::NIVEL_ROL[$a->rol] ?? 0);

        return (int) $max;
    }

    private function campanaIdsPermitidos(): ?array
    {
        if ($this->esAdmin()) return null;

        $asignaciones = UserAsignacion::where('user_id', (int) auth()->id())
            ->whereIn('rol', [UserAsignacion::ROL_COORDINADOR, UserAsignacion::ROL_JEFE_OPERACIONES])
            ->vigentes()
            ->get();

        if ($asignaciones->isEmpty()) return [];

        $ids = [];
        foreach ($asignaciones as $a) {
            $campana = Campana::with('subcampanas')->find($a->campana_id);
            if ($campana) {
                $ids = array_merge($ids, $campana->todosLosIds());
            }
        }

        return array_unique($ids);
    }

    private function puedeAprobarRol(string $rol): bool
    {
        return $this->miNivelMax() > (UserAsignacion::NIVEL_ROL[$rol] ?? 99);
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function index()
    {
        $permitidos       = $this->campanaIdsPermitidos();
        $mostrarCerradas  = request()->boolean('cerradas');

        if ($permitidos !== null && empty($permitidos)) {
            abort(403, 'No tienes campañas asignadas para gestionar asignaciones.');
        }

        $campanasQuery = Campana::withCount('subcampanas')->orderBy('nombre');
        if ($permitidos !== null) {
            $campanasQuery->whereIn('id', $permitidos);
        }
        $campanas = $campanasQuery->get(['id', 'nombre']);

        $query = UserAsignacion::with(['usuario', 'campana', 'superior'])
            ->when(!$mostrarCerradas, fn ($q) => $q->whereNull('fecha_fin'))
            ->when($mostrarCerradas,  fn ($q) => $q->whereNotNull('fecha_fin'))
            ->orderByRaw("CASE estado WHEN 'pendiente' THEN 1 WHEN 'aprobado' THEN 2 ELSE 3 END")
            ->orderBy('created_at', 'desc');

        if ($permitidos !== null) {
            $query->whereIn('campana_id', $permitidos);
        }

        $asignaciones = $query->get();

        return view('admin.asignaciones.index', [
            'asignaciones'    => $asignaciones,
            'campanas'        => $campanas,
            'esAdmin'         => $this->esAdmin(),
            'miNivelMax'      => $this->miNivelMax(),
            'mostrarCerradas' => $mostrarCerradas,
        ]);
    }

    public function store(Request $request)
    {
        $permitidos = $this->campanaIdsPermitidos();
        if ($permitidos !== null && empty($permitidos)) abort(403);

        $request->validate([
            'user_id'      => 'required|integer',
            'campana_id'   => 'required|integer',
            'rol'          => 'required|in:' . implode(',', UserAsignacion::ROLES),
            'superior_id'  => 'nullable|integer',
            'fecha_inicio' => 'required|date',
        ]);

        $campanaId = (int) $request->campana_id;

        if ($permitidos !== null && !in_array($campanaId, $permitidos)) {
            abort(403);
        }

        User::findOrFail($request->user_id);

        $existente = UserAsignacion::where('user_id', (int) $request->user_id)
            ->where('campana_id', $campanaId)
            ->whereNull('fecha_fin')
            ->first();

        if ($existente) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario ya tiene una asignación activa en esta campaña.',
            ], 422);
        }

        if ($request->superior_id) {
            $superior = UserAsignacion::where('user_id', (int) $request->superior_id)
                ->where('campana_id', $campanaId)
                ->vigentes()
                ->first();

            if (!$superior) {
                return response()->json([
                    'success' => false,
                    'message' => 'El superior no tiene asignación vigente en esta campaña.',
                ], 422);
            }

            if ((UserAsignacion::NIVEL_ROL[$superior->rol] ?? 0) <= (UserAsignacion::NIVEL_ROL[$request->rol] ?? 0)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El superior debe tener un rol de mayor jerarquía.',
                ], 422);
            }
        }

        $autoAprobar = $this->puedeAprobarRol($request->rol);

        UserAsignacion::create([
            'user_id'      => (int) $request->user_id,
            'campana_id'   => $campanaId,
            'rol'          => $request->rol,
            'superior_id'  => $request->superior_id ? (int) $request->superior_id : null,
            'estado'       => $autoAprobar ? UserAsignacion::ESTADO_APROBADO : UserAsignacion::ESTADO_PENDIENTE,
            'aprobado_por' => $autoAprobar ? (int) auth()->id() : null,
            'aprobado_en'  => $autoAprobar ? now() : null,
            'activo'       => true,
            'fecha_inicio' => $request->fecha_inicio,
            'creado_por'   => (int) auth()->id(),
        ]);

        $msg = $autoAprobar
            ? 'Asignación creada y aprobada.'
            : 'Solicitud creada. Pendiente de aprobación.';

        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function aprobar($id)
    {
        $a = UserAsignacion::findOrFail($id);

        abort_unless($a->estado === UserAsignacion::ESTADO_PENDIENTE, 422, 'Solo se pueden aprobar solicitudes pendientes.');
        abort_unless($this->puedeAprobarRol($a->rol), 403, 'Sin autoridad para aprobar este rol.');

        $permitidos = $this->campanaIdsPermitidos();
        if ($permitidos !== null) {
            abort_unless(in_array((int) $a->campana_id, $permitidos), 403);
        }

        $a->update([
            'estado'         => UserAsignacion::ESTADO_APROBADO,
            'aprobado_por'   => (int) auth()->id(),
            'aprobado_en'    => now(),
            'motivo_rechazo' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Asignación aprobada.']);
    }

    public function rechazar(Request $request, $id)
    {
        $a = UserAsignacion::findOrFail($id);

        abort_unless($a->estado === UserAsignacion::ESTADO_PENDIENTE, 422, 'Solo se pueden rechazar solicitudes pendientes.');
        abort_unless($this->puedeAprobarRol($a->rol), 403, 'Sin autoridad para rechazar este rol.');

        $permitidos = $this->campanaIdsPermitidos();
        if ($permitidos !== null) {
            abort_unless(in_array((int) $a->campana_id, $permitidos), 403);
        }

        $request->validate(['motivo' => 'required|string|max:500']);

        $a->update([
            'estado'         => UserAsignacion::ESTADO_RECHAZADO,
            'motivo_rechazo' => $request->motivo,
            'aprobado_por'   => (int) auth()->id(),
            'aprobado_en'    => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Asignación rechazada.']);
    }

    public function pausar($id)
    {
        $a = UserAsignacion::findOrFail($id);

        abort_unless($a->estado === UserAsignacion::ESTADO_APROBADO, 422, 'Solo se pueden pausar asignaciones aprobadas.');
        abort_unless($this->puedeAprobarRol($a->rol), 403, 'Sin autoridad para esta acción.');

        $permitidos = $this->campanaIdsPermitidos();
        if ($permitidos !== null) {
            abort_unless(in_array((int) $a->campana_id, $permitidos), 403);
        }

        $a->update(['activo' => !$a->activo]);
        $estado = $a->activo ? 'reactivada' : 'pausada';

        return response()->json(['success' => true, 'message' => "Asignación {$estado}.", 'activo' => $a->activo]);
    }

    public function cerrar($id)
    {
        $a = UserAsignacion::findOrFail($id);

        abort_if($a->fecha_fin !== null, 422, 'La asignación ya está cerrada.');
        abort_unless($this->puedeAprobarRol($a->rol), 403, 'Sin autoridad para esta acción.');

        $permitidos = $this->campanaIdsPermitidos();
        if ($permitidos !== null) {
            abort_unless(in_array((int) $a->campana_id, $permitidos), 403);
        }

        $a->update(['fecha_fin' => now()->toDateString(), 'activo' => false]);

        return response()->json(['success' => true, 'message' => 'Asignación cerrada definitivamente.']);
    }

    public function transferir(Request $request, $id)
    {
        $a = UserAsignacion::findOrFail($id);

        abort_if($a->fecha_fin !== null, 422, 'La asignación ya está cerrada.');
        abort_unless($a->estado === UserAsignacion::ESTADO_APROBADO, 422, 'Solo se pueden transferir asignaciones aprobadas.');
        abort_unless($this->puedeAprobarRol($a->rol), 403, 'Sin autoridad para transferir este rol.');

        $permitidos = $this->campanaIdsPermitidos();
        if ($permitidos !== null) {
            abort_unless(in_array((int) $a->campana_id, $permitidos), 403);
        }

        $request->validate([
            'superior_id'  => 'nullable|integer',
            'rol'          => 'nullable|in:' . implode(',', UserAsignacion::ROLES),
            'fecha_inicio' => 'required|date',
        ]);

        $nuevoRol = $request->rol ?? $a->rol;

        if ($nuevoRol !== $a->rol && !$this->puedeAprobarRol($nuevoRol)) {
            return response()->json(['success' => false, 'message' => 'Sin autoridad para asignar este rol.'], 403);
        }

        if ($request->superior_id) {
            $superior = UserAsignacion::where('user_id', (int) $request->superior_id)
                ->where('campana_id', $a->campana_id)
                ->vigentes()
                ->first();

            if (!$superior) {
                return response()->json(['success' => false, 'message' => 'El nuevo superior no tiene asignación vigente en esta campaña.'], 422);
            }

            if ((UserAsignacion::NIVEL_ROL[$superior->rol] ?? 0) <= (UserAsignacion::NIVEL_ROL[$nuevoRol] ?? 0)) {
                return response()->json(['success' => false, 'message' => 'El superior debe tener un rol de mayor jerarquía.'], 422);
            }
        }

        DB::transaction(function () use ($a, $request, $nuevoRol) {
            // El registro viejo cierra el día anterior al inicio del nuevo,
            // pero nunca antes de su propio fecha_inicio.
            $fechaInicioNueva = Carbon::parse($request->fecha_inicio);
            $fechaFinAnterior = $fechaInicioNueva->copy()->subDay()->toDateString();
            $fechaFinAnterior = max($fechaFinAnterior, $a->fecha_inicio->toDateString());

            $a->update(['fecha_fin' => $fechaFinAnterior, 'activo' => false]);

            UserAsignacion::create([
                'user_id'      => $a->user_id,
                'campana_id'   => $a->campana_id,
                'rol'          => $nuevoRol,
                'superior_id'  => $request->superior_id ? (int) $request->superior_id : null,
                'estado'       => UserAsignacion::ESTADO_APROBADO,
                'aprobado_por' => (int) auth()->id(),
                'aprobado_en'  => now(),
                'activo'       => true,
                'fecha_inicio' => $request->fecha_inicio,
                'creado_por'   => (int) auth()->id(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Asignación transferida exitosamente.']);
    }

    // ── API helpers ───────────────────────────────────────────────────────────

    public function usuariosDisponibles(Request $request)
    {
        $campanaId = (int) $request->campana_id;
        if (!$campanaId) return response()->json([]);

        $permitidos = $this->campanaIdsPermitidos();
        if ($permitidos !== null && !in_array($campanaId, $permitidos)) abort(403);

        $asignadosIds = UserAsignacion::where('campana_id', $campanaId)
            ->whereNull('fecha_fin')
            ->pluck('user_id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        $users = User::where('activo', true)
            ->whereNotIn('id', $asignadosIds)
            ->orderBy('name')
            ->get(['id', 'name', 'numero_documento']);

        return response()->json($users);
    }

    public function superioresDisponibles(Request $request)
    {
        $campanaId       = (int) $request->campana_id;
        $rolSolicitado   = $request->rol;
        $nivelSolicitado = UserAsignacion::NIVEL_ROL[$rolSolicitado] ?? 0;

        if (!$campanaId || !$rolSolicitado) return response()->json([]);

        $rolesSuperiores = array_keys(array_filter(
            UserAsignacion::NIVEL_ROL,
            fn($n) => $n > $nivelSolicitado
        ));

        if (empty($rolesSuperiores)) return response()->json([]);

        $asignaciones = UserAsignacion::with('usuario')
            ->where('campana_id', $campanaId)
            ->whereIn('rol', $rolesSuperiores)
            ->vigentes()
            ->get();

        return response()->json($asignaciones->map(fn($a) => [
            'id'               => (int) $a->user_id,
            'name'             => $a->usuario->name ?? '—',
            'numero_documento' => $a->usuario->numero_documento ?? '',
            'rol'              => $a->rol,
        ]));
    }
}
