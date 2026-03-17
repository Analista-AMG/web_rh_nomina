<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\Campana;
use App\Models\Contrato;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\Scopes\AlcanceUsuarioScope;
use App\Models\User;
use App\Models\UserAsignacion;
use App\Services\JerarquiaService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AsistenciaGerenciaController extends Controller
{
    public function __construct(protected JerarquiaService $jerarquia) {}

    public function index(Request $request)
    {
        $user    = Auth::user();
        $esAdmin = $user->hasRole('Administrador');
        $hoy     = Carbon::today();
        $hoyStr  = $hoy->toDateString();

        $pagos = Pago::orderBy('periodo', 'desc')->orderBy('quincena', 'desc')->get(['id', 'periodo', 'quincena', 'inicio', 'fin']);

        $pagoId = $request->input('pago_id')
            ?: ($pagos->first(fn($p) => $p->inicio->format('Y-m-d') <= $hoyStr && $p->fin->format('Y-m-d') >= $hoyStr)?->id
                ?? $pagos->first()?->id);
        $pagoSeleccionado = $pagoId ? $pagos->firstWhere('id', $pagoId) : null;

        $filas       = collect();
        $diasPeriodo = collect();

        if (!$pagoSeleccionado) {
            return view('reportes.asistencia-gerencia.index', compact('pagos', 'pagoSeleccionado', 'filas', 'diasPeriodo'));
        }

        $inicioStr = $pagoSeleccionado->inicio->format('Y-m-d');
        $finStr    = min($pagoSeleccionado->fin->format('Y-m-d'), $hoyStr);

        foreach (CarbonPeriod::create($inicioStr, $finStr) as $f) {
            $diasPeriodo->push($f->copy());
        }

        // 1. Universo de personas visibles según jerarquía
        $personaIds = $esAdmin
            ? null
            : $this->jerarquia->personaIdsVisiblesEnPeriodo($user, $inicioStr, $finStr);

        // 2. Contratos vigentes en el período
        $q = Contrato::withoutGlobalScope(AlcanceUsuarioScope::class)
            ->with(['persona' => fn($q) => $q->withoutGlobalScope(AlcanceUsuarioScope::class)])
            ->where('inicio_contrato', '<=', $finStr)
            ->where(function ($q) use ($inicioStr) {
                $q->whereNull('fin_contrato')->whereNull('fecha_renuncia')
                  ->orWhereRaw('COALESCE(fecha_renuncia, fin_contrato) >= ?', [$inicioStr]);
            });

        if ($personaIds !== null) {
            $q->whereIn('persona_id', $personaIds);
        }

        $contratos           = $q->get();
        $contratosPorPersona = $contratos->groupBy('persona_id');
        $allPersonaIds       = $contratos->pluck('persona_id')->unique()->map(fn($id) => (int) $id)->toArray();

        if (empty($allPersonaIds)) {
            return view('reportes.asistencia-gerencia.index', compact('pagos', 'pagoSeleccionado', 'filas', 'diasPeriodo'));
        }

        [$personaToUser, $userToPersona] = $this->resolverPersonaUserMap($allPersonaIds);
        $allUserIds = array_values($personaToUser);

        // 3. Asistencias registradas en el período
        $asistenciasSet = Asistencia::whereIn('contrato_id', $contratos->pluck('id'))
            ->whereBetween('fecha', [$inicioStr, $finStr])
            ->whereNotNull('item_asistencia_id')
            ->get()
            ->keyBy(fn($a) => $a->contrato_id . '_' . $a->fecha->format('Y-m-d'));

        // 4. Asignaciones del período para usuarios visibles
        $asignaciones = UserAsignacion::whereIn('user_id', $allUserIds)
            ->where('estado', UserAsignacion::ESTADO_APROBADO)
            ->where('fecha_inicio', '<=', $finStr)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicioStr))
            ->with('campana')
            ->get();

        // 5. Cargar campañas con su padre
        $campanaIds = $asignaciones->pluck('campana_id')->filter()->unique()->toArray();
        if (empty($campanaIds)) {
            return view('reportes.asistencia-gerencia.index', compact('pagos', 'pagoSeleccionado', 'filas', 'diasPeriodo'));
        }
        $campanas = Campana::whereIn('id', $campanaIds)->with('padre')->get()->keyBy('id');

        // Nombres de todos los usuarios para el detalle
        $userNames = User::whereIn('id', $allUserIds)->pluck('name', 'id');

        // 6. Construir fila por campaña
        $asigPorCampana = $asignaciones->groupBy('campana_id');

        foreach ($asigPorCampana as $campanaId => $asigsCampana) {
            $campana = $campanas[$campanaId] ?? null;
            if (!$campana) continue;

            // Personas en esta campaña
            $userIdsCampana    = $asigsCampana->pluck('user_id')->unique()->toArray();
            $personaIdsCampana = array_values(array_filter(
                array_map(fn($uid) => $userToPersona[$uid] ?? null, $userIdsCampana)
            ));

            if ($personaIds !== null) {
                $personaIdsCampana = array_values(array_intersect($personaIdsCampana, $personaIds));
            }

            if (empty($personaIdsCampana)) continue;

            // Cobertura total de la campaña
            $totalEsperado = 0;
            $totalLlenado  = 0;

            foreach ($personaIdsCampana as $personaId) {
                $contratosPersona = ($contratosPorPersona[$personaId] ?? collect())->sortBy('inicio_contrato');

                foreach ($diasPeriodo as $fecha) {
                    $fStr = $fecha->format('Y-m-d');
                    foreach ($contratosPersona as $c) {
                        $inicioC     = Carbon::parse($c->inicio_contrato);
                        $finEfectivo = $c->fecha_renuncia
                            ? Carbon::parse($c->fecha_renuncia)
                            : ($c->fin_contrato ? Carbon::parse($c->fin_contrato) : null);

                        if ($fecha->gte($inicioC) && (!$finEfectivo || $fecha->lte($finEfectivo))) {
                            $totalEsperado++;
                            if (isset($asistenciasSet[$c->id . '_' . $fStr])) {
                                $totalLlenado++;
                            }
                            break;
                        }
                    }
                }
            }

            if ($totalEsperado === 0) continue;

            // 7. Detalle: responsables dentro de la campaña con días vacíos
            $responsableIds = $asigsCampana
                ->pluck('superior_id')
                ->filter()
                ->unique()
                ->intersect($userIdsCampana)
                ->values()
                ->toArray();

            $asigPropiasCampana = $asignaciones->whereIn('user_id', $responsableIds)->groupBy('user_id');
            $subPorResp         = $asigsCampana->whereIn('superior_id', $responsableIds)->groupBy('superior_id');

            $detalleResponsables = [];

            foreach ($responsableIds as $supUserId) {
                $directosUserIds = ($subPorResp[$supUserId] ?? collect())
                    ->pluck('user_id')->unique()->toArray();

                $puedePropia  = ($asigPropiasCampana[$supUserId] ?? collect())->contains('puede_editar_propia_asistencia', true);
                $supPersonaId = $userToPersona[$supUserId] ?? null;

                $grupoPersonaIds = array_values(array_filter(
                    array_map(fn($uid) => $userToPersona[$uid] ?? null, $directosUserIds)
                ));

                if ($puedePropia && $supPersonaId) {
                    $grupoPersonaIds = array_values(array_unique(array_merge([$supPersonaId], $grupoPersonaIds)));
                }

                $grupoPersonaIds = array_values(array_intersect($grupoPersonaIds, $personaIdsCampana));

                if (empty($grupoPersonaIds)) continue;

                $espResp  = 0;
                $llenResp = 0;

                foreach ($grupoPersonaIds as $personaId) {
                    $contratosPersona = ($contratosPorPersona[$personaId] ?? collect())->sortBy('inicio_contrato');

                    foreach ($diasPeriodo as $fecha) {
                        $fStr = $fecha->format('Y-m-d');
                        foreach ($contratosPersona as $c) {
                            $inicioC     = Carbon::parse($c->inicio_contrato);
                            $finEfectivo = $c->fecha_renuncia
                                ? Carbon::parse($c->fecha_renuncia)
                                : ($c->fin_contrato ? Carbon::parse($c->fin_contrato) : null);

                            if ($fecha->gte($inicioC) && (!$finEfectivo || $fecha->lte($finEfectivo))) {
                                $espResp++;
                                if (isset($asistenciasSet[$c->id . '_' . $fStr])) {
                                    $llenResp++;
                                }
                                break;
                            }
                        }
                    }
                }

                if ($espResp === 0) continue;

                // Obtener rol del responsable en esta campaña
                $rolNivel  = ($asigPropiasCampana[$supUserId] ?? collect())->map(fn($a) => UserAsignacion::NIVEL_ROL[$a->rol] ?? 0)->max() ?? 0;
                $rolNombre = collect(UserAsignacion::NIVEL_ROL)->search($rolNivel) ?: '—';

                $detalleResponsables[] = [
                    'nombre'       => $userNames[$supUserId] ?? '—',
                    'rol'          => $rolNombre,
                    'colaboradores'=> count($grupoPersonaIds),
                    'vacios'       => $espResp - $llenResp,
                    'esperados'    => $espResp,
                    'cobertura'    => round($llenResp / $espResp * 100),
                ];
            }

            usort($detalleResponsables, fn($a, $b) => $b['vacios'] <=> $a['vacios']);

            $filas->push([
                'campana'       => $campana->nombre,
                'padre'         => $campana->padre?->nombre,
                'colaboradores' => count($personaIdsCampana),
                'responsables'  => count($responsableIds),
                'esperados'     => $totalEsperado,
                'llenados'      => $totalLlenado,
                'vacios'        => $totalEsperado - $totalLlenado,
                'cobertura'     => round($totalLlenado / $totalEsperado * 100),
                'detalle'       => $detalleResponsables,
            ]);
        }

        $filas = $filas->sortByDesc('vacios')->values();

        return view('reportes.asistencia-gerencia.index', compact(
            'pagos', 'pagoSeleccionado', 'filas', 'diasPeriodo'
        ));
    }

    private function resolverPersonaUserMap(array $personaIds): array
    {
        if (empty($personaIds)) return [[], []];

        $docs = Persona::withoutGlobalScope(AlcanceUsuarioScope::class)
            ->whereIn('id', $personaIds)
            ->whereNotNull('numero_documento')
            ->pluck('numero_documento', 'id');

        if ($docs->isEmpty()) return [[], []];

        $users = User::whereIn('numero_documento', $docs->values())->pluck('id', 'numero_documento');

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
}
