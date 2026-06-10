<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\ControlConfirmacion;
use App\Models\GrupoGerencia;
use App\Models\Persona;
use App\Models\User;
use App\Models\UserAsignacion;
use App\Models\Scopes\AlcanceUsuarioScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ControlConfirmacionController extends Controller
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function esAdminORrhh(): bool
    {
        return auth()->user()->hasRole('Administrador')
            || auth()->user()->hasRole('Recursos Humanos');
    }

    /**
     * Genera el dropdown de periodos: mes actual + 5 meses anteriores.
     */
    private function periodosDisponibles(): array
    {
        $limite  = '2026-06';
        $periodos = [];
        for ($i = 0; $i < 6; $i++) {
            $fecha = now()->subMonths($i);
            if ($fecha->format('Y-m') < $limite) break;
            $periodos[] = [
                'value' => $fecha->format('Y-m'),
                'label' => ucfirst($fecha->translatedFormat('F Y')),
            ];
        }
        return $periodos;
    }

    /**
     * Devuelve [inicio_mes, fin_mes] como Carbon para un periodo 'YYYY-MM'.
     */
    private function rangoMes(string $periodo): array
    {
        $inicio = Carbon::parse($periodo . '-01')->startOfDay();
        $fin    = $inicio->copy()->endOfMonth()->endOfDay();
        return [$inicio, $fin];
    }

    /**
     * Devuelve todos los user_id del subárbol jerárquico del usuario dado.
     * Una sola query trae todas las asignaciones vigentes; el recorrido BFS es en PHP.
     */
    private function subtreeUserIds(int $userId): \Illuminate\Support\Collection
    {
        $mapa = UserAsignacion::vigentes()
            ->whereNotNull('superior_id')
            ->get(['superior_id', 'user_id'])
            ->groupBy('superior_id')
            ->map(fn($g) => $g->pluck('user_id'));

        $resultado = collect();
        $cola      = collect([$userId]);

        while ($cola->isNotEmpty()) {
            $actual    = $cola->shift();
            $hijos     = $mapa->get($actual, collect());
            $resultado = $resultado->merge($hijos);
            $cola      = $cola->merge($hijos);
        }

        return $resultado->unique()->values();
    }

    private function subordinadosEnPeriodo(int $superiorId, Carbon $inicio, Carbon $fin)
    {
        return UserAsignacion::where('estado', UserAsignacion::ESTADO_APROBADO)
            ->where('superior_id', $superiorId)
            ->where('fecha_inicio', '<=', $fin->toDateString())
            ->where(fn($q) => $q->whereNull('fecha_fin')
                                ->orWhere('fecha_fin', '>=', $inicio->toDateString()))
            ->with('usuario')
            ->get();
    }

    /**
     * Obtiene los contratos de una colección de personas activos en cualquier
     * punto del periodo, con el movimiento vigente en ese rango cargado.
     *
     * @param  \Illuminate\Support\Collection $personas  keyBy('numero_documento')
     * @param  Carbon                         $inicio
     * @param  Carbon                         $fin
     * @return \Illuminate\Support\Collection de Contrato (con ->persona adjunto)
     */
    private function contratosEnPeriodo($personas, Carbon $inicio, Carbon $fin)
    {
        if ($personas->isEmpty()) return collect();

        $personaIds = $personas->pluck('id');

        return Contrato::withoutGlobalScope(AlcanceUsuarioScope::class)
            ->whereIn('persona_id', $personaIds)
            ->where('inicio_contrato', '<=', $fin->toDateString())
            ->where(function ($q) use ($inicio) {
                $q->whereNull('fin_contrato')
                  ->orWhere('fin_contrato', '>=', $inicio->toDateString());
            })
            ->where(function ($q) use ($inicio) {
                $q->whereNull('fecha_renuncia')
                  ->orWhere('fecha_renuncia', '>=', $inicio->toDateString());
            })
            ->with(['persona', 'movimientos' => function ($q) use ($inicio, $fin) {
                $q->where('inicio', '<=', $fin->toDateString())
                  ->where(function ($m) use ($inicio) {
                      $m->whereNull('fin')
                        ->orWhere('fin', '>=', $inicio->toDateString());
                  })
                  ->with(['planilla', 'centroCosto', 'familia', 'cargo', 'condicion'])
                  ->orderByDesc('inicio');
            }])
            ->get();
    }

    /**
     * Aplica el snapshot de un movimiento + datos de aprobación y hace upsert.
     */
    private function upsertConfirmacion(
        array $base,
        int $contratoId,
        int $personaId,
        ?string $comentario,
        string $estado,
        string $origen
    ): void {
        ControlConfirmacion::updateOrCreate(
            ['periodo' => $base['periodo'], 'contrato_id' => $contratoId],
            array_merge($base, [
                'persona_id'     => $personaId,
                'estado'         => $estado,
                'confirmado_por' => auth()->id(),
                'origen_accion'  => $origen,
                'comentario'     => $comentario,
                'confirmado_en'  => now(),
            ])
        );
    }

    /**
     * Detecta si el movimiento actual difiere del snapshot confirmado.
     * Cubre dos casos: nuevo movimiento SCD2 (id distinto) o edición
     * directa de campos críticos en el mismo movimiento (id igual).
     */
    private function movimientoCambio(ControlConfirmacion $conf, $mov): bool
    {
        // != (loose) porque SQL Server ODBC devuelve IDs como string desde BD
        if ($conf->movimiento_id != $mov->id) return true;

        return $conf->planilla_id     != $mov->planilla_id
            || $conf->cargo_id        != $mov->cargo_id
            || $conf->centro_costo_id != $mov->centro_costo_id
            || $conf->familia_id      != $mov->familia_id
            || $conf->condicion_id    != $mov->condicion_id
            || $conf->haber_basico    != $mov->haber_basico;
    }

    /**
     * Devuelve un array [campo => [antes, ahora]] con las diferencias detectadas.
     * Usa los mismos datos ya cargados — cero queries adicionales.
     * Nota: nombre_condicion no está en el snapshot; se muestra solo el valor nuevo.
     */
    private function camposModificados(ControlConfirmacion $conf, $mov): array
    {
        $cambios = [];

        if ($conf->planilla_id != $mov->planilla_id)
            $cambios['Empresa'] = [$conf->empresa, $mov->planilla?->nombre_empresa];

        if ($conf->cargo_id != $mov->cargo_id)
            $cambios['Cargo'] = [$conf->nombre_cargo, $mov->cargo?->nombre_cargo];

        if ($conf->centro_costo_id != $mov->centro_costo_id)
            $cambios['Centro costo'] = [$conf->nombre_centro_costo, $mov->centroCosto?->nombre_centro_costo];

        if ($conf->familia_id != $mov->familia_id)
            $cambios['Familia'] = [$conf->nombre_familia, $mov->familia?->nombre_familia];

        if ($conf->condicion_id != $mov->condicion_id)
            $cambios['Condición'] = [null, $mov->condicion?->nombre_condicion];

        if ($conf->haber_basico != $mov->haber_basico)
            $cambios['Haber básico'] = [
                'S/ ' . number_format((float) $conf->haber_basico, 2),
                'S/ ' . number_format((float) $mov->haber_basico, 2),
            ];

        // Nuevo movimiento SCD2 pero sin diferencias en campos críticos
        if (empty($cambios) && $conf->movimiento_id != $mov->id)
            $cambios['Movimiento'] = ['#' . $conf->movimiento_id, '#' . $mov->id];

        return $cambios;
    }

    // ── Vista supervisor ───────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $periodo = $request->input('periodo', now()->format('Y-m'));

        if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            $periodo = now()->format('Y-m');
        }

        [$inicio, $fin] = $this->rangoMes($periodo);

        $user = auth()->user();

        // Subordinados activos en cualquier punto del periodo (no solo hoy)
        $subordinados = $this->subordinadosEnPeriodo($user->id, $inicio, $fin);

        // Cada persona va con su supervisor más reciente en el periodo.
        // Si alguien tuvo dos supervisores en el mes, el anterior no lo ve.
        if ($subordinados->isNotEmpty()) {
            $todasAsigsPeriodo = UserAsignacion::where('estado', UserAsignacion::ESTADO_APROBADO)
                ->whereIn('user_id', $subordinados->pluck('user_id')->unique())
                ->where('fecha_inicio', '<=', $fin->toDateString())
                ->where(fn($q) => $q->whereNull('fecha_fin')
                                    ->orWhere('fecha_fin', '>=', $inicio->toDateString()))
                ->get(['user_id', 'superior_id', 'fecha_inicio']);

            $supMasReciente = $todasAsigsPeriodo
                ->groupBy('user_id')
                ->map(fn($asigs) => $asigs->sortByDesc('fecha_inicio')->first()->superior_id);

            $subordinados = $subordinados->reject(
                fn($a) => $supMasReciente->get($a->user_id) != $user->id
            );
        }

        $docs = $subordinados
            ->map(fn($a) => $a->usuario?->numero_documento)
            ->filter()
            ->unique()
            ->values();

        $personas = Persona::withoutGlobalScope(AlcanceUsuarioScope::class)
            ->whereIn('numero_documento', $docs)
            ->get()
            ->keyBy('numero_documento');

        $contratos = $this->contratosEnPeriodo($personas, $inicio, $fin);

        // Admin/RRHH: reemplazar con contratos huérfanos (sin supervisor asignado en el periodo)
        if ($this->esAdminORrhh()) {
            $docsConSup = UserAsignacion::where('estado', UserAsignacion::ESTADO_APROBADO)
                ->whereNotNull('superior_id')
                ->where('fecha_inicio', '<=', $fin->toDateString())
                ->where(fn($q) => $q->whereNull('fecha_fin')
                                    ->orWhere('fecha_fin', '>=', $inicio->toDateString()))
                ->with('usuario')
                ->get()
                ->map(fn($a) => $a->usuario?->numero_documento)
                ->filter()->unique()->values();

            $personasHuerfanas = Persona::withoutGlobalScope(AlcanceUsuarioScope::class)
                ->whereNotIn('numero_documento', $docsConSup->isEmpty() ? ['__none__'] : $docsConSup)
                ->get()
                ->keyBy('numero_documento');

            $contratos  = $this->contratosEnPeriodo($personasHuerfanas, $inicio, $fin);
            $personas   = $personasHuerfanas;
            $subordinados = collect();
        }

        // Confirmaciones ya existentes para el periodo
        $confirmaciones = ControlConfirmacion::where('periodo', $periodo)
            ->whereIn('contrato_id', $contratos->pluck('id'))
            ->with('confirmadoPor')
            ->get()
            ->keyBy('contrato_id');

        // Agrupar asignaciones por persona_id para evitar matches cruzados entre personas distintas
        $asigsPorPersonaId = collect();
        foreach ($subordinados as $a) {
            $pid = $personas->get($a->usuario?->numero_documento)?->id;
            if ($pid) {
                if (!$asigsPorPersonaId->has($pid)) $asigsPorPersonaId->put($pid, collect());
                $asigsPorPersonaId->get($pid)->push($a);
            }
        }

        // Map user_id → persona_id para cruzar con asignaciones de otros supervisores
        $personaIdPorUserId = $subordinados->mapWithKeys(fn($a) => [
            $a->user_id => $personas->get($a->usuario?->numero_documento)?->id
        ])->filter();

        // Asignaciones de supervisores anteriores sobre los mismos subordinados en el periodo
        $asigsPreviasByPersonaId = collect();
        if ($subordinados->isNotEmpty()) {
            $prev = UserAsignacion::where('estado', UserAsignacion::ESTADO_APROBADO)
                ->whereIn('user_id', $subordinados->pluck('user_id')->unique())
                ->where('superior_id', '!=', $user->id)
                ->where('fecha_inicio', '<=', $fin->toDateString())
                ->where(fn($q) => $q->whereNull('fecha_fin')
                                    ->orWhere('fecha_fin', '>=', $inicio->toDateString()))
                ->with('superior')
                ->get();
            foreach ($prev as $a) {
                $pid = $personaIdPorUserId->get($a->user_id);
                if ($pid) {
                    if (!$asigsPreviasByPersonaId->has($pid)) $asigsPreviasByPersonaId->put($pid, collect());
                    $asigsPreviasByPersonaId->get($pid)->push($a);
                }
            }
        }

        // Map contrato_id → {asignacion, solapo, relacion, asigAnterior}
        // Keyed por contrato (no persona) para manejar personas con 2 contratos en el mismo mes.
        $asigsPorContratoId = collect();
        foreach ($contratos as $contrato) {
            $cInicio = Carbon::parse($contrato->inicio_contrato)->max($inicio);
            $cFin    = $fin->copy();
            if ($contrato->fin_contrato)   { $cFin = Carbon::parse($contrato->fin_contrato)->min($cFin); }
            if ($contrato->fecha_renuncia) { $cFin = Carbon::parse($contrato->fecha_renuncia)->min($cFin); }

            $asigsDeLaPersona = $asigsPorPersonaId->get($contrato->persona_id, collect());

            $solapeFn = function ($a) use ($cInicio, $cFin) {
                $aFin = $a->fecha_fin ? Carbon::parse($a->fecha_fin) : null;
                return Carbon::parse($a->fecha_inicio)->lte($cFin)
                    && (!$aFin || $aFin->gte($cInicio));
            };

            $asig = $asigsDeLaPersona->sortByDesc('fecha_inicio')->first($solapeFn);

            $solapo = $asig !== null;

            if (!$asig && $asigsDeLaPersona->isNotEmpty()) {
                $asig = $asigsDeLaPersona
                    ->filter(fn($a) => Carbon::parse($a->fecha_inicio)->gt($cFin))
                    ->sortBy('fecha_inicio')->first();
            }

            // Quién tenía a esta persona durante la ventana del contrato (otro supervisor)
            $asigAnterior = $asigsPreviasByPersonaId->get($contrato->persona_id, collect())
                ->sortByDesc('fecha_inicio')->first($solapeFn);

            if ($asig) {
                $asigsPorContratoId->put($contrato->id, (object)[
                    'asignacion'   => $asig,
                    'solapo'       => $solapo,
                    'asigAnterior' => $asigAnterior,
                ]);
            }
        }

        // Ensamblar filas para la vista
        $filasAll = $contratos->map(function ($contrato) use ($confirmaciones, $asigsPorContratoId) {
            $mov          = $contrato->movimientos->first();
            $confirmacion = $confirmaciones->get($contrato->id);
            $movCambio    = $confirmacion && $mov && $this->movimientoCambio($confirmacion, $mov);
            $asigEntry    = $asigsPorContratoId->get($contrato->id);
            return (object) [
                'contrato'     => $contrato,
                'persona'      => $contrato->persona,
                'movimiento'   => $mov,
                'confirmacion' => $confirmacion,
                'movCambio'    => $movCambio,
                'cambiados'    => $movCambio ? $this->camposModificados($confirmacion, $mov) : [],
                'asignacion'   => $asigEntry?->asignacion,
                'asigSolapo'   => $asigEntry?->solapo ?? false,
                'asigAnterior' => $asigEntry?->asigAnterior,
            ];
        })->sortBy('persona.apellido_paterno')->values();

        // Stats siempre sobre el total antes de filtrar
        $stats = ['total' => $filasAll->count(), 'confirmados' => 0, 'requieren_actualizacion' => 0, 'pendientes' => 0, 'mov_cambio' => 0];
        foreach ($filasAll as $f) {
            if ($f->confirmacion?->estado === ControlConfirmacion::ESTADO_CONFIRMADO && !$f->movCambio) $stats['confirmados']++;
            if ($f->confirmacion?->estado === ControlConfirmacion::ESTADO_REQUIERE_ACTUALIZACION)       $stats['requieren_actualizacion']++;
            if (!$f->confirmacion || $f->confirmacion->estado === ControlConfirmacion::ESTADO_PENDIENTE) $stats['pendientes']++;
            if ($f->movCambio)                                                                           $stats['mov_cambio']++;
        }

        // Filtro por estado
        $filtro = in_array($request->input('filtro'), ['pendiente', 'datos_actualizados', 'confirmado'])
            ? $request->input('filtro')
            : null;

        if ($filtro === 'pendiente') {
            $filasAll = $filasAll->filter(fn($f) =>
                !$f->movCambio && (!$f->confirmacion || $f->confirmacion->estado !== ControlConfirmacion::ESTADO_CONFIRMADO)
            )->values();
        } elseif ($filtro === 'datos_actualizados') {
            $filasAll = $filasAll->filter(fn($f) => $f->movCambio)->values();
        } elseif ($filtro === 'confirmado') {
            $filasAll = $filasAll->filter(fn($f) =>
                !$f->movCambio && $f->confirmacion?->estado === ControlConfirmacion::ESTADO_CONFIRMADO
            )->values();
        }

        $perPage = 10;
        $page    = max(1, (int) $request->input('page', 1));
        $filas   = new LengthAwarePaginator(
            $filasAll->slice(($page - 1) * $perPage, $perPage)->values(),
            $filasAll->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->except('page')]
        );

        return view('contratos.confirmaciones.index', [
            'filas'        => $filas,
            'periodo'      => $periodo,
            'periodos'     => $this->periodosDisponibles(),
            'stats'        => $stats,
            'filtro'       => $filtro,
            'esAdminORrhh' => $this->esAdminORrhh(),
        ]);
    }

    // ── POST confirmar (supervisor) ────────────────────────────────────────────

    public function confirmar(Request $request)
    {
        $data = $request->validate([
            'periodo'       => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'contrato_ids'  => ['required', 'array', 'min:1'],
            'contrato_ids.*'=> ['integer'],
            'estado'        => ['required', 'in:confirmado,requiere_actualizacion'],
            'comentario'    => ['nullable', 'string', 'max:500'],
        ]);

        [$inicio, $fin] = $this->rangoMes($data['periodo']);

        // Security: subordinados en cualquier punto del periodo (mismo criterio que index)
        $subordinados = $this->subordinadosEnPeriodo(auth()->id(), $inicio, $fin);

        // Cada persona va con su supervisor más reciente — misma lógica que index()
        if ($subordinados->isNotEmpty()) {
            $todasAsigsPeriodo = UserAsignacion::where('estado', UserAsignacion::ESTADO_APROBADO)
                ->whereIn('user_id', $subordinados->pluck('user_id')->unique())
                ->where('fecha_inicio', '<=', $fin->toDateString())
                ->where(fn($q) => $q->whereNull('fecha_fin')
                                    ->orWhere('fecha_fin', '>=', $inicio->toDateString()))
                ->get(['user_id', 'superior_id', 'fecha_inicio']);

            $supMasReciente = $todasAsigsPeriodo
                ->groupBy('user_id')
                ->map(fn($asigs) => $asigs->sortByDesc('fecha_inicio')->first()->superior_id);

            $subordinados = $subordinados->reject(
                fn($a) => $supMasReciente->get($a->user_id) != auth()->id()
            );
        }

        $docs       = $subordinados
            ->map(fn($a) => $a->usuario?->numero_documento)
            ->filter()->unique()->values();
        $personaIds = Persona::withoutGlobalScope(AlcanceUsuarioScope::class)
            ->whereIn('numero_documento', $docs)
            ->pluck('id');

        $contratos = Contrato::withoutGlobalScope(AlcanceUsuarioScope::class)
            ->whereIn('id', $data['contrato_ids'])
            ->whereIn('persona_id', $personaIds)
            ->with(['movimientos' => function ($q) use ($inicio, $fin) {
                $q->where('inicio', '<=', $fin->toDateString())
                  ->where(function ($m) use ($inicio) {
                      $m->whereNull('fin')
                        ->orWhere('fin', '>=', $inicio->toDateString());
                  })
                  ->with(['planilla', 'centroCosto', 'familia', 'cargo', 'condicion'])
                  ->orderByDesc('inicio');
            }])
            ->get()
            ->keyBy('id');

        $existentes = ControlConfirmacion::where('periodo', $data['periodo'])
            ->whereIn('contrato_id', $data['contrato_ids'])
            ->get()->keyBy('contrato_id');

        DB::transaction(function () use ($data, $contratos, $existentes) {
            foreach ($data['contrato_ids'] as $contratoId) {
                $contrato = $contratos->get($contratoId);
                if (!$contrato) continue;

                $mov = $contrato->movimientos->first();
                if (!$mov) continue;

                // Solo 'confirmado' actualiza el snapshot.
                // 'requiere_actualizacion' preserva el snapshot existente.
                if ($data['estado'] === ControlConfirmacion::ESTADO_CONFIRMADO) {
                    $snapshot = array_merge(
                        ['periodo' => $data['periodo']],
                        ControlConfirmacion::buildSnapshot($mov)
                    );
                } else {
                    $snapshot = $existentes->has($contratoId)
                        ? ['periodo' => $data['periodo']]
                        : array_merge(['periodo' => $data['periodo']], ControlConfirmacion::buildSnapshot($mov));
                }

                $this->upsertConfirmacion(
                    $snapshot,
                    (int) $contratoId,
                    $contrato->persona_id,
                    $data['comentario'] ?? null,
                    $data['estado'],
                    ControlConfirmacion::ORIGEN_SUPERVISOR
                );
            }
        });

        return back()->with('success', 'Confirmación registrada correctamente.');
    }

    // ── Tablero RRHH/Admin ─────────────────────────────────────────────────────

    public function tablero(Request $request)
    {

        $periodo = $request->input('periodo', now()->format('Y-m'));

        if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            $periodo = now()->format('Y-m');
        }

        [$inicio, $fin] = $this->rangoMes($periodo);

        // ── Query 1a: superiores con subordinados VIGENTES hoy (estructura del tablero) ──
        // RRHH/Admin ven toda la empresa. Cualquier otro usuario con permiso al tablero
        // (ej. coordinador) solo ve los supervisores que son sus subordinados directos.
        if ($this->esAdminORrhh()) {
            $superiorIdsActivos = UserAsignacion::vigentes()
                ->whereNotNull('superior_id')
                ->pluck('superior_id')
                ->unique();
        } else {
            $subtree = $this->subtreeUserIds(auth()->id());

            $superiorIdsActivos = UserAsignacion::vigentes()
                ->whereNotNull('superior_id')
                ->whereIn('superior_id', $subtree)
                ->pluck('superior_id')
                ->unique();
        }

        if ($superiorIdsActivos->isEmpty()) {
            $filtro = in_array($request->input('filtro'), ['pendiente', 'confirmado', 'requiere_actualizacion'])
                ? $request->input('filtro')
                : null;

            return view('contratos.confirmaciones.tablero', [
                'tablero'  => collect(),
                'stats'    => ['total' => 0, 'confirmados' => 0, 'requieren' => 0, 'pendientes' => 0, 'pct' => 0],
                'filtro'   => $filtro,
                'periodo'  => $periodo,
                'periodos' => $this->periodosDisponibles(),
                'esRrhh'   => $this->esAdminORrhh(),
                'grupos'   => GrupoGerencia::orderBy('nombre_grupo_gerencia')->get(),
            ]);
        }

        // ── Query 1b: subordinados del periodo, restringidos a superiores activos ──
        // Captura personas que estuvieron en el equipo aunque se hayan ido mid-mes,
        // pero solo bajo supervisores que siguen activos (no resucita jefes dados de baja).
        $todasAsignaciones = UserAsignacion::where('estado', UserAsignacion::ESTADO_APROBADO)
            ->whereNotNull('superior_id')
            ->whereIn('superior_id', $superiorIdsActivos)
            ->where('fecha_inicio', '<=', $fin->toDateString())
            ->where(fn($q) => $q->whereNull('fecha_fin')
                                ->orWhere('fecha_fin', '>=', $inicio->toDateString()))
            ->with('usuario')
            ->get();

        // Cada persona va con su supervisor más reciente. Si alguien tuvo dos supervisores
        // en el periodo (cambio mid-mes), la asignación anterior se descarta aquí y solo
        // aparece en el tooltip de Variante C del supervisor nuevo.
        $todasAsignaciones = $todasAsignaciones
            ->groupBy('user_id')
            ->map(fn($asigs) => $asigs->sortByDesc('fecha_inicio')->first())
            ->values();

        // doc → [superior_id, ...] (un colaborador puede tener un único superior vigente)
        $docsPorSupervisor = $todasAsignaciones
            ->groupBy('superior_id')
            ->map(fn($asigs) => $asigs
                ->map(fn($a) => $a->usuario?->numero_documento)
                ->filter()->unique()->values()
            );

        $todosLosDocs = $todasAsignaciones
            ->map(fn($a) => $a->usuario?->numero_documento)
            ->filter()->unique()->values();

        // ── Query 2: todos los supervisores ──────────────────────────────────
        $supervisores = User::whereIn('id', $docsPorSupervisor->keys())
            ->get()
            ->keyBy('id');

        // ── Query 3: todas las personas de todos los subordinados ─────────────
        $todasPersonas = Persona::withoutGlobalScope(AlcanceUsuarioScope::class)
            ->whereIn('numero_documento', $todosLosDocs)
            ->get()
            ->keyBy('numero_documento');
        $personasPorId = $todasPersonas->values()->keyBy('id');

        // supervisor_id → [persona_id, ...]
        $personaIdsPorSupervisor = $docsPorSupervisor->map(fn($docs) =>
            $docs->map(fn($doc) => $todasPersonas->get($doc)?->id)->filter()->values()
        );

        // ── Query 4: todos los contratos del periodo para esas personas ───────
        $todosContratos = Contrato::withoutGlobalScope(AlcanceUsuarioScope::class)
            ->whereIn('persona_id', $todasPersonas->pluck('id'))
            ->where('inicio_contrato', '<=', $fin->toDateString())
            ->where(fn($q) => $q
                ->whereNull('fin_contrato')
                ->orWhere('fin_contrato', '>=', $inicio->toDateString())
            )
            ->where(fn($q) => $q
                ->whereNull('fecha_renuncia')
                ->orWhere('fecha_renuncia', '>=', $inicio->toDateString())
            )
            ->with(['movimientos' => fn($q) => $q
                ->where('inicio', '<=', $fin->toDateString())
                ->where(fn($m) => $m->whereNull('fin')->orWhere('fin', '>=', $inicio->toDateString()))
                ->with(['planilla', 'centroCosto', 'familia', 'cargo', 'condicion'])
                ->orderByDesc('inicio')
            ])
            ->get();

        $contratosPorPersonaId = $todosContratos->groupBy('persona_id');

        // ── Query 5: todas las confirmaciones del periodo ─────────────────────
        $todasConfirmaciones = ControlConfirmacion::where('periodo', $periodo)
            ->whereIn('contrato_id', $todosContratos->pluck('id'))
            ->with('confirmadoPor')
            ->get()
            ->keyBy('contrato_id');

        // Pre-agrupar asignaciones: supervisor_id → (persona_id → [asignaciones])
        // Evita matches cruzados entre personas distintas al buscar por solape de fechas.
        $asigsPorSupYPersona = collect();
        foreach ($todasAsignaciones as $a) {
            $pid = $todasPersonas->get($a->usuario?->numero_documento)?->id;
            $sid = $a->superior_id;
            if ($pid && $sid) {
                if (!$asigsPorSupYPersona->has($sid)) $asigsPorSupYPersona->put($sid, collect());
                if (!$asigsPorSupYPersona->get($sid)->has($pid)) $asigsPorSupYPersona->get($sid)->put($pid, collect());
                $asigsPorSupYPersona->get($sid)->get($pid)->push($a);
            }
        }

        // Map user_id → persona_id para cruzar con asignaciones de supervisores inactivos
        $personaIdPorUserIdGlobal = $todasAsignaciones->mapWithKeys(fn($a) => [
            $a->user_id => $todasPersonas->get($a->usuario?->numero_documento)?->id
        ])->filter();

        // ── Query 1c: asignaciones cerradas (históricas) para estos subordinados ──
        // Una asignación "anterior" siempre tiene fecha_fin seteado. Filtrar por
        // whereNotIn($superiorIdsActivos) era incorrecto: excluía supervisores que
        // siguen activos con otras personas pero ya no tienen a este colaborador.
        $asigsPreviasTabl = collect();
        if ($todasAsignaciones->isNotEmpty()) {
            $prev = UserAsignacion::where('estado', UserAsignacion::ESTADO_APROBADO)
                ->whereIn('user_id', $todasAsignaciones->pluck('user_id')->unique())
                ->whereNotNull('fecha_fin')
                ->where('fecha_inicio', '<=', $fin->toDateString())
                ->where('fecha_fin', '>=', $inicio->toDateString())
                ->with('superior')
                ->get();
            foreach ($prev as $a) {
                $pid = $personaIdPorUserIdGlobal->get($a->user_id);
                if ($pid) {
                    if (!$asigsPreviasTabl->has($pid)) $asigsPreviasTabl->put($pid, collect());
                    $asigsPreviasTabl->get($pid)->push($a);
                }
            }
        }

        // ── Agrupación en PHP — sin más queries ───────────────────────────────
        $tablero = $supervisores->map(function (User $sup) use (
            $personaIdsPorSupervisor, $contratosPorPersonaId, $todasConfirmaciones, $personasPorId,
            $asigsPorSupYPersona, $asigsPreviasTabl, $inicio, $fin
        ) {
            $personaIds = $personaIdsPorSupervisor->get($sup->id, collect());

            $contratos = $personaIds
                ->flatMap(fn($pid) => $contratosPorPersonaId->get($pid, collect()))
                ->values();

            $confirmados = 0;
            $requieren   = 0;
            $movCambios  = 0;

            // Asignaciones de este supervisor agrupadas por persona_id
            $asigsDeSup = $asigsPorSupYPersona->get($sup->id, collect());

            $asigsPorContratoId = collect();
            foreach ($contratos as $contrato) {
                $cInicio = Carbon::parse($contrato->inicio_contrato)->max($inicio);
                $cFin    = $fin->copy();
                if ($contrato->fin_contrato)   { $cFin = Carbon::parse($contrato->fin_contrato)->min($cFin); }
                if ($contrato->fecha_renuncia) { $cFin = Carbon::parse($contrato->fecha_renuncia)->min($cFin); }

                $asigsDeLaPersona = $asigsDeSup->get($contrato->persona_id, collect());

                $solapeFn = function ($a) use ($cInicio, $cFin) {
                    $aFin = $a->fecha_fin ? Carbon::parse($a->fecha_fin) : null;
                    return Carbon::parse($a->fecha_inicio)->lte($cFin)
                        && (!$aFin || $aFin->gte($cInicio));
                };

                $asig = $asigsDeLaPersona->sortByDesc('fecha_inicio')->first($solapeFn);

                $solapo = $asig !== null;

                if (!$asig && $asigsDeLaPersona->isNotEmpty()) {
                    $asig = $asigsDeLaPersona
                        ->filter(fn($a) => Carbon::parse($a->fecha_inicio)->gt($cFin))
                        ->sortBy('fecha_inicio')->first();
                }

                $asigAnterior = $asigsPreviasTabl->get($contrato->persona_id, collect())
                    ->filter(fn($a) => $a->superior_id !== $sup->id)
                    ->sortByDesc('fecha_inicio')->first($solapeFn);

                if ($asig) {
                    $asigsPorContratoId->put($contrato->id, (object)[
                        'asignacion'   => $asig,
                        'solapo'       => $solapo,
                        'asigAnterior' => $asigAnterior,
                    ]);
                }
            }

            $filas = $contratos->map(function ($contrato) use (
                $todasConfirmaciones, &$confirmados, &$requieren, &$movCambios, $personasPorId,
                $asigsPorContratoId
            ) {
                $mov          = $contrato->movimientos->first();
                $confirmacion = $todasConfirmaciones->get($contrato->id);
                $movCambio    = $confirmacion && $mov && $this->movimientoCambio($confirmacion, $mov);

                if ($movCambio)                                                                   $movCambios++;
                elseif ($confirmacion?->estado === ControlConfirmacion::ESTADO_CONFIRMADO)         $confirmados++;
                elseif ($confirmacion?->estado === ControlConfirmacion::ESTADO_REQUIERE_ACTUALIZACION) $requieren++;

                $asigEntry = $asigsPorContratoId->get($contrato->id);
                return (object) [
                    'contrato'     => $contrato,
                    'persona'      => $personasPorId->get($contrato->persona_id),
                    'movimiento'   => $mov,
                    'confirmacion' => $confirmacion,
                    'movCambio'    => $movCambio,
                    'cambiados'    => $movCambio ? $this->camposModificados($confirmacion, $mov) : [],
                    'asignacion'   => $asigEntry?->asignacion,
                    'asigSolapo'   => $asigEntry?->solapo ?? false,
                    'asigAnterior' => $asigEntry?->asigAnterior,
                ];
            })->sortBy('persona.apellido_paterno')->values();

            $total      = $contratos->count();
            $pendientes = $total - $confirmados - $requieren - $movCambios;

            return (object) [
                'supervisor'  => $sup,
                'total'       => $total,
                'confirmados' => $confirmados,
                'requieren'   => $requieren,
                'pendientes'  => $pendientes + $movCambios,
                'pct'         => $total > 0 ? round(($confirmados / $total) * 100) : 0,
                'filas'       => $filas,
            ];
        })->sortBy('supervisor.name')->values();

        // Stats globales antes de filtrar — los KPIs siempre muestran el total real
        $stats = [
            'total'      => $tablero->sum('total'),
            'confirmados'=> $tablero->sum('confirmados'),
            'requieren'  => $tablero->sum('requieren'),
            'pendientes' => $tablero->sum('pendientes'),
            'pct'        => $tablero->sum('total') > 0
                                ? round(($tablero->sum('confirmados') / $tablero->sum('total')) * 100)
                                : 0,
        ];

        $filtro = in_array($request->input('filtro'), ['pendiente', 'confirmado', 'requiere_actualizacion'])
            ? $request->input('filtro')
            : null;

        if ($filtro) {
            $tablero = $tablero->map(function ($row) use ($filtro) {
                $row->filas = match($filtro) {
                    'pendiente'              => $row->filas->filter(fn($f) => !$f->confirmacion || $f->confirmacion->estado === 'pendiente' || $f->movCambio),
                    'confirmado'             => $row->filas->filter(fn($f) => $f->confirmacion?->estado === 'confirmado' && !$f->movCambio),
                    'requiere_actualizacion' => $row->filas->filter(fn($f) => $f->confirmacion?->estado === 'requiere_actualizacion'),
                };
                $row->filas = $row->filas->values();
                return $row;
            })->filter(fn($row) => $row->filas->isNotEmpty())->values();
        }

        $grupos = GrupoGerencia::orderBy('nombre_grupo_gerencia')->get();

        return view('contratos.confirmaciones.tablero', [
            'tablero'     => $tablero,
            'stats'       => $stats,
            'filtro'      => $filtro,
            'periodo'     => $periodo,
            'periodos'    => $this->periodosDisponibles(),
            'esRrhh'      => $this->esAdminORrhh(),
            'puedeActuar' => auth()->user()->hasRole('Administrador'),
            'grupos'      => $grupos,
        ]);
    }

    // ── POST intervención RRHH ─────────────────────────────────────────────────

    public function intervenirRrhh(Request $request)
    {
        abort_unless($this->esAdminORrhh(), 403);

        $data = $request->validate([
            'periodo'       => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'contrato_ids'  => ['required', 'array', 'min:1'],
            'contrato_ids.*'=> ['integer'],
            'estado'        => ['required', 'in:confirmado,requiere_actualizacion'],
            'comentario'    => ['nullable', 'string', 'max:500'],
        ]);

        [$inicio, $fin] = $this->rangoMes($data['periodo']);

        $contratos = Contrato::withoutGlobalScope(AlcanceUsuarioScope::class)
            ->whereIn('id', $data['contrato_ids'])
            ->with(['movimientos' => function ($q) use ($inicio, $fin) {
                $q->where('inicio', '<=', $fin->toDateString())
                  ->where(function ($m) use ($inicio) {
                      $m->whereNull('fin')
                        ->orWhere('fin', '>=', $inicio->toDateString());
                  })
                  ->with(['planilla', 'centroCosto', 'familia', 'cargo', 'condicion'])
                  ->orderByDesc('inicio');
            }])
            ->get()
            ->keyBy('id');

        $existentes = ControlConfirmacion::where('periodo', $data['periodo'])
            ->whereIn('contrato_id', $data['contrato_ids'])
            ->get()->keyBy('contrato_id');

        DB::transaction(function () use ($data, $contratos, $existentes) {
            foreach ($data['contrato_ids'] as $contratoId) {
                $contrato = $contratos->get($contratoId);
                if (!$contrato) continue;

                $mov = $contrato->movimientos->first();
                if (!$mov) continue;

                if ($data['estado'] === ControlConfirmacion::ESTADO_CONFIRMADO) {
                    $snapshot = array_merge(
                        ['periodo' => $data['periodo']],
                        ControlConfirmacion::buildSnapshot($mov)
                    );
                } else {
                    $snapshot = $existentes->has($contratoId)
                        ? ['periodo' => $data['periodo']]
                        : array_merge(['periodo' => $data['periodo']], ControlConfirmacion::buildSnapshot($mov));
                }

                $this->upsertConfirmacion(
                    $snapshot,
                    (int) $contratoId,
                    $contrato->persona_id,
                    $data['comentario'] ?? null,
                    $data['estado'],
                    ControlConfirmacion::ORIGEN_RRHH
                );
            }
        });

        return back()->with('success', 'Intervención de RRHH registrada correctamente.');
    }

    public function asignarGrupo(Request $request)
    {
        abort_unless($this->esAdminORrhh(), 403);

        $conn          = config('database.default');
        $periodosValidos = array_column($this->periodosDisponibles(), 'value');

        $data = $request->validate([
            'contrato_id'       => ['required', 'integer'],
            'periodo'           => ['required', 'regex:/^\d{4}-\d{2}$/', 'in:' . implode(',', $periodosValidos)],
            'grupo_gerencia_id' => ['nullable', 'integer', "exists:{$conn}.dbo.dim_grupo_gerencia,id"],
        ]);

        $conf = ControlConfirmacion::firstOrNew([
            'periodo'     => $data['periodo'],
            'contrato_id' => $data['contrato_id'],
        ]);

        if (!$conf->exists) {
            [$inicio, $fin] = $this->rangoMes($data['periodo']);

            $contrato = Contrato::withoutGlobalScope(AlcanceUsuarioScope::class)
                ->with(['movimientos' => function ($q) use ($inicio, $fin) {
                    $q->where('inicio', '<=', $fin->toDateString())
                      ->where(fn($m) => $m->whereNull('fin')->orWhere('fin', '>=', $inicio->toDateString()))
                      ->orderByDesc('inicio');
                }])
                ->findOrFail($data['contrato_id']);

            $mov = $contrato->movimientos->first();

            if (!$mov) {
                return response()->json(['ok' => false, 'message' => 'Sin movimiento activo en el periodo'], 422);
            }

            $conf->persona_id    = $contrato->persona_id;
            $conf->movimiento_id = $mov->id;
            $conf->estado        = ControlConfirmacion::ESTADO_PENDIENTE;
        }

        $conf->grupo_gerencia_id = $data['grupo_gerencia_id'];
        $conf->save();

        return response()->json(['ok' => true]);
    }
}
