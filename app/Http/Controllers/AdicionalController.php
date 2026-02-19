<?php

namespace App\Http\Controllers;

use App\Models\Adicional;
use App\Models\Contrato;
use App\Models\Familia;
use App\Models\Pago;
use App\Models\Planilla;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AdicionalController extends Controller
{
    public function index(Request $request)
    {
        $periodos = Pago::select('periodo')
            ->distinct()
            ->orderBy('periodo', 'desc')
            ->pluck('periodo');

        $planillas = Planilla::orderBy('nombre_planilla')->get();
        $familias  = Familia::orderBy('nombre_familia')->get();
        $empresas  = $planillas->pluck('nombre_empresa')->unique()->filter()->sort()->values();

        $contratos = collect();
        $combos = collect();
        $periodoSeleccionado = $request->input('periodo');

        $kpis = [];
        foreach (Adicional::TIPOS as $tipo) {
            $kpis[$tipo] = 0;
        }
        $totalRegistros = 0;

        if ($periodoSeleccionado) {
            $fechasPeriodo = Pago::where('periodo', $periodoSeleccionado)
                ->selectRaw('MIN(inicio) as inicio_periodo, MAX(fin) as fin_periodo')
                ->first();

            // Pares planilla-familia de todos los contratos del periodo (sin filtros extra)
            $combos = Contrato::where('inicio_contrato', '<=', $fechasPeriodo->fin_periodo)
                ->where(function ($q) use ($fechasPeriodo) {
                    $q->where(function ($subQ) {
                        $subQ->whereNull('fin_contrato')->whereNull('fecha_renuncia');
                    })->orWhereRaw("
                        CASE
                            WHEN fecha_renuncia IS NOT NULL THEN fecha_renuncia
                            ELSE fin_contrato
                        END >= ?
                    ", [$fechasPeriodo->inicio_periodo]);
                })
                ->whereNotNull('id_planilla')
                ->whereNotNull('id_familia')
                ->select('id_planilla', 'id_familia')
                ->distinct()
                ->get()
                ->map(fn($c) => [
                    'p' => $c->id_planilla,
                    'f' => $c->id_familia,
                    'e' => $planillas->firstWhere('id_planilla', $c->id_planilla)?->nombre_empresa,
                ])
                ->values();

            $query = Contrato::with(['persona', 'condicion', 'planilla'])
                ->where('inicio_contrato', '<=', $fechasPeriodo->fin_periodo)
                ->where(function ($q) use ($fechasPeriodo) {
                    $q->where(function ($subQ) {
                        $subQ->whereNull('fin_contrato')->whereNull('fecha_renuncia');
                    })->orWhereRaw("
                        CASE
                            WHEN fecha_renuncia IS NOT NULL THEN fecha_renuncia
                            ELSE fin_contrato
                        END >= ?
                    ", [$fechasPeriodo->inicio_periodo]);
                });

            if ($request->filled('nombre_empresa')) {
                $query->whereHas('planilla', fn($q) => $q->where('nombre_empresa', $request->nombre_empresa));
            }

            if ($request->filled('id_planilla')) {
                $query->where('id_planilla', $request->id_planilla);
            }

            if ($request->filled('id_familia')) {
                $query->where('id_familia', $request->id_familia);
            }

            if ($request->filled('numero_documento')) {
                $query->whereHas('persona', function ($q) use ($request) {
                    $q->where('numero_documento', 'like', '%' . $request->numero_documento . '%');
                });
            }

            $contratos = $query->get();

            $adicionales = Adicional::whereIn('id_contrato', $contratos->pluck('id_contrato'))
                ->where('periodo', $periodoSeleccionado)
                ->get()
                ->groupBy('id_contrato');

            $contratos = $contratos->map(function ($contrato) use ($adicionales) {
                $adicionalesContrato = $adicionales->get($contrato->id_contrato, collect());
                $mapped = [];
                foreach (Adicional::TIPOS as $tipo) {
                    $adicional = $adicionalesContrato->firstWhere('tipo_adicional', $tipo);
                    $mapped[$tipo] = [
                        'monto' => $adicional ? $adicional->monto : '',
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
            'empresas',
            'combos',
            'contratos',
            'periodoSeleccionado',
            'kpis',
            'totalRegistros'
        ));
    }

    public function guardar(Request $request): JsonResponse
    {
        $request->validate([
            'id_contrato' => 'required|integer',
            'periodo' => 'required|string|max:10',
            'tipo_adicional' => 'required|string|in:' . implode(',', Adicional::TIPOS),
            'monto' => 'nullable|numeric',
            'motivo' => 'nullable|string|max:500',
        ]);

        $contrato = Contrato::find($request->id_contrato);
        if (!$contrato) {
            return response()->json(['error' => 'Contrato no encontrado'], 404);
        }

        $monto = $request->monto;

        if ($monto === null || $monto === '' || floatval($monto) == 0) {
            Adicional::where('id_contrato', $request->id_contrato)
                ->where('periodo', $request->periodo)
                ->where('tipo_adicional', $request->tipo_adicional)
                ->delete();

            return response()->json(['success' => true, 'action' => 'deleted']);
        }

        $adicional = Adicional::updateOrCreate(
            [
                'id_contrato' => $request->id_contrato,
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

    public function importMovilidad(Request $request)
    {
        $request->validate([
            'archivo'  => 'required|file|mimes:xlsx,xls',
            'periodo'  => 'required|string|max:10|regex:/^\d{4}-\d{2}$/',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('archivo')->getPathname());
            $filas = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

            if (count($filas) < 2) {
                return back()->with('error', 'El archivo no contiene datos.')->withInput();
            }

            // Mapear headers (primera fila)
            $headers = array_map(fn($h) => strtolower(trim((string) $h)), $filas[0]);
            $colIdContrato = array_search('id_contrato', $headers);
            $colMonto      = array_search('monto', $headers);

            if ($colIdContrato === false || $colMonto === false) {
                return back()->with('error', 'El archivo no tiene las columnas requeridas: id_contrato, monto.')->withInput();
            }

            $periodo   = $request->periodo;
            $encargado = auth()->user()->name;
            $registros = [];

            foreach (array_slice($filas, 1) as $fila) {
                $idContrato = $fila[$colIdContrato];
                $monto      = $fila[$colMonto];

                if (!$idContrato || $monto === null || $monto === '' || floatval($monto) == 0) {
                    continue;
                }

                $registros[] = [
                    'id_contrato'    => (int) $idContrato,
                    'periodo'        => $periodo,
                    'tipo_adicional' => Adicional::TIPO_MOVILIDAD,
                    'monto'          => floatval($monto),
                    'encargado'      => $encargado,
                    'motivo'         => null,
                ];
            }

            DB::transaction(function () use ($periodo, $registros) {
                DB::table('bronze.fact_adicionales')
                    ->where('periodo', $periodo)
                    ->where('tipo_adicional', Adicional::TIPO_MOVILIDAD)
                    ->delete();

                if (!empty($registros)) {
                    DB::table('bronze.fact_adicionales')->insert($registros);
                }
            });

            $total = count($registros);
            return back()->with('success', "Movilidad cargada: {$total} registro(s) para el periodo {$periodo}.");

        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage())->withInput();
        }
    }
}
