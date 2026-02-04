<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Calendario;
use App\Models\Contrato;
use App\Models\ItemAsistencia;
use App\Models\Pago;
use App\Models\Planilla;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AsistenciaController extends Controller
{
    public function index(Request $request)
    {
        $pagosBase = Pago::select('id_pago', 'periodo', 'quincena', 'inicio', 'fin')
            ->orderBy('periodo', 'desc')
            ->orderBy('quincena', 'desc')
            ->get();
        $pagosAsc = $pagosBase->sortBy('inicio')->values();

        $planillas = Planilla::orderBy('nombre_planilla')->get();
        $itemsAsistencia = ItemAsistencia::all();

        $pagoSeleccionado = null;
        $contratos = collect();
        $fechas = [];
        $feriados = [];

        $minFechaPago = $pagosAsc->isNotEmpty() ? Carbon::parse($pagosAsc->first()->inicio)->toDateString() : null;
        $maxFechaPago = $pagosAsc->isNotEmpty() ? Carbon::parse($pagosAsc->last()->fin)->toDateString() : null;
        $rangosPago = $pagosAsc->map(function ($pago) {
            return [
                'inicio' => Carbon::parse($pago->inicio)->toDateString(),
                'fin' => Carbon::parse($pago->fin)->toDateString(),
            ];
        })->values();

        $fechaInicioRango = null;
        $fechaFinRango = null;
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            try {
                $fechaInicioRango = Carbon::parse($request->fecha_inicio)->startOfDay();
                $fechaFinRango = Carbon::parse($request->fecha_fin)->endOfDay();
                if ($fechaFinRango->lt($fechaInicioRango)) {
                    $fechaInicioRango = null;
                    $fechaFinRango = null;
                }
            } catch (\Exception $e) {
                $fechaInicioRango = null;
                $fechaFinRango = null;
            }
        }

        if ($fechaInicioRango && $fechaFinRango) {
            $hayCobertura = $pagosAsc->contains(function ($pago) use ($fechaInicioRango, $fechaFinRango) {
                $inicioPago = Carbon::parse($pago->inicio)->startOfDay();
                $finPago = Carbon::parse($pago->fin)->endOfDay();
                return $inicioPago->lte($fechaFinRango) && $finPago->gte($fechaInicioRango);
            });
            if (!$hayCobertura) {
                $fechaInicioRango = null;
                $fechaFinRango = null;
            }
        }

        $quincenas = collect();
        if ($fechaInicioRango && $fechaFinRango) {
            $quincenas = $pagosAsc
                ->filter(function ($pago) use ($fechaInicioRango, $fechaFinRango) {
                    $inicioPago = Carbon::parse($pago->inicio)->startOfDay();
                    $finPago = Carbon::parse($pago->fin)->endOfDay();
                    return $inicioPago->lte($fechaFinRango) && $finPago->gte($fechaInicioRango);
                })
                ->values()
                ->map(function ($pago) {
                    return [
                        'id_pago' => $pago->id_pago,
                        'label' => Carbon::parse($pago->inicio)->format('d/m/Y')
                            . ' - '
                            . Carbon::parse($pago->fin)->format('d/m/Y')
                            . ' (Q'
                            . $pago->quincena
                            . ')',
                    ];
                });
        }

        $fechaInicio = null;
        $fechaFin = null;

        if ($request->filled('id_pago')) {
            $pagoSeleccionado = Pago::find($request->id_pago);
            if ($pagoSeleccionado) {
                $inicioPago = Carbon::parse($pagoSeleccionado->inicio)->startOfDay();
                $finPago = Carbon::parse($pagoSeleccionado->fin)->endOfDay();
                if ($fechaInicioRango && $fechaFinRango) {
                    $fechaInicio = $fechaInicioRango->copy()->max($inicioPago);
                    $fechaFin = $fechaFinRango->copy()->min($finPago);
                } else {
                    $fechaInicio = $inicioPago;
                    $fechaFin = $finPago;
                }
            }
        } elseif ($fechaInicioRango && $fechaFinRango) {
            $fechaInicio = $fechaInicioRango;
            $fechaFin = $fechaFinRango;
        }

        if ($fechaInicio && $fechaFin && $fechaInicio->lte($fechaFin)) {
            $fechasValidas = collect();
            $pagosCobertura = $pagosAsc->filter(function ($pago) use ($fechaInicio, $fechaFin) {
                $inicioPago = Carbon::parse($pago->inicio)->startOfDay();
                $finPago = Carbon::parse($pago->fin)->endOfDay();
                return $inicioPago->lte($fechaFin) && $finPago->gte($fechaInicio);
            });

            foreach ($pagosCobertura as $pago) {
                $segInicio = Carbon::parse($pago->inicio)->startOfDay()->max($fechaInicio);
                $segFin = Carbon::parse($pago->fin)->endOfDay()->min($fechaFin);
                foreach (CarbonPeriod::create($segInicio, $segFin) as $f) {
                    $fechasValidas->push($f->copy());
                }
            }

            $fechasValidas = $fechasValidas
                ->sortBy(fn ($f) => $f->toDateString())
                ->unique(fn ($f) => $f->toDateString())
                ->values();

            if ($fechasValidas->isNotEmpty()) {
                if (!$pagoSeleccionado) {
                    $pagoSeleccionado = (object) [
                        'inicio' => $fechasValidas->first()->toDateString(),
                        'fin' => $fechasValidas->last()->toDateString(),
                    ];
                }

                $fechas = $fechasValidas->all();
                $inicioConsulta = $fechasValidas->first()->copy()->startOfDay();
                $finConsulta = $fechasValidas->last()->copy()->endOfDay();

                $feriados = Calendario::whereBetween('fecha', [$inicioConsulta, $finConsulta])
                    ->where('tipo_dia', 'Feriado')
                    ->pluck('fecha')
                    ->map(fn ($fecha) => $fecha->format('Y-m-d'))
                    ->flip()
                    ->all();

                $query = Contrato::with(['persona', 'condicion', 'planilla'])
                    ->where(function ($q) use ($inicioConsulta, $finConsulta) {
                        $q->where('inicio_contrato', '<=', $finConsulta)
                            ->where(function ($q2) use ($inicioConsulta) {
                                $q2->whereNull('fin_contrato')
                                    ->orWhere('fin_contrato', '>=', $inicioConsulta);
                            });
                    });

                if ($request->filled('id_planilla')) {
                    $query->where('id_planilla', $request->id_planilla);
                }

                if ($request->filled('numero_documento')) {
                    $query->whereHas('persona', function ($q) use ($request) {
                        $q->where('numero_documento', 'like', '%' . $request->numero_documento . '%');
                    });
                }

                $contratos = $query->get()->filter(function ($contrato) use ($fechasValidas) {
                    $inicioContrato = Carbon::parse($contrato->inicio_contrato);
                    $finContrato = $contrato->fin_contrato ? Carbon::parse($contrato->fin_contrato) : null;

                    foreach ($fechasValidas as $fecha) {
                        if ($fecha->gte($inicioContrato) && (!$finContrato || $fecha->lte($finContrato))) {
                            return true;
                        }
                    }
                    return false;
                });

                $asistenciasExistentes = Asistencia::whereIn('id_contrato', $contratos->pluck('id_contrato'))
                    ->whereBetween('fecha', [$inicioConsulta, $finConsulta])
                    ->get()
                    ->keyBy(function ($item) {
                        return $item->id_contrato . '_' . $item->fecha->format('Y-m-d');
                    });

                $contratos = $contratos->map(function ($contrato) use ($asistenciasExistentes, $fechas) {
                    $asistenciasContrato = [];
                    foreach ($fechas as $fecha) {
                        $key = $contrato->id_contrato . '_' . $fecha->format('Y-m-d');
                        $asistenciasContrato[$fecha->format('Y-m-d')] = $asistenciasExistentes->get($key);
                    }
                    $contrato->setAttribute('asistencias_periodo', $asistenciasContrato);
                    return $contrato;
                });
            }
        }

        return view('asistencia.index', compact(
            'quincenas',
            'planillas',
            'pagoSeleccionado',
            'contratos',
            'fechas',
            'itemsAsistencia',
            'feriados',
            'minFechaPago',
            'maxFechaPago',
            'rangosPago'
        ));
    }

    public function guardar(Request $request): JsonResponse
    {
        $request->validate([
            'id_contrato' => 'required|integer',
            'fecha' => 'required|date',
            'id_cod_asistencia' => 'nullable|integer',
        ]);

        $contrato = Contrato::find($request->id_contrato);

        if (!$contrato) {
            return response()->json(['error' => 'Contrato no encontrado'], 404);
        }

        $fecha = Carbon::parse($request->fecha);
        $inicioContrato = Carbon::parse($contrato->inicio_contrato);
        $finContrato = $contrato->fin_contrato ? Carbon::parse($contrato->fin_contrato) : null;

        // Validar que la fecha este dentro del rango del contrato
        if ($fecha->lt($inicioContrato) || ($finContrato && $fecha->gt($finContrato))) {
            return response()->json(['error' => 'Fecha fuera del rango del contrato'], 400);
        }

        $asistencia = Asistencia::where('id_contrato', $request->id_contrato)
            ->where('fecha', $request->fecha)
            ->first();

        if ($request->id_cod_asistencia) {
            if ($asistencia) {
                $asistencia->update([
                    'id_cod_asistencia' => $request->id_cod_asistencia,
                ]);
            } else {
                Asistencia::create([
                    'id_contrato' => $request->id_contrato,
                    'fecha' => $request->fecha,
                    'id_cod_asistencia' => $request->id_cod_asistencia,
                ]);
            }
        } elseif ($asistencia) {
            $asistencia->delete();
        }

        return response()->json(['success' => true]);
    }
}
