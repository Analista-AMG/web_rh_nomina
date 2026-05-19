<?php

namespace App\Http\Controllers;

use App\Helpers\PeriodoCalendario;
use App\Services\CalculoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $base = DB::table('nomina.tabla_maestra_validacion');

        $empresas     = (clone $base)->distinct()->orderBy('empresa')->pluck('empresa')->filter()->values();
        $regimenes    = (clone $base)->distinct()->orderBy('regimen')->pluck('regimen')->filter()->values();
        $centrosCosto = (clone $base)->distinct()->orderBy('centro_costo')->pluck('centro_costo')->filter()->values();
        $familias     = (clone $base)->distinct()->orderBy('familia')->pluck('familia')->filter()->values();

        return view('calculos.index', compact('periodos', 'empresas', 'regimenes', 'centrosCosto', 'familias'));
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

    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'periodo'      => 'required|string|max:10',
            'empresa'      => 'nullable|string|max:100',
            'regimen'      => 'nullable|string|max:50',
            'centro_costo' => 'nullable|string|max:100',
            'familia'      => 'nullable|string|max:150',
        ]);

        $filtros = array_filter([
            'empresa'      => $request->empresa,
            'regimen'      => $request->regimen,
            'centro_costo' => $request->centro_costo,
            'familia'      => $request->familia,
        ]);

        $resultado = $this->calculoService->obtenerStats($request->periodo, $filtros);

        if ($resultado['success']) {
            return response()->json($resultado);
        }

        return response()->json($resultado, 500);
    }

    public function exportar(Request $request)
    {
        $request->validate([
            'periodo'      => 'required|string|max:10',
            'empresa'      => 'nullable|string|max:100',
            'regimen'      => 'nullable|string|max:50',
            'centro_costo' => 'nullable|string|max:100',
            'familia'      => 'nullable|string|max:150',
        ]);

        $filtros = array_filter([
            'empresa'      => $request->empresa,
            'regimen'      => $request->regimen,
            'centro_costo' => $request->centro_costo,
            'familia'      => $request->familia,
        ]);

        return $this->calculoService->exportarExcel($request->periodo, $filtros);
    }
}
