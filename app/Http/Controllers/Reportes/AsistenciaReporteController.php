<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\Calendario;
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

class AsistenciaReporteController extends Controller
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
            return view('reportes.asistencia.index', compact('pagos', 'pagoSeleccionado', 'filas', 'diasPeriodo'));
        }

        $inicioStr = $pagoSeleccionado->inicio->format('Y-m-d');
        $finStr    = min($pagoSeleccionado->fin->format('Y-m-d'), $hoyStr);

        // Todos los días del período hasta hoy
        $diasPeriodo = collect();
        foreach (CarbonPeriod::create($inicioStr, $finStr) as $f) {
            $diasPeriodo->push($f->copy());
        }

        // Personas visibles en el período
        $personaIds = $esAdmin
            ? null
            : $this->jerarquia->personaIdsVisiblesEnPeriodo($user, $inicioStr, $finStr);

        // Contratos vigentes
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
            return view('reportes.asistencia.index', compact('pagos', 'pagoSeleccionado', 'filas', 'diasPeriodo', 'esAdmin'));
        }

        [$personaToUser, $userToPersona] = $this->resolverPersonaUserMap($allPersonaIds);

        // Asistencias con item registrado
        $asistenciasSet = Asistencia::whereIn('contrato_id', $contratos->pluck('id'))
            ->whereBetween('fecha', [$inicioStr, $finStr])
            ->whereNotNull('item_asistencia_id')
            ->get()
            ->keyBy(fn($a) => $a->contrato_id . '_' . $a->fecha->format('Y-m-d'));

        // Supervisores visibles en el período
        $supervisorAsigs = UserAsignacion::whereIn('user_id', array_values($personaToUser))
            ->where('rol', UserAsignacion::ROL_SUPERVISOR)
            ->where('estado', UserAsignacion::ESTADO_APROBADO)
            ->where('fecha_inicio', '<=', $finStr)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicioStr))
            ->with('campana')
            ->get()
            ->unique('user_id');

        $supervisorUserIds = $supervisorAsigs->pluck('user_id')->unique()->toArray();

        $userNames  = User::whereIn('id', $supervisorUserIds)->pluck('name', 'id');

        // Colaboradores de todos los supervisores en una sola query
        $colabPorSuperior = UserAsignacion::whereIn('superior_id', $supervisorUserIds)
            ->where('estado', UserAsignacion::ESTADO_APROBADO)
            ->where('fecha_inicio', '<=', $finStr)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicioStr))
            ->get(['user_id', 'superior_id'])
            ->groupBy('superior_id');

        // Construir filas del reporte
        foreach ($supervisorAsigs as $supAsig) {
            $supUserId  = (int) $supAsig->user_id;
            $supNombre  = $userNames[$supUserId] ?? '—';
            $supCampana = $supAsig->campana->nombre ?? '—';

            $colabUserIds    = ($colabPorSuperior[$supUserId] ?? collect())->pluck('user_id')->unique()->toArray();
            $colabPersonaIds = array_values(array_filter(
                array_map(fn($uid) => $userToPersona[$uid] ?? null, $colabUserIds)
            ));

            if ($personaIds !== null) {
                $colabPersonaIds = array_values(array_intersect($colabPersonaIds, $personaIds));
            }

            if (empty($colabPersonaIds)) continue;

            $totalEsperado = 0;
            $totalLlenado  = 0;
            $detalleColabs = [];

            foreach ($colabPersonaIds as $personaId) {
                $persona          = $contratosPorPersona[$personaId]?->first()?->persona;
                $contratosPersona = $contratosPorPersona[$personaId] ?? collect();
                $esperadoColab    = 0;
                $llenadoColab     = 0;
                $fechasVacias     = [];

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
                        : '—';
                    $detalleColabs[] = [
                        'nombre'    => $nombre,
                        'vacios'    => $esperadoColab - $llenadoColab,
                        'esperados' => $esperadoColab,
                        'fechas'    => $fechasVacias,
                    ];
                }
            }

            if ($totalEsperado === 0) continue;

            usort($detalleColabs, fn($a, $b) => $b['vacios'] <=> $a['vacios']);

            $filas->push([
                'supervisor'    => $supNombre,
                'campana'       => $supCampana,
                'colaboradores' => count($colabPersonaIds),
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
