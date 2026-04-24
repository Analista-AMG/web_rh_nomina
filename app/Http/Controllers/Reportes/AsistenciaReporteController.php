<?php

namespace App\Http\Controllers\Reportes;

use App\Helpers\PeriodoCalendario;
use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\Contrato;
use App\Models\Persona;
use App\Models\Scopes\AlcanceUsuarioScope;
use App\Models\User;
use App\Models\UserAsignacion;
use App\Services\JerarquiaService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AsistenciaReporteController extends Controller
{
    public function __construct(protected JerarquiaService $jerarquia) {}

    public function index(Request $request)
    {
        $user    = Auth::user();
        $esAdmin = $user->hasRole('Administrador');
        $hoy     = Carbon::today();
        $hoyStr  = $hoy->toDateString();

        $pagos = PeriodoCalendario::listarDesc(24);

        $pagoId = $request->input('pago_id')
            ?: ($pagos->first(fn($p) => $p->inicio->format('Y-m-d') <= $hoyStr && $p->fin->format('Y-m-d') >= $hoyStr)?->id
                ?? $pagos->first()?->id);
        $pagoSeleccionado = $pagoId ? $pagos->firstWhere('id', $pagoId) : null;

        $filas       = collect();
        $diasPeriodo = collect();

        if (!$pagoSeleccionado) {
            return view('reportes.asistencia.index', compact('pagos', 'pagoSeleccionado', 'filas', 'diasPeriodo'));
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
            ->whereHas('persona', fn($q) => $q->withoutGlobalScope(AlcanceUsuarioScope::class))
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
            return view('reportes.asistencia.index', compact('pagos', 'pagoSeleccionado', 'filas', 'diasPeriodo', 'esAdmin'));
        }

        [$personaToUser, $userToPersona] = $this->resolverPersonaUserMap($allPersonaIds);
        $allUserIds = array_values($personaToUser);

        // 3. Asistencias registradas en el período
        $asistenciasSet = Asistencia::whereIn('contrato_id', $contratos->pluck('id'))
            ->whereBetween('fecha', [$inicioStr, $finStr])
            ->whereNotNull('item_asistencia_id')
            ->get()
            ->keyBy(fn($a) => $a->contrato_id . '_' . $a->fecha->format('Y-m-d'));

        // 4. Asignaciones del período para personas visibles
        $asignaciones = UserAsignacion::whereIn('user_id', $allUserIds)
            ->where('estado', UserAsignacion::ESTADO_APROBADO)
            ->where('fecha_inicio', '<=', $finStr)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicioStr))
            ->with('campana')
            ->get();

        // 5. Responsables: user_ids visibles que aparecen como superior_id de alguien visible
        $responsableUserIds = $asignaciones
            ->pluck('superior_id')
            ->filter()
            ->unique()
            ->intersect($allUserIds)   // el responsable también debe ser visible
            ->values()
            ->toArray();

        if (empty($responsableUserIds)) {
            return view('reportes.asistencia.index', compact('pagos', 'pagoSeleccionado', 'filas', 'diasPeriodo', 'esAdmin'));
        }

        // 6. Asignaciones propias de los responsables (rol, campaña, puede_editar_propia)
        $asigPropias = UserAsignacion::whereIn('user_id', $responsableUserIds)
            ->where('estado', UserAsignacion::ESTADO_APROBADO)
            ->where('fecha_inicio', '<=', $finStr)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicioStr))
            ->with('campana')
            ->get()
            ->groupBy('user_id');

        // 7. Subordinados directos por responsable (del universo visible)
        $subPorResponsable = $asignaciones
            ->whereIn('superior_id', $responsableUserIds)
            ->groupBy('superior_id');

        // 8. Nombres de todos los usuarios relevantes
        $userNames = User::whereIn('id', array_unique(array_merge($responsableUserIds, $allUserIds)))
            ->pluck('name', 'id');

        // 9. Construir filas del reporte
        foreach ($responsableUserIds as $supUserId) {
            $supAsigs = $asigPropias[$supUserId] ?? collect();

            // Rol más alto en el período
            $rolNivel  = $supAsigs->map(fn($a) => UserAsignacion::NIVEL_ROL[$a->rol] ?? 0)->max() ?? 0;
            $rolNombre = collect(UserAsignacion::NIVEL_ROL)->search($rolNivel) ?: '—';

            // Campaña del rol más alto
            $supCampana = $supAsigs
                ->sortByDesc(fn($a) => UserAsignacion::NIVEL_ROL[$a->rol] ?? 0)
                ->first()?->campana?->nombre ?? '—';

            // ¿Registra su propia asistencia?
            $puedePropia  = $supAsigs->contains('puede_editar_propia_asistencia', true);
            $supPersonaId = $userToPersona[$supUserId] ?? null;

            // Directos en el universo visible
            $directosUserIds = ($subPorResponsable[$supUserId] ?? collect())
                ->pluck('user_id')->unique()->toArray();

            $grupoPersonaIds = array_values(array_filter(
                array_map(fn($uid) => $userToPersona[$uid] ?? null, $directosUserIds)
            ));

            if ($personaIds !== null) {
                $grupoPersonaIds = array_values(array_intersect($grupoPersonaIds, $personaIds));
            }

            // Incluirse a sí mismo si aplica
            if ($puedePropia && $supPersonaId) {
                $grupoPersonaIds = array_values(array_unique(array_merge([$supPersonaId], $grupoPersonaIds)));
            }

            if (empty($grupoPersonaIds)) continue;

            // Calcular cobertura del grupo
            $totalEsperado = 0;
            $totalLlenado  = 0;
            $detalleColabs = [];

            foreach ($grupoPersonaIds as $personaId) {
                $persona         = $contratosPorPersona[$personaId]?->first()?->persona;
                $contratosPersona = ($contratosPorPersona[$personaId] ?? collect())->sortBy('inicio_contrato');
                $esperadoColab   = 0;
                $llenadoColab    = 0;
                $fechasVacias    = [];

                foreach ($diasPeriodo as $fecha) {
                    $fStr = $fecha->format('Y-m-d');
                    foreach ($contratosPersona as $c) {
                        $inicioC     = Carbon::parse($c->inicio_contrato);
                        $finEfectivo = $c->fecha_renuncia
                            ? Carbon::parse($c->fecha_renuncia)
                            : ($c->fin_contrato ? Carbon::parse($c->fin_contrato) : null);

                        if ($fecha->gte($inicioC) && (!$finEfectivo || $fecha->lte($finEfectivo))) {
                            $esperadoColab++;
                            if (isset($asistenciasSet[$c->id . '_' . $fStr])) {
                                $llenadoColab++;
                            } else {
                                $fechasVacias[] = $fStr;
                            }
                            break;
                        }
                    }
                }

                $totalEsperado += $esperadoColab;
                $totalLlenado  += $llenadoColab;

                if ($esperadoColab > 0 && $llenadoColab < $esperadoColab) {
                    $nombre = $persona
                        ? trim(($persona->apellido_paterno ?? '') . ' ' . ($persona->apellido_materno ?? '') . ' ' . (explode(' ', trim($persona->nombres ?? ''))[0] ?? ''))
                        : ($userNames[$supUserId] ?? '—');

                    $detalleColabs[] = [
                        'nombre'    => $nombre,
                        'vacios'    => $esperadoColab - $llenadoColab,
                        'esperados' => $esperadoColab,
                        'fechas'    => $fechasVacias,
                        'es_propio' => ($personaId === $supPersonaId),
                    ];
                }
            }

            if ($totalEsperado === 0) continue;

            usort($detalleColabs, fn($a, $b) => $b['vacios'] <=> $a['vacios']);

            $filas->push([
                'supervisor'    => $userNames[$supUserId] ?? '—',
                'rol'           => $rolNombre,
                'campana'       => $supCampana,
                'colaboradores' => count($grupoPersonaIds),
                'esperados'     => $totalEsperado,
                'llenados'      => $totalLlenado,
                'vacios'        => $totalEsperado - $totalLlenado,
                'cobertura'     => round($totalLlenado / $totalEsperado * 100),
                'detalle'       => $detalleColabs,
            ]);
        }

        $filas = $filas->sortByDesc('vacios')->values();

        return view('reportes.asistencia.index', compact(
            'pagos', 'pagoSeleccionado', 'filas', 'diasPeriodo', 'esAdmin'
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
