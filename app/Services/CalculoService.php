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
                'error'   => 'No se pudo conectar con el servicio de cálculos.',
            ];
        }
    }

    public function obtenerStats(string $periodo, array $filtros = []): array
    {
        try {
            $rows = $this->queryBase($periodo, $filtros)->get();

            if ($rows->isEmpty()) {
                return ['success' => true, 'data' => null];
            }

            $sum = fn(string $col) => $rows->sum(fn($r) => (float)($r->$col ?? 0));
            $isFT  = fn($r) => stripos($r->regimen ?? '', 'RHE') === false;
            $isRHE = fn($r) => stripos($r->regimen ?? '', 'RHE') !== false;

            $rowsFT  = $rows->filter($isFT);
            $rowsRHE = $rows->filter($isRHE);
            $sumFT   = fn(string $col) => $rowsFT->sum(fn($r) => (float)($r->$col ?? 0));

            // ── KPIs resumen ─────────────────────────────────────────────────
            $kpis = [
                'contratos'        => $rows->count(),
                'contratos_ft'     => $rowsFT->count(),
                'contratos_rhe'    => $rowsRHE->count(),
                'total_haberes'    => $sum('total_haberes'),
                'total_descuentos' => $sum('total_descuentos'),
                'adelanto_quincena' => $sum('adelanto_quincena'),
                'neto_soles'       => $sum('neto_soles'),
                'costo_total'      => $sum('costo_total_empleado'),
            ];

            // ── Asignación familiar ──────────────────────────────────────────
            $asigFam = $rows->filter(fn($r) => ($r->asignacion_familiar ?? 0) > 0);
            $bloqueAsigFam = [
                'total'         => $sum('asignacion_familiar'),
                'beneficiarios' => $asigFam->count(),
            ];

            // ── Feriados ─────────────────────────────────────────────────────
            $bloqueFeriados = [
                'dias_total'  => (int)$rows->sum(fn($r) => (int)($r->dias_feriado ?? 0)),
                'monto_total' => $sum('remun_feriado'),
            ];

            // ── Movilidad (desglosada) ───────────────────────────────────────
            $movBruto   = $sum('movilidad');
            $movAdelanto = $sum('adelanto_movilidad');
            $bloqueMovilidad = [
                'bruto'   => $movBruto,
                'adelanto' => $movAdelanto,
                'real'    => $movBruto - $movAdelanto,
            ];

            // ── Fondo de pensiones ───────────────────────────────────────────
            $bloqueFP = [
                'detalle' => $rows->groupBy('fondo_pensiones')
                    ->map(fn($g, $fp) => [
                        'fondo_pensiones' => $fp ?: '—',
                        'contratos'       => $g->count(),
                        'aporte'          => $g->sum(fn($r) => (float)($r->aporte_fp   ?? 0)),
                        'prima'           => $g->sum(fn($r) => (float)($r->prima_fp    ?? 0)),
                        'comision'        => $g->sum(fn($r) => (float)($r->comision_fp ?? 0)),
                    ])
                    ->sortBy('fondo_pensiones')
                    ->values(),
            ];

            // ── Adelantos ────────────────────────────────────────────────────
            $bloqueAdelantos = [
                'comision'      => $sum('adelanto_comision'),
                'movilidad'     => $movAdelanto,
                'gratificacion' => $sum('adelanto_gratificacion'),
                'quincena'      => $sum('adelanto_quincena'),
                'total'         => $sum('adelanto_comision') + $movAdelanto + $sum('adelanto_gratificacion') + $sum('adelanto_quincena'),
            ];

            // ── Bonos (afectos + inafectos unificados) ───────────────────────
            $bloqueBonos = [
                // Afectos AFP/EsSalud
                'rendimiento' => $sum('bono_rendimiento'),
                'nocturnidad' => $sum('bono_nocturnidad'),
                'mensual'     => $sum('bono_mensual'),
                'reintegro'   => $sum('bono_reintegro'),
                'extra_9pct'  => $sum('bono_extra_9pct'),
                // Inafecto
                'maqueta_inafecto' => $sum('maqueta_inafecto'),
                // Subtotales
                'total_afectos'    => $sum('bono_rendimiento') + $sum('bono_nocturnidad') + $sum('bono_mensual') + $sum('bono_reintegro') + $sum('bono_extra_9pct'),
                'total_inafectos'  => $sum('maqueta_inafecto'),
            ];

            // ── Provisiones empleador (solo FT) ─────────────────────────────
            $provGrat  = $sumFT('prov_grat');
            $prov9pct  = $sumFT('prov_9pct');
            $provCts   = $sumFT('prov_cts');
            $provVac   = $sumFT('prov_vac');
            $essalud   = $sumFT('essalud');
            $movilidadFT = $sumFT('movilidad');
            $bloqueProvisiones = [
                'contratos_ft'       => $rowsFT->count(),
                'basico_comp'        => $sumFT('basico_compensatorio'),
                'movilidad'          => $movilidadFT,
                'essalud'            => $essalud,
                'prov_grat'          => $provGrat,
                'prov_9pct'          => $prov9pct,
                'prov_cts'           => $provCts,
                'prov_vac'           => $provVac,
                'total_provisiones'  => $movilidadFT + $essalud + $provGrat + $prov9pct + $provCts + $provVac,
                'costo_empleador_ft' => $sumFT('costo_total_empleado'),
            ];

            // ── IR 4ta categoría (RHE) ───────────────────────────────────────
            $bloqueIR4ta = [
                'total'          => $sum('ir_8pct'),
                'personas_afectas' => $rows->filter(fn($r) => ($r->ir_8pct ?? 0) > 0)->count(),
            ];

            // ── Distribución por empresa ─────────────────────────────────────
            $distribucion = $rows->groupBy(fn($r) => ($r->empresa ?? '—') . '||' . ($r->regimen ?? '—'))
                ->map(fn($g) => [
                    'empresa'          => $g->first()->empresa ?? '—',
                    'regimen'          => $g->first()->regimen ?? '—',
                    'contratos'        => $g->count(),
                    'total_haberes'    => $g->sum(fn($r) => (float)($r->total_haberes       ?? 0)),
                    'total_descuentos' => $g->sum(fn($r) => (float)($r->total_descuentos    ?? 0)),
                    'neto_soles'       => $g->sum(fn($r) => (float)($r->neto_soles          ?? 0)),
                    'essalud'          => $g->sum(fn($r) => (float)($r->essalud             ?? 0)),
                    'prov_total'       => $g->sum(fn($r) => (float)(($r->prov_grat ?? 0) + ($r->prov_9pct ?? 0) + ($r->prov_cts ?? 0) + ($r->prov_vac ?? 0))),
                    'ir_8pct'          => $g->sum(fn($r) => (float)($r->ir_8pct             ?? 0)),
                    'costo_total'      => $g->sum(fn($r) => (float)($r->costo_total_empleado ?? 0)),
                    'es_rhe'           => stripos($g->first()->regimen ?? '', 'RHE') !== false,
                ])
                ->sortBy('empresa')
                ->values();

            return [
                'success' => true,
                'data'    => [
                    'kpis'          => $kpis,
                    'asig_familiar' => $bloqueAsigFam,
                    'feriados'      => $bloqueFeriados,
                    'movilidad'     => $bloqueMovilidad,
                    'fp'            => $bloqueFP,
                    'adelantos'     => $bloqueAdelantos,
                    'bonos'         => $bloqueBonos,
                    'provisiones'   => $bloqueProvisiones,
                    'ir_4ta'        => $bloqueIR4ta,
                    'distribucion'  => $distribucion,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('CalculoService::obtenerStats - ' . $e->getMessage());
            return ['success' => false, 'error' => 'Error al consultar resultados: ' . $e->getMessage()];
        }
    }

    public function exportarExcel(string $periodo, array $filtros = []): StreamedResponse
    {
        $rows = $this->queryBase($periodo, $filtros)->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Nómina ' . $periodo);

        $headers = [
            'Empresa', 'Régimen', 'Familia', 'Centro Costo',
            'Documento', 'Nombre Completo', 'Cargo', 'Fondo Pensiones',
            'Estado', 'Tipo Cálculo', 'Fecha Ingreso', 'Fecha Cese',
            'D.LSG', 'D.Feriado', 'D.Faltas', 'D.DM', 'D.Vacac.', 'Tardanza', 'D.Trabajados',
            'Haber Básico', 'Asig. Familiar', 'Movilidad', 'Comisión',
            'Remun. Feriado', 'Reintegro Afecto', 'Maqueta Inafecto',
            'Bono Rendimiento', 'Bono Nocturnidad', 'Bono Mensual', 'Bono Reintegro', 'Bono Extra 9%',
            'Total Haberes',
            'Aporte FP', 'Prima FP', 'Comisión FP',
            'EsSalud', 'Retención 5ta', 'Adel. Comisión', 'Adel. Movilidad',
            'Adel. Gratif.', 'Adel. Quincena', 'Tard. Desc.', 'Otros Desc.', 'IR 8%',
            'Total Descuentos', 'Neto S/', 'Neto USD',
            'B.Comp.', 'Prov. Grat.', 'Prov. 9%', 'Prov. CTS', 'Prov. Vac.', 'Costo Total',
        ];

        $colCount = count($headers);
        $coord    = fn(int $col) => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $lastCol  = $coord($colCount);

        foreach ($headers as $i => $header) {
            $sheet->setCellValue($coord($i + 1) . '1', $header);
        }

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E67E22']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);

        $rowNum = 2;
        foreach ($rows as $row) {
            $values = [
                $row->empresa, $row->regimen, $row->familia, $row->centro_costo,
                $row->numero_documento, $row->nombre_completo, $row->cargo, $row->fondo_pensiones,
                $row->status, $row->tipo_calculo, $row->fecha_ingreso, $row->fecha_de_cese,
                $row->dias_lsg, $row->dias_feriado, $row->dias_con_faltas, $row->dias_dm,
                $row->dias_vacaciones, $row->tardanza, $row->dias_trabajados,
                $row->haber_basico, $row->asignacion_familiar, $row->movilidad, $row->comision,
                $row->remun_feriado, $row->reintegro_afecto, $row->maqueta_inafecto,
                $row->bono_rendimiento, $row->bono_nocturnidad, $row->bono_mensual,
                $row->bono_reintegro, $row->bono_extra_9pct,
                $row->total_haberes,
                $row->aporte_fp, $row->prima_fp, $row->comision_fp,
                $row->essalud, $row->retencion_5ta, $row->adelanto_comision,
                $row->adelanto_movilidad, $row->adelanto_gratificacion, $row->adelanto_quincena,
                $row->tardanzas_descuentos, $row->otros_descuentos, $row->ir_8pct,
                $row->total_descuentos, $row->neto_soles, $row->neto_usd,
                $row->basico_compensatorio, $row->prov_grat, $row->prov_9pct,
                $row->prov_cts, $row->prov_vac, $row->costo_total_empleado,
            ];

            foreach ($values as $i => $value) {
                $sheet->setCellValue($coord($i + 1) . $rowNum, $value);
            }

            if ($rowNum % 2 === 0) {
                $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF9F5']],
                ]);
            }
            $rowNum++;
        }

        for ($i = 1; $i <= $colCount; $i++) {
            $sheet->getColumnDimension($coord($i))->setAutoSize(true);
        }
        $sheet->freezePane('A2');

        $suffix   = collect($filtros)->map(fn($v, $k) => $k[0] . $v)->implode('_');
        $filename = 'nomina_' . $periodo . ($suffix ? "_{$suffix}" : '') . '.xlsx';

        $writer = new Xlsx($spreadsheet);
        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    private function queryBase(string $periodo, array $filtros = [])
    {
        $query = DB::table('nomina.tabla_maestra_validacion')
            ->where('periodo', $periodo)
            ->where('persona_id', '!=', 192);

        foreach (['empresa', 'regimen', 'centro_costo', 'familia'] as $col) {
            if (!empty($filtros[$col])) {
                $query->where($col, $filtros[$col]);
            }
        }

        if (!empty($filtros['regime_tipo'])) {
            if ($filtros['regime_tipo'] === 'planilla') {
                $query->where('regimen', 'not like', '%RHE%');
            } elseif ($filtros['regime_tipo'] === 'RHE') {
                $query->where('regimen', 'like', '%RHE%');
            }
        }

        return $query->orderBy('empresa')->orderBy('nombre_completo');
    }
}
