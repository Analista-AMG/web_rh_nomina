<?php

namespace App\Http\Controllers;

use App\Models\Adicional;
use App\Models\CentroCosto;
use App\Models\Condicion;
use App\Models\Contrato;
use App\Models\Familia;
use App\Models\Pago;
use App\Models\Planilla;
use App\Services\JerarquiaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AdicionalController extends Controller
{
    public function __construct(protected JerarquiaService $jerarquia) {}

    public function index(Request $request)
    {
        $periodos = Pago::select('periodo')
            ->distinct()
            ->orderBy('periodo', 'desc')
            ->pluck('periodo');

        $planillas    = collect();
        $familias     = collect();
        $centrosCosto = collect();
        $condiciones  = collect();

        $contratos = collect();
        $periodoSeleccionado = $request->input('periodo');
        $tiposActivos = [];
        $kpis = [];
        $totalRegistros = 0;

        if ($periodoSeleccionado) {
            $fechasPeriodo = Pago::where('periodo', $periodoSeleccionado)
                ->selectRaw('MIN(inicio) as inicio_periodo, MAX(fin) as fin_periodo')
                ->first();

            $personaIds = $this->jerarquia->personaIdsVisibles(auth()->user());

            $finEfectivoCondicion = function ($q) use ($fechasPeriodo) {
                $q->where(function ($subQ) {
                    $subQ->whereNull('fin_contrato')->whereNull('fecha_renuncia');
                })->orWhereRaw("
                    CASE
                        WHEN fecha_renuncia IS NOT NULL THEN fecha_renuncia
                        ELSE fin_contrato
                    END >= ?
                ", [$fechasPeriodo->inicio_periodo]);
            };

            $baseContratos = Contrato::where('inicio_contrato', '<=', $fechasPeriodo->fin_periodo)
                ->where($finEfectivoCondicion);
            $this->jerarquia->aplicarFiltroPersonas($baseContratos, $personaIds, 'persona_id');

            // Filtros activos del request
            $filtrosActivos = array_filter([
                'planilla_id'     => $request->planilla_id,
                'centro_costo_id' => $request->centro_costo_id,
                'familia_id'      => $request->familia_id,
                'condicion_id'    => $request->condicion_id,
            ]);

            // Opciones facetadas: cada select usa el base + todos los filtros EXCEPTO el suyo
            $faceta = function (string $columna) use ($baseContratos, $filtrosActivos): \Illuminate\Support\Collection {
                $q = clone $baseContratos;
                foreach ($filtrosActivos as $col => $val) {
                    if ($col !== $columna) $q->where($col, $val);
                }
                return $q->whereNotNull($columna)->distinct()->pluck($columna);
            };

            $planillas    = Planilla::whereIn('id', $faceta('planilla_id'))->orderBy('nombre_planilla')->get();
            $centrosCosto = CentroCosto::whereIn('id', $faceta('centro_costo_id'))->orderBy('nombre_centro_costo')->get();
            $familias     = Familia::whereIn('id', $faceta('familia_id'))->orderBy('nombre_familia')->get();
            $condiciones  = Condicion::whereIn('id', $faceta('condicion_id'))->orderBy('nombre_condicion')->get();

            $query = (clone $baseContratos)->with(['persona', 'centroCosto', 'planilla', 'condicion']);

            foreach ($filtrosActivos as $col => $val) {
                $query->where($col, $val);
            }

            if ($request->filled('numero_documento')) {
                $query->whereHas('persona', fn($q) => $q->where('numero_documento', 'like', '%' . $request->numero_documento . '%'));
            }

            $contratos = $query->get();

            $tiposActivos = Adicional::TIPOS;

            $kpis = array_fill_keys($tiposActivos, 0);

            $adicionales = Adicional::whereIn('contrato_id', $contratos->pluck('id'))
                ->where('periodo', $periodoSeleccionado)
                ->get()
                ->groupBy('contrato_id');

            $contratos = $contratos->map(function ($contrato) use ($adicionales, $tiposActivos) {
                $adicionalesContrato = $adicionales->get($contrato->id, collect());
                $mapped = [];
                foreach ($tiposActivos as $tipo) {
                    $adicional = $adicionalesContrato->firstWhere('tipo_adicional', $tipo);
                    $mapped[$tipo] = [
                        'monto'  => $adicional ? $adicional->monto : '',
                        'motivo' => $adicional ? $adicional->motivo : '',
                    ];
                }
                $contrato->setAttribute('adicionales_periodo', $mapped);
                return $contrato;
            });

            foreach ($contratos as $contrato) {
                foreach ($contrato->adicionales_periodo as $tipo => $data) {
                    $monto = floatval($data['monto']);
                    if ($monto != 0) {
                        $totalRegistros++;
                        $kpis[$tipo] += $monto;
                    }
                }
            }
        }

        return view('adicionales.index', compact(
            'periodos',
            'planillas',
            'familias',
            'centrosCosto',
            'condiciones',
            'contratos',
            'periodoSeleccionado',
            'tiposActivos',
            'kpis',
            'totalRegistros'
        ));
    }

    public function guardar(Request $request): JsonResponse
    {
        $request->validate([
            'contrato_id' => 'required|integer',
            'periodo' => 'required|string|max:10',
            'tipo_adicional' => 'required|string|in:' . implode(',', Adicional::TIPOS),
            'monto' => 'nullable|numeric',
            'motivo' => 'nullable|string|max:500',
        ]);

        $contrato = Contrato::find($request->contrato_id);
        if (!$contrato) {
            return response()->json(['error' => 'Contrato no encontrado'], 404);
        }

        // Bloqueo por período: si el período solicitado no es el actual y ya pasaron 3 días del mes
        $hoy = Carbon::today();
        if ($hoy->day > 3) {
            $pagoActual = Pago::where('inicio', '<=', $hoy->toDateString())
                ->where('fin', '>=', $hoy->toDateString())
                ->first();

            if ($pagoActual && $request->periodo !== $pagoActual->periodo) {
                return response()->json(['error' => 'El período está cerrado. Solo se puede editar el período actual los primeros 3 días del mes.'], 403);
            }
        }

        $monto = $request->monto;

        if ($monto === null || $monto === '' || floatval($monto) == 0) {
            Adicional::where('contrato_id', $request->contrato_id)
                ->where('periodo', $request->periodo)
                ->where('tipo_adicional', $request->tipo_adicional)
                ->delete();

            return response()->json(['success' => true, 'action' => 'deleted']);
        }

        $adicional = Adicional::updateOrCreate(
            [
                'contrato_id' => $request->contrato_id,
                'periodo' => $request->periodo,
                'tipo_adicional' => $request->tipo_adicional,
            ],
            [
                'monto' => $monto,
                'motivo' => $request->motivo,
                'encargado' => auth()->user()->name,
            ]
        );

        return response()->json(['success' => true, 'action' => 'saved', 'id' => $adicional->id]);
    }

    public function importConsolidado(Request $request)
    {
        $request->validate([
            'archivo'          => 'required|file|mimes:xlsx,xls',
            'periodo_esperado' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('archivo')->getPathname());

            if (!$spreadsheet->sheetNameExists('carga_web')) {
                return back()->with('error', 'El archivo no contiene una hoja llamada "carga_web".')->withInput();
            }

            $filas = $spreadsheet->getSheetByName('carga_web')->toArray(null, true, true, false);

            if (count($filas) < 2) {
                return back()->with('error', 'El archivo no contiene datos.')->withInput();
            }

            $headers       = array_map(fn($h) => strtolower(trim((string) $h)), $filas[0]);
            $colContrato   = array_search('contrato_id',    $headers);
            $colTipo       = array_search('tipo_adicional', $headers);
            $colMonto      = array_search('monto',          $headers);
            $colObservacion = array_search('observacion',   $headers);
            $colPeriodo    = array_search('periodo',        $headers);

            if ($colContrato === false || $colTipo === false || $colMonto === false) {
                return back()->with('error', 'Columnas requeridas faltantes: contrato_id, tipo_adicional, monto.')->withInput();
            }

            $periodoEsperado = $request->periodo_esperado;
            $tiposValidos    = Adicional::TIPOS;
            $tiposSoloManual = Adicional::TIPOS_SOLO_MANUAL;
            $encargado       = auth()->user()->name;
            $registros       = [];

            // ── Validación completa antes de insertar ─────────────────────────
            foreach (array_slice($filas, 1) as $i => $fila) {
                $fila_num   = $i + 2;
                $contratoId = $fila[$colContrato] ?? null;
                $tipo       = strtoupper(trim((string) ($fila[$colTipo] ?? '')));
                $monto      = $fila[$colMonto] ?? null;

                // Filas vacías se saltan
                if (!$contratoId && !$tipo && ($monto === null || $monto === '')) {
                    continue;
                }

                if (!$contratoId || !is_numeric($contratoId)) {
                    return back()->with('error', "Fila {$fila_num}: contrato_id inválido o vacío.")->withInput();
                }

                if (!$tipo) {
                    return back()->with('error', "Fila {$fila_num}: tipo_adicional vacío.")->withInput();
                }

                if (in_array($tipo, $tiposSoloManual)) {
                    return back()->with('error', "Fila {$fila_num}: el tipo «{$tipo}» es solo manual y no puede importarse.")->withInput();
                }

                if (!in_array($tipo, $tiposValidos)) {
                    return back()->with('error', "Fila {$fila_num}: tipo_adicional «{$tipo}» desconocido.")->withInput();
                }

                if ($monto === null || $monto === '' || !is_numeric($monto)) {
                    return back()->with('error', "Fila {$fila_num}: monto inválido.")->withInput();
                }

                $periodo = $colPeriodo !== false
                    ? trim((string) ($fila[$colPeriodo] ?? ''))
                    : null;

                if (!$periodo || !preg_match('/^\d{4}-\d{2}$/', $periodo)) {
                    return back()->with('error', "Fila {$fila_num}: periodo inválido o faltante (formato YYYY-MM).")->withInput();
                }

                if ($periodo !== $periodoEsperado) {
                    return back()->with('error', "Fila {$fila_num}: el periodo del Excel ({$periodo}) no coincide con el periodo seleccionado ({$periodoEsperado}).")->withInput();
                }

                $registros[] = [
                    'contrato_id'    => (int) $contratoId,
                    'periodo'        => $periodo,
                    'tipo_adicional' => $tipo,
                    'monto'          => floatval($monto),
                    'encargado'      => $encargado,
                    'motivo'         => $colObservacion !== false ? (trim((string) ($fila[$colObservacion] ?? '')) ?: null) : null,
                ];
            }

            if (empty($registros)) {
                return back()->with('error', 'El archivo no contiene filas con datos válidos.')->withInput();
            }

            // ── Validar que todos los contrato_ids existen ────────────────────
            $idsEnArchivo  = array_unique(array_column($registros, 'contrato_id'));
            $idsEnBD       = Contrato::whereIn('id', $idsEnArchivo)->pluck('id')->toArray();
            $idsInvalidos  = array_diff($idsEnArchivo, $idsEnBD);

            if (!empty($idsInvalidos)) {
                return back()->with('error', 'Los siguientes contrato_id no existen: ' . implode(', ', $idsInvalidos))->withInput();
            }

            // ── Upsert en transacción ─────────────────────────────────────────
            DB::transaction(function () use ($registros) {
                foreach ($registros as $r) {
                    Adicional::updateOrCreate(
                        [
                            'contrato_id'    => $r['contrato_id'],
                            'periodo'        => $r['periodo'],
                            'tipo_adicional' => $r['tipo_adicional'],
                        ],
                        [
                            'monto'     => $r['monto'],
                            'encargado' => $r['encargado'],
                            'motivo'    => $r['motivo'],
                        ]
                    );
                }
            });

            $total = count($registros);
            return back()->with('success', "{$total} registro(s) importado(s) correctamente.");

        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage())->withInput();
        }
    }
}
