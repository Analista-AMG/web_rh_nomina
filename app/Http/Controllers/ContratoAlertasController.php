<?php

namespace App\Http\Controllers;

use App\Models\CentroCosto;
use App\Models\Contrato;
use App\Models\Familia;
use App\Models\Planilla;
use App\Services\JerarquiaService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ContratoAlertasController extends Controller
{
    public function __construct(protected JerarquiaService $jerarquia) {}

    public function index(Request $request)
    {
        $user   = auth()->user();
        $hoy    = Carbon::today();
        $hace15 = $hoy->copy()->subDays(15)->toDateString();
        $manana = $hoy->copy()->addDay()->toDateString();
        $en15   = $hoy->copy()->addDays(15)->toDateString();
        $hoyStr = $hoy->toDateString();

        $tab = $request->input('tab', 'vencidos');

        // Filtros activos
        $nombre        = trim($request->input('nombre', ''));
        $empresa       = $request->input('empresa');
        $planillaId    = $request->input('planilla_id');
        $centroCostoId = $request->input('centro_costo_id');
        $familiaId     = $request->input('familia_id');

        // ── Subquery "sin renovación posterior" ──────────────────────────────
        $noRenovado = fn($sq) => $sq
            ->from('nomina.fact_contratos as c2')
            ->whereColumn('c2.persona_id', 'nomina.fact_contratos.persona_id')
            ->whereRaw('c2.inicio_contrato > nomina.fact_contratos.fin_contrato');

        // ── Condiciones del tab activo (mismas para tabla Y dropdowns) ────────
        $applyTab = function ($q) use ($tab, $hace15, $hoyStr, $manana, $en15, $noRenovado) {
            return match ($tab) {
                'bajas' => $q
                    ->whereBetween('fecha_renuncia', [$hace15, $hoyStr]),
                'por_vencer' => $q
                    ->whereBetween('fin_contrato', [$manana, $en15])
                    ->whereNull('fecha_renuncia')
                    ->whereNotExists($noRenovado),
                default => $q
                    ->whereBetween('fin_contrato', [$hace15, $hoyStr])
                    ->whereNull('fecha_renuncia')
                    ->whereNotExists($noRenovado),
            };
        };

        // ── Filtros de usuario (todos activos) ────────────────────────────────
        $applyFiltros = fn($q) => $q
            ->when($empresa,       fn($q) => $q->whereHas('planilla', fn($p) => $p->where('nombre_empresa', $empresa)))
            ->when($planillaId,    fn($q) => $q->where('planilla_id',     $planillaId))
            ->when($centroCostoId, fn($q) => $q->where('centro_costo_id', $centroCostoId))
            ->when($familiaId,     fn($q) => $q->where('familia_id',      $familiaId))
            ->when($nombre !== '', fn($q) => $q->whereHas('persona', fn($p) => $p
                ->whereRaw("CONCAT(apellido_paterno, ' ', ISNULL(apellido_materno,''), ' ', nombres) LIKE ?", ["%{$nombre}%"])
            ));

        // ── Base para resultados ──────────────────────────────────────────────
        $base = fn() => $applyFiltros(
            Contrato::with(['persona', 'centroCosto', 'familia', 'planilla'])
        );

        // ── Base para opciones de dropdown ────────────────────────────────────
        // Aplica: condiciones del tab activo + todos los filtros EXCEPTO el propio
        $opts = fn(array $skip = []) => $applyTab(Contrato::query())
            ->when(!in_array('empresa', $skip)  && $empresa,       fn($q) => $q->whereHas('planilla', fn($p) => $p->where('nombre_empresa', $empresa)))
            ->when(!in_array('planilla', $skip) && $planillaId,    fn($q) => $q->where('planilla_id',     $planillaId))
            ->when(!in_array('centro', $skip)   && $centroCostoId, fn($q) => $q->where('centro_costo_id', $centroCostoId))
            ->when(!in_array('familia', $skip)  && $familiaId,     fn($q) => $q->where('familia_id',      $familiaId))
            ->when(!in_array('nombre', $skip)   && $nombre !== '', fn($q) => $q->whereHas('persona', fn($p) => $p
                ->whereRaw("CONCAT(apellido_paterno, ' ', ISNULL(apellido_materno,''), ' ', nombres) LIKE ?", ["%{$nombre}%"])
            ));

        // ── Conteos para KPI cards (sin filtros de usuario, sin tab) ─────────
        $baseKpi = fn() => Contrato::query();

        $countVencidos = $baseKpi()
            ->whereBetween('fin_contrato', [$hace15, $hoyStr])
            ->whereNull('fecha_renuncia')
            ->whereNotExists($noRenovado)
            ->count();

        $countBajas = $baseKpi()
            ->whereBetween('fecha_renuncia', [$hace15, $hoyStr])
            ->count();

        $countPorVencer = $baseKpi()
            ->whereBetween('fin_contrato', [$manana, $en15])
            ->whereNull('fecha_renuncia')
            ->whereNotExists($noRenovado)
            ->count();

        // ── Datos paginados del tab activo ────────────────────────────────────
        $filas = $applyTab($base())
            ->paginate(7)
            ->appends($request->all());

        // ── Dropdowns dinámicos ───────────────────────────────────────────────
        $planillaIdsEmpresa  = $opts(['empresa'])->pluck('planilla_id')->filter()->unique();
        $empresas = Planilla::whereIn('id', $planillaIdsEmpresa)
            ->select('nombre_empresa')->distinct()->orderBy('nombre_empresa')
            ->pluck('nombre_empresa');

        $planillaIdsPlanilla = $opts(['planilla'])->pluck('planilla_id')->filter()->unique();
        $planillas = Planilla::whereIn('id', $planillaIdsPlanilla)
            ->select('id', 'nombre_planilla')->orderBy('nombre_planilla')->get();

        $centroCostoIds = $opts(['centro'])->pluck('centro_costo_id')->filter()->unique();
        $centrosCosto = CentroCosto::whereIn('id', $centroCostoIds)
            ->select('id', 'nombre_centro_costo')->orderBy('nombre_centro_costo')->get();

        $familiaIds = $opts(['familia'])->pluck('familia_id')->filter()->unique();
        $familias = Familia::whereIn('id', $familiaIds)
            ->select('id', 'nombre_familia')->orderBy('nombre_familia')->get();

        return view('contratos.alertas', compact(
            'filas', 'hoy', 'tab',
            'countVencidos', 'countBajas', 'countPorVencer',
            'empresas', 'planillas', 'centrosCosto', 'familias',
            'nombre', 'empresa', 'planillaId', 'centroCostoId', 'familiaId'
        ));
    }
}
