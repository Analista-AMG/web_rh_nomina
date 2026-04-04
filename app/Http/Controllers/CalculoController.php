<?php

namespace App\Http\Controllers;

use App\Helpers\PeriodoCalendario;
use App\Models\Planilla;
use App\Services\CalculoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalculoController extends Controller
{
    protected CalculoService $calculoService;

    public function __construct(CalculoService $calculoService)
    {
        $this->calculoService = $calculoService;
    }

    public function index(Request $request)
    {
        $periodos = PeriodoCalendario::listarDesc(24)->pluck('periodo')->unique()->values();

        $planillas = Planilla::orderBy('nombre_planilla')->get();

        return view('calculos.index', compact('periodos', 'planillas'));
    }

    public function ejecutar(Request $request): JsonResponse
    {
        $request->validate([
            'periodo' => 'required|string|max:10',
        ]);

        $resultado = $this->calculoService->ejecutarCalculo($request->periodo);

        if ($resultado['success']) {
            return response()->json($resultado);
        }

        return response()->json($resultado, $resultado['status'] ?? 500);
    }

    public function obtenerResultados(Request $request): JsonResponse
    {
        $request->validate([
            'periodo'     => 'required|string|max:10',
            'planilla_id' => 'nullable|integer',
        ]);

        $resultado = $this->calculoService->obtenerResultados(
            $request->periodo,
            $request->planilla_id
        );

        if ($resultado['success']) {
            return response()->json($resultado);
        }

        return response()->json($resultado, 500);
    }

    public function exportar(Request $request)
    {
        $request->validate([
            'periodo'     => 'required|string|max:10',
            'planilla_id' => 'nullable|integer',
        ]);

        return $this->calculoService->exportarExcel(
            $request->periodo,
            $request->planilla_id ? (int)$request->planilla_id : null
        );
    }
}
