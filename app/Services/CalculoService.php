<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalculoService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.fastapi.url', 'http://localhost:8000'), '/');
    }

    /**
     * Ejecutar cálculo de nómina via FastAPI.
     * Endpoint real: POST /procesar/{periodo}
     */
    public function ejecutarCalculo(string $periodo): array
    {
        try {
            $response = Http::timeout(120)->post("{$this->baseUrl}/procesar/{$periodo}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            $body = $response->json();
            return [
                'success' => false,
                'error' => $body['mensaje'] ?? $body['detail'] ?? 'Error al ejecutar el cálculo',
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('CalculoService::ejecutarCalculo - ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'No se pudo conectar con el servicio de cálculos. Verifique que el servicio esté disponible.',
            ];
        }
    }

    /**
     * Obtener resultados ya calculados desde gold.fact_resultados_nomina.
     */
    public function obtenerResultados(string $periodo, ?int $idPlanilla = null): array
    {
        try {
            $query = DB::table('gold.fact_resultados_nomina as r')
                ->join('bronze.fact_contratos as c', 'r.id_contrato', '=', 'c.id_contrato')
                ->join('bronze.dim_persona as p', 'c.id_persona', '=', 'p.id_persona')
                ->join('bronze.dim_planilla as pl', 'c.id_planilla', '=', 'pl.id_planilla')
                ->where('r.periodo', $periodo)
                ->select(
                    'r.id_contrato',
                    DB::raw("p.apellido_paterno + ' ' + p.apellido_materno + ' ' + p.nombres as colaborador"),
                    'p.numero_documento',
                    'pl.nombre_planilla as planilla',
                    'r.tipo_calculo',
                    'r.dias_calculo',
                    'r.dias_trabajados',
                    DB::raw('r.HBR as haber_basico'),
                    DB::raw('r.AFR as asig_familiar'),
                    'r.movilidad',
                    'r.comision',
                    'r.bono',
                    'r.total_haberes',
                    'r.base_imponible',
                    'r.aporte',
                    'r.prima',
                    DB::raw('r.comision_afp'),
                    'r.essalud',
                    'r.total_descuentos',
                    'r.neto'
                );

            if ($idPlanilla) {
                $query->where('c.id_planilla', $idPlanilla);
            }

            $resultados = $query->orderBy('p.apellido_paterno')
                ->orderBy('p.apellido_materno')
                ->get();

            return [
                'success' => true,
                'data' => $resultados,
            ];
        } catch (\Exception $e) {
            Log::error('CalculoService::obtenerResultados - ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Error al consultar resultados: ' . $e->getMessage(),
            ];
        }
    }
}
