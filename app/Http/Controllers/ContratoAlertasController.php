<?php

namespace App\Http\Controllers;

use App\Models\CentroCosto;
use App\Models\Contrato;
use App\Models\Familia;
use App\Models\Planilla;
use App\Services\JerarquiaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

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

    public function exportar(Request $request)
    {
        $hoy    = Carbon::today();
        $hace15 = $hoy->copy()->subDays(15)->toDateString();
        $manana = $hoy->copy()->addDay()->toDateString();
        $en15   = $hoy->copy()->addDays(15)->toDateString();
        $hoyStr = $hoy->toDateString();

        $tab           = $request->input('tab', 'vencidos');
        $nombre        = trim($request->input('nombre', ''));
        $empresa       = $request->input('empresa');
        $planillaId    = $request->input('planilla_id');
        $centroCostoId = $request->input('centro_costo_id');
        $familiaId     = $request->input('familia_id');

        $noRenovado = fn($sq) => $sq
            ->from('nomina.fact_contratos as c2')
            ->whereColumn('c2.persona_id', 'nomina.fact_contratos.persona_id')
            ->whereRaw('c2.inicio_contrato > nomina.fact_contratos.fin_contrato');

        $query = Contrato::with(['persona', 'centroCosto', 'familia', 'planilla'])
            ->when($empresa,       fn($q) => $q->whereHas('planilla', fn($p) => $p->where('nombre_empresa', $empresa)))
            ->when($planillaId,    fn($q) => $q->where('planilla_id',     $planillaId))
            ->when($centroCostoId, fn($q) => $q->where('centro_costo_id', $centroCostoId))
            ->when($familiaId,     fn($q) => $q->where('familia_id',      $familiaId))
            ->when($nombre !== '', fn($q) => $q->whereHas('persona', fn($p) => $p
                ->whereRaw("CONCAT(apellido_paterno, ' ', ISNULL(apellido_materno,''), ' ', nombres) LIKE ?", ["%{$nombre}%"])
            ));

        $query = match ($tab) {
            'bajas'      => $query->whereBetween('fecha_renuncia', [$hace15, $hoyStr]),
            'por_vencer' => $query->whereBetween('fin_contrato', [$manana, $en15])
                                  ->whereNull('fecha_renuncia')->whereNotExists($noRenovado),
            default      => $query->whereBetween('fin_contrato', [$hace15, $hoyStr])
                                  ->whereNull('fecha_renuncia')->whereNotExists($noRenovado),
        };

        $registros = $query->get();

        // ── Construir Excel ────────────────────────────────────────────────────
        $esBajas     = $tab === 'bajas';
        $esPorVencer = $tab === 'por_vencer';

        $tabLabels = [
            'vencidos'   => 'Vencidos sin acción',
            'bajas'      => 'Bajas recientes',
            'por_vencer' => 'Por vencer',
        ];
        $tabLabel = $tabLabels[$tab] ?? $tab;

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle($tabLabel);

        // Encabezados
        $headers = [
            'Colaborador', 'N° Documento', 'Empresa', 'Planilla',
            'Centro de Costo', 'Familia', 'Inicio Contrato',
            $esBajas ? 'Fecha Baja' : 'Fin Contrato',
            $esPorVencer ? 'Días para vencer' : ($esBajas ? 'Días desde baja' : 'Días vencido'),
        ];

        $sheet->fromArray([$headers], null, 'A1');

        // Estilo encabezado
        $headerRange = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Filas de datos
        $row = 2;
        foreach ($registros as $c) {
            $persona    = $c->persona;
            $nombre_col = trim(($persona->apellido_paterno ?? '') . ' ' . ($persona->apellido_materno ?? '') . ', ' . ($persona->nombres ?? ''));
            $fechaRef   = $esBajas ? $c->fecha_renuncia : $c->fin_contrato;
            $diasDiff   = $hoy->diffInDays($fechaRef, false);
            $diasStr    = $esPorVencer
                ? 'en ' . abs((int) $diasDiff) . ' días'
                : (abs((int) $diasDiff) . ' días');

            $sheet->fromArray([[
                $nombre_col,
                $persona->numero_documento ?? '',
                $c->planilla?->nombre_empresa ?? '',
                $c->planilla?->nombre_planilla ?? '',
                $c->centroCosto?->nombre_centro_costo ?? '',
                $c->familia?->nombre_familia ?? '',
                $c->inicio_contrato?->format('d/m/Y') ?? '',
                $fechaRef?->format('d/m/Y') ?? '',
                $diasStr,
            ]], null, "A{$row}");
            $row++;
        }

        // Ancho automático de columnas
        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        // Nombre del archivo
        $fileNames = [
            'vencidos'   => 'control-vencidos',
            'bajas'      => 'control-bajas',
            'por_vencer' => 'control-por-vencer',
        ];
        $filename = ($fileNames[$tab] ?? 'control') . '-' . $hoy->format('Ymd') . '.xlsx';

        ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }
}
