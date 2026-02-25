<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CalculoService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.fastapi.url', 'http://localhost:8000'), '/');
    }

    /**
     * Ejecutar cálculo de nómina via FastAPI.
     */
    public function ejecutarCalculo(string $periodo): array
    {
        try {
            $response = Http::timeout(120)->post("{$this->baseUrl}/procesar/{$periodo}");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            $body = $response->json();
            return [
                'success' => false,
                'error'   => $body['mensaje'] ?? $body['detail'] ?? 'Error al ejecutar el cálculo',
                'status'  => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('CalculoService::ejecutarCalculo - ' . $e->getMessage());
            return [
                'success' => false,
                'error'   => 'No se pudo conectar con el servicio de cálculos. Verifique que el servicio esté disponible.',
            ];
        }
    }

    /**
     * Obtener resultados desde gold.fact_resultados_nomina.
     */
    public function obtenerResultados(string $periodo, ?int $idPlanilla = null): array
    {
        try {
            $resultados = $this->queryResultados($periodo, $idPlanilla)->get();

            return ['success' => true, 'data' => $resultados];
        } catch (\Exception $e) {
            Log::error('CalculoService::obtenerResultados - ' . $e->getMessage());
            return ['success' => false, 'error' => 'Error al consultar resultados: ' . $e->getMessage()];
        }
    }

    /**
     * Exportar resultados a Excel (.xlsx).
     */
    public function exportarExcel(string $periodo, ?int $idPlanilla = null): StreamedResponse
    {
        $rows = $this->queryResultados($periodo, $idPlanilla)->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Nómina ' . $periodo);

        $headers = [
            'Contrato', 'Colaborador', 'Documento', 'Planilla', 'Tipo',
            'D. Cálculo', 'D. Trabajados',
            'Haber Básico', 'Asig. Familiar', 'Movilidad', 'Comisión', 'Bono',
            'RV', 'Pago DM', 'Pago Feriado', 'Reintegro Afecto', 'Tardanza',
            'Total Haberes', 'Base Imponible',
            'Aporte AFP', 'Prima', 'Com. AFP', 'EsSalud', 'Adelanto Sueldo',
            'Total Descuentos', 'Neto',
            'Básico Comp.', 'Prov. Gratif.', 'Bono Extra 9%', 'Prov. CTS', 'Prov. Vacac.', 'Costo Total',
        ];

        // Header row
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Header style
        $lastCol = chr(ord('A') + count($headers) - 1);
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E67E22']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);

        // Data rows
        $rowNum = 2;
        foreach ($rows as $row) {
            $values = [
                $row->id_contrato, $row->colaborador, $row->numero_documento, $row->planilla, $row->tipo_calculo,
                $row->dias_calculo, $row->dias_trabajados,
                $row->haber_basico, $row->asig_familiar, $row->movilidad, $row->comision, $row->bono,
                $row->rv, $row->pago_descanso_medico, $row->pago_feriado, $row->reintegro_afecto, $row->tardanza,
                $row->total_haberes, $row->base_imponible,
                $row->aporte, $row->prima, $row->comision_afp, $row->essalud, $row->adelanto_sueldo,
                $row->total_descuentos, $row->neto,
                $row->basico_compensatorio, $row->prov_gratificacion, $row->bono_extra_9, $row->prov_cts, $row->prov_vacaciones, $row->costo_total,
            ];

            $col = 'A';
            foreach ($values as $value) {
                $sheet->setCellValue($col . $rowNum, $value);
                $col++;
            }

            // Zebra rows
            if ($rowNum % 2 === 0) {
                $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF9F5']],
                ]);
            }
            $rowNum++;
        }

        // Auto-width columns
        foreach (range('A', $lastCol) as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Freeze header
        $sheet->freezePane('A2');

        $filename = "nomina_{$periodo}" . ($idPlanilla ? "_planilla{$idPlanilla}" : '') . '.xlsx';

        $writer = new Xlsx($spreadsheet);
        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Query base reutilizable para resultados.
     */
    private function queryResultados(string $periodo, ?int $idPlanilla = null)
    {
        $query = DB::table('gold.fact_resultados_nomina as r')
            ->join('bronze.fact_contratos as c', 'r.id_contrato', '=', 'c.id_contrato')
            ->join('bronze.dim_persona as p', 'c.id_persona', '=', 'p.id_persona')
            ->join('bronze.dim_planilla as pl', 'c.id_planilla', '=', 'pl.id_planilla')
            ->where('r.periodo', $periodo)
            ->select(
                'r.id_contrato',
                DB::raw("p.apellido_paterno + ' ' + p.apellido_materno + ' ' + LEFT(p.nombres, CHARINDEX(' ', p.nombres + ' ') - 1) as colaborador"),
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
                DB::raw('r.RV as rv'),
                'r.pago_descanso_medico',
                'r.pago_feriado',
                'r.reintegro_afecto',
                'r.tardanza',
                'r.total_haberes',
                'r.base_imponible',
                'r.aporte',
                'r.prima',
                'r.comision_afp',
                'r.essalud',
                'r.adelanto_sueldo',
                'r.total_descuentos',
                'r.neto',
                'r.basico_compensatorio',
                'r.prov_gratificacion',
                'r.bono_extra_9',
                'r.prov_cts',
                'r.prov_vacaciones',
                'r.costo_total',
            );

        if ($idPlanilla) {
            $query->where('c.id_planilla', $idPlanilla);
        }

        return $query->orderBy('p.apellido_paterno')->orderBy('p.apellido_materno');
    }
}
