<?php

namespace App\Http\Controllers;

use App\Models\Adicional;
use App\Models\Contrato;
use App\Models\Pago;
use App\Models\Planilla;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdicionalController extends Controller
{
    public function index(Request $request)
    {
        $periodos = Pago::select('periodo')
            ->distinct()
            ->orderBy('periodo', 'desc')
            ->pluck('periodo');

        $planillas = Planilla::orderBy('nombre_planilla')->get();

        $contratos = collect();
        $periodoSeleccionado = $request->input('periodo');

        $kpis = [];
        foreach (Adicional::TIPOS as $tipo) {
            $kpis[$tipo] = 0;
        }
        $totalRegistros = 0;

        if ($periodoSeleccionado) {
            $query = Contrato::with(['persona', 'condicion', 'planilla'])
                ->activos();

            if ($request->filled('id_planilla')) {
                $query->where('id_planilla', $request->id_planilla);
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
}
