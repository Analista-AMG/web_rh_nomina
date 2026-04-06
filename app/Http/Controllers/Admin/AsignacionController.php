<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campana;
use App\Models\CentroCosto;
use App\Models\Contrato;
use App\Models\EquipoPrestamo;
use App\Models\Familia;
use App\Models\Planilla;
use App\Models\Persona;
use App\Models\Scopes\AlcanceUsuarioScope;
use App\Models\User;
use App\Models\UserAsignacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
            ->whereIn('rol', [UserAsignacion::ROL_SUPERVISOR, UserAsignacion::ROL_COORDINADOR, UserAsignacion::ROL_JEFE_OPERACIONES])
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
        $permitidos            = $this->campanaIdsPermitidos();
        $mostrarCerradas       = request()->boolean('cerradas');
        $mostrarSinAsignacion  = request()->boolean('sin_asignacion');
        $puedeVerSinAsignacion = $this->esAdmin() || auth()->user()->hasRole('Recursos Humanos');

        if ($permitidos !== null && empty($permitidos)) {
            abort(403, 'No tienes campañas asignadas para gestionar asignaciones.');
        }

        // Solo Admin/RRHH pueden ver la vista "sin asignación"
        if ($mostrarSinAsignacion && !$puedeVerSinAsignacion) {
            abort(403);
        }

        $campanasQuery = Campana::withCount('subcampanas')->orderBy('nombre');
        if ($permitidos !== null) {
            $campanasQuery->whereIn('id', $permitidos);
        }
        $campanas = $campanasQuery->get(['id', 'nombre']);

        $userId = (int) auth()->id();

        // ── Sin Asignación ────────────────────────────────────────────────────
        $sinAsignacion      = collect();
        $countSinAsignacion = 0;
        $planillas          = collect();
        $centrosCosto       = collect();
        $familias           = collect();
        $filtroPlanilla     = array_filter((array) request()->input('planilla_id', []));
        $filtroCentro       = array_filter((array) request()->input('centro_costo_id', []));
        $filtroFamilia      = array_filter((array) request()->input('familia_id', []));

        if ($puedeVerSinAsignacion) {
            $userIdsAsignados = UserAsignacion::vigentes()->pluck('user_id')->toArray();

            // IDs de centros de costo de Gerencia (excluidos)
            $centrosGerencia = CentroCosto::where('nombre_centro_costo', 'like', '%Gerencia%')
                ->pluck('id')->toArray();

            // Personas con contrato activo hoy (sin baja), respetando filtros
            $docsActivos = Persona::withoutGlobalScope(AlcanceUsuarioScope::class)
                ->whereHas('contratos', fn($q) => $q
                    ->withoutGlobalScope(AlcanceUsuarioScope::class)
                    ->whereRaw('inicio_contrato <= CAST(GETDATE() AS DATE)')
                    ->whereRaw('(' . Contrato::FIN_EFECTIVO . ' IS NULL OR ' . Contrato::FIN_EFECTIVO . ' > CAST(GETDATE() AS DATE))')
                    ->when($centrosGerencia, fn($q) => $q->whereNotIn('centro_costo_id', $centrosGerencia))
                    ->when($filtroPlanilla, fn($q) => $q->whereIn('planilla_id',     $filtroPlanilla))
                    ->when($filtroCentro,  fn($q) => $q->whereIn('centro_costo_id', $filtroCentro))
                    ->when($filtroFamilia, fn($q) => $q->whereIn('familia_id',      $filtroFamilia))
                )
                ->pluck('numero_documento')
                ->filter();

            // Usuarios de esas personas sin asignación vigente
            $usersSinAsignacion = User::whereIn('numero_documento', $docsActivos)
                ->whereNotIn('id', $userIdsAsignados)
                ->orderBy('name')
                ->get();

            $countSinAsignacion = $usersSinAsignacion->count();

            if ($mostrarSinAsignacion) {
                // Base sin filtros de usuario (solo Gerencia excluida) para dropdowns coherentes
                $docsBase = Persona::withoutGlobalScope(AlcanceUsuarioScope::class)
                    ->whereHas('contratos', fn($q) => $q
                        ->withoutGlobalScope(AlcanceUsuarioScope::class)
                        ->whereRaw('inicio_contrato <= CAST(GETDATE() AS DATE)')
                        ->whereRaw('(' . Contrato::FIN_EFECTIVO . ' IS NULL OR ' . Contrato::FIN_EFECTIVO . ' > CAST(GETDATE() AS DATE))')
                        ->when($centrosGerencia, fn($q) => $q->whereNotIn('centro_costo_id', $centrosGerencia))
                    )
                    ->pluck('numero_documento')->filter();

                $docsBaseElegibles = User::whereIn('numero_documento', $docsBase)
                    ->whereNotIn('id', $userIdsAsignados)
                    ->pluck('numero_documento');

                // Closure base para dropdowns (evita repetición)
                $baseDropdown = fn() => Contrato::withoutGlobalScope(AlcanceUsuarioScope::class)
                    ->whereRaw('inicio_contrato <= CAST(GETDATE() AS DATE)')
                    ->whereRaw('(' . Contrato::FIN_EFECTIVO . ' IS NULL OR ' . Contrato::FIN_EFECTIVO . ' > CAST(GETDATE() AS DATE))')
                    ->when($centrosGerencia, fn($q) => $q->whereNotIn('centro_costo_id', $centrosGerencia))
                    ->whereHas('persona', fn($q) => $q
                        ->withoutGlobalScope(AlcanceUsuarioScope::class)
                        ->whereIn('numero_documento', $docsBaseElegibles)
                    );

                // Dropdown planillas: aplica centro + familia (no planilla)
                $planillaIds = $baseDropdown()
                    ->when($filtroCentro,  fn($q) => $q->whereIn('centro_costo_id', $filtroCentro))
                    ->when($filtroFamilia, fn($q) => $q->whereIn('familia_id',      $filtroFamilia))
                    ->pluck('planilla_id')->filter()->unique();

                // Dropdown centros: aplica planilla + familia (no centro)
                $centroIds = $baseDropdown()
                    ->when($filtroPlanilla, fn($q) => $q->whereIn('planilla_id', $filtroPlanilla))
                    ->when($filtroFamilia,  fn($q) => $q->whereIn('familia_id',  $filtroFamilia))
                    ->pluck('centro_costo_id')->filter()->unique();

                // Dropdown familias: aplica planilla + centro (no familia)
                $familiaIds = $baseDropdown()
                    ->when($filtroPlanilla, fn($q) => $q->whereIn('planilla_id',     $filtroPlanilla))
                    ->when($filtroCentro,   fn($q) => $q->whereIn('centro_costo_id', $filtroCentro))
                    ->pluck('familia_id')->filter()->unique();

                $planillas    = Planilla::whereIn('id', $planillaIds)
                    ->orderBy('nombre_planilla')->get(['id', 'nombre_planilla']);

                $centrosCosto = CentroCosto::whereIn('id', $centroIds)
                    ->orderBy('nombre_centro_costo')->get(['id', 'nombre_centro_costo']);

                $familias = Familia::whereIn('id', $familiaIds)
                    ->orderBy('nombre_familia')->get(['id', 'nombre_familia']);

                // Datos completos para la tabla
                $docsNecesarios = $usersSinAsignacion->pluck('numero_documento');

                $personasSinAsig = Persona::withoutGlobalScope(AlcanceUsuarioScope::class)
                    ->whereIn('numero_documento', $docsNecesarios)
                    ->with(['contratos' => fn($q) => $q
                        ->withoutGlobalScope(AlcanceUsuarioScope::class)
                        ->activos()
                        ->whereNull('fecha_renuncia')
                        ->when($centrosGerencia, fn($q) => $q->whereNotIn('centro_costo_id', $centrosGerencia))
                        ->when($filtroPlanilla, fn($q) => $q->whereIn('planilla_id',     $filtroPlanilla))
                        ->when($filtroCentro,   fn($q) => $q->whereIn('centro_costo_id', $filtroCentro))
                        ->when($filtroFamilia,  fn($q) => $q->whereIn('familia_id',      $filtroFamilia))
                        ->with(['planilla', 'centroCosto', 'familia'])
                        ->orderByDesc('inicio_contrato')
                    ])
                    ->get()
                    ->keyBy('numero_documento');

                $sinAsignacion = $usersSinAsignacion->map(fn($user) => (object)[
                    'user'     => $user,
                    'persona'  => $personasSinAsig[$user->numero_documento] ?? null,
                    'contrato' => $personasSinAsig[$user->numero_documento]?->contratos->first(),
                ])->filter(fn($row) => $row->persona !== null && $row->contrato !== null)->values();
            }
        }

        // ── Asignaciones normales ─────────────────────────────────────────────
        $query = UserAsignacion::with(['usuario', 'campana', 'superior'])
            ->when(!$mostrarCerradas, fn ($q) => $q->whereNull('fecha_fin'))
            ->when($mostrarCerradas,  fn ($q) => $q->whereNotNull('fecha_fin'))
            ->orderByRaw("CASE estado WHEN 'pendiente' THEN 1 WHEN 'aprobado' THEN 2 ELSE 3 END")
            ->orderBy('created_at', 'desc');

        if ($permitidos !== null) {
            $query->whereIn('campana_id', $permitidos);

            if ($this->miNivelMax() <= UserAsignacion::NIVEL_ROL[UserAsignacion::ROL_SUPERVISOR]) {
                $query->where(fn ($q) => $q
                    ->where('user_id', $userId)
                    ->orWhere('superior_id', $userId)
                );
            }
        }

        $asignaciones = $query->get();

        $docs = $asignaciones->map(fn ($a) => $a->usuario?->numero_documento)
            ->filter()->unique()->values();

        $personasPorDoc = Persona::withoutGlobalScope(AlcanceUsuarioScope::class)
            ->with('contratos')
            ->whereIn('numero_documento', $docs)
            ->get()
            ->keyBy('numero_documento');

        return view('admin.asignaciones.index', [
            'asignaciones'         => $asignaciones,
            'campanas'             => $campanas,
            'esAdmin'              => $this->esAdmin(),
            'miNivelMax'           => $this->miNivelMax(),
            'mostrarCerradas'      => $mostrarCerradas,
            'mostrarSinAsignacion' => $mostrarSinAsignacion,
            'puedeVerSinAsignacion'=> $puedeVerSinAsignacion,
            'sinAsignacion'        => $sinAsignacion,
            'countSinAsignacion'   => $countSinAsignacion,
            'planillas'            => $planillas,
            'centrosCosto'         => $centrosCosto,
            'familias'             => $familias,
            'filtroPlanilla'       => $filtroPlanilla,
            'filtroCentro'         => $filtroCentro,
            'filtroFamilia'        => $filtroFamilia,
            'personasPorDoc'       => $personasPorDoc,
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

        // Colaboradores no pueden tener asignaciones simultáneas en ninguna campaña.
        // Roles superiores (Supervisor, Coordinador, JO) sí pueden tener varias.
        if ($request->rol === UserAsignacion::ROL_COLABORADOR) {
            $solapada = UserAsignacion::where('user_id', (int) $request->user_id)
                ->where('estado', UserAsignacion::ESTADO_APROBADO)
                ->where('fecha_inicio', '<=', $request->fecha_inicio)
                ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $request->fecha_inicio))
                ->with('campana')
                ->first();

            if ($solapada) {
                $detalle = $solapada->campana?->nombre ?? 'otra campaña';
                $desde   = $solapada->fecha_inicio->format('d/m/Y');
                $hasta   = $solapada->fecha_fin ? $solapada->fecha_fin->format('d/m/Y') : 'sin fecha fin';
                return response()->json([
                    'success' => false,
                    'message' => "El colaborador ya tiene una asignación activa en {$detalle} del {$desde} al {$hasta}. Ciérrala antes de crear una nueva.",
                ], 422);
            }
        }

        if ($request->superior_id) {
            $superiorQuery = UserAsignacion::where('user_id', (int) $request->superior_id)->vigentes();
            if ($permitidos !== null) {
                $superiorQuery->whereIn('campana_id', $permitidos);
            }
            $asignacionesSuperior = $superiorQuery->get();

            if ($asignacionesSuperior->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El superior no tiene asignación vigente en las campañas permitidas.',
                ], 422);
            }

            $nivelMaxSuperior = $asignacionesSuperior->max(fn($a) => UserAsignacion::NIVEL_ROL[$a->rol] ?? 0);
            if ($nivelMaxSuperior <= (UserAsignacion::NIVEL_ROL[$request->rol] ?? 0)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El superior debe tener un rol de mayor jerarquía.',
                ], 422);
            }
        }

        $autoAprobar = $this->puedeAprobarRol($request->rol);

        $asignacion = UserAsignacion::create([
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

    public function editar(Request $request, $id)
    {
        $a = UserAsignacion::findOrFail($id);

        abort_if($a->fecha_fin !== null, 422, 'La asignación ya está cerrada.');
        abort_unless($this->puedeAprobarRol($a->rol), 403, 'Sin autoridad para editar este rol.');

        $permitidos = $this->campanaIdsPermitidos();
        if ($permitidos !== null) {
            abort_unless(in_array((int) $a->campana_id, $permitidos), 403);
        }

        $request->validate([
            'superior_id'                    => 'nullable|integer',
            'puede_editar_propia_asistencia' => 'nullable|boolean',
            'fecha_inicio'                   => 'nullable|date',
        ]);

        if ($request->superior_id) {
            $superiorQuery = UserAsignacion::where('user_id', (int) $request->superior_id)->vigentes();
            if ($permitidos !== null) {
                $superiorQuery->whereIn('campana_id', $permitidos);
            }
            $asignacionesSuperior = $superiorQuery->get();

            if ($asignacionesSuperior->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'El superior no tiene asignación vigente en las campañas permitidas.'], 422);
            }

            $nivelMaxSuperior = $asignacionesSuperior->max(fn($a) => UserAsignacion::NIVEL_ROL[$a->rol] ?? 0);
            if ($nivelMaxSuperior <= (UserAsignacion::NIVEL_ROL[$a->rol] ?? 0)) {
                return response()->json(['success' => false, 'message' => 'El superior debe tener un rol de mayor jerarquía.'], 422);
            }
        }

        $campos = ['superior_id' => $request->superior_id ? (int) $request->superior_id : null];

        if ($request->filled('fecha_inicio')) {
            $campos['fecha_inicio'] = $request->fecha_inicio;
        }
        $oldSuperiorId = $a->superior_id;
        $newSuperiorId = $campos['superior_id'];

        if ($request->has('puede_editar_propia_asistencia')) {
            $campos['puede_editar_propia_asistencia'] = (bool) $request->puede_editar_propia_asistencia;
        }

        DB::transaction(function () use ($a, $campos, $oldSuperiorId, $newSuperiorId, $asignacionesSuperior) {
            $a->update($campos);

            if ($oldSuperiorId && $newSuperiorId && $oldSuperiorId !== $newSuperiorId) {
                $user = $a->usuario;
                if ($user?->numero_documento) {
                    $empleadoId = Persona::withoutGlobalScope(AlcanceUsuarioScope::class)
                        ->where('numero_documento', $user->numero_documento)
                        ->value('id');

                    if ($empleadoId) {
                        $nuevaCampana = $asignacionesSuperior->first()?->campana_id;

                        // Reasignar préstamos activos/pendientes al nuevo supervisor
                        EquipoPrestamo::where('empleado_id', $empleadoId)
                            ->where('supervisor_origen_id', $oldSuperiorId)
                            ->whereIn('estado', [EquipoPrestamo::ESTADO_PENDIENTE, EquipoPrestamo::ESTADO_APROBADO])
                            ->update([
                                'supervisor_origen_id' => $newSuperiorId,
                                'campana_origen_id'    => $nuevaCampana,
                            ]);

                    }
                }
            }
        });

        return response()->json(['success' => true, 'message' => 'Asignación actualizada.']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Retorna el campana_id dado más todos sus ancestros.
     * Usado para validar que un superior pertenece a la cadena de la asignación.
     */
    private function campanaYAncestros(int $campanaId): array
    {
        $ids = [];
        $id  = $campanaId;

        while ($id) {
            $ids[] = $id;
            $id    = Campana::where('id', $id)->value('parent_id');
        }

        return $ids;
    }

    /**
     * Retorna solo los ancestros (padre, abuelo, …) de una campaña, sin incluirla a ella.
     * Los superiores siempre viven en una campaña de nivel superior, no en la misma.
     */
    private function soloAncestros(int $campanaId): array
    {
        $ids = [];
        $id  = Campana::where('id', $campanaId)->value('parent_id');

        while ($id) {
            $ids[] = $id;
            $id    = Campana::where('id', $id)->value('parent_id');
        }

        return $ids;
    }

    public function destroy(int $id)
    {
        $asignacion = UserAsignacion::findOrFail($id);
        $asignacion->delete();

        return response()->json(['message' => 'Asignación eliminada correctamente.']);
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
        $nivelMax        = $this->miNivelMax();

        if (!$campanaId || !$rolSolicitado) return response()->json([]);

        $rolesSuperiores = array_keys(array_filter(
            UserAsignacion::NIVEL_ROL,
            fn($n) => $n > $nivelSolicitado && $n <= $nivelMax
        ));

        if (empty($rolesSuperiores)) return response()->json([]);

        $permitidos = $this->campanaIdsPermitidos();
        if ($permitidos !== null && !in_array($campanaId, $permitidos)) abort(403);

        $query = UserAsignacion::with(['usuario', 'campana'])
            ->whereIn('rol', $rolesSuperiores)
            ->where('campana_id', $campanaId)
            ->vigentes();

        $asignaciones = $query->get();

        return response()->json($asignaciones->map(fn($a) => [
            'id'               => (int) $a->user_id,
            'name'             => $a->usuario->name ?? '—',
            'numero_documento' => $a->usuario->numero_documento ?? '',
            'rol'              => $a->rol,
            'campana'          => $a->campana->nombre ?? '—',
        ]));
    }

}
