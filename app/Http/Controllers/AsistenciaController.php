<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Contrato;
use App\Models\ItemAsistencia;
use App\Models\Pago;
use App\Models\Planilla;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AsistenciaController extends Controller
{
    public function index(Request $request)
    {
        $pagos = Pago::orderBy('periodo', 'desc')
            ->orderBy('quincena', 'desc')
            ->get();

        $planillas = Planilla::orderBy('nombre_planilla')->get();

        $pagoSeleccionado = null;
        $contratos = collect();
        $fechas = [];
        $itemsAsistencia = ItemAsistencia::all();

        if ($request->has('id_pago') && $request->id_pago) {
            $pagoSeleccionado = Pago::find($request->id_pago);

            if ($pagoSeleccionado) {
                $fechaInicio = Carbon::parse($pagoSeleccionado->inicio);
                $fechaFin = Carbon::parse($pagoSeleccionado->fin);

                // Generar array de fechas del periodo
                $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);
                foreach ($periodo as $fecha) {
                    $fechas[] = $fecha->copy();
                }

                // Obtener contratos que tengan al menos 1 día válido en el periodo
                $query = Contrato::with(['persona', 'condicion', 'planilla'])
                    ->where(function ($q) use ($fechaInicio, $fechaFin) {
                        $q->where('inicio_contrato', '<=', $fechaFin)
                            ->where(function ($q2) use ($fechaInicio) {
                                $q2->whereNull('fin_contrato')
                                    ->orWhere('fin_contrato', '>=', $fechaInicio);
                            });
                    });

                // Filtro por planilla
                if ($request->filled('id_planilla')) {
                    $query->where('id_planilla', $request->id_planilla);
                }

                // Filtro por número de documento
                if ($request->filled('numero_documento')) {
                    $query->whereHas('persona', function ($q) use ($request) {
                        $q->where('numero_documento', 'like', '%' . $request->numero_documento . '%');
                    });
                }

                $contratos = $query->get()
                    ->filter(function ($contrato) use ($fechaInicio, $fechaFin) {
                        // Verificar que tenga al menos 1 día válido
                        $inicioContrato = Carbon::parse($contrato->inicio_contrato);
                        $finContrato = $contrato->fin_contrato ? Carbon::parse($contrato->fin_contrato) : null;

                        foreach (CarbonPeriod::create($fechaInicio, $fechaFin) as $fecha) {
                            if ($fecha->gte($inicioContrato) && (!$finContrato || $fecha->lte($finContrato))) {
                                return true;
                            }
                        }
                        return false;
                    });

                // Cargar asistencias existentes
                $asistenciasExistentes = Asistencia::whereIn('id_contrato', $contratos->pluck('id_contrato'))
                    ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                    ->get()
                    ->keyBy(function ($item) {
                        return $item->id_contrato . '_' . $item->fecha->format('Y-m-d');
                    });

                // Agregar asistencias a cada contrato
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

        return view('asistencia.index', compact('pagos', 'planillas', 'pagoSeleccionado', 'contratos', 'fechas', 'itemsAsistencia'));
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

        // Validar que la fecha esté dentro del rango del contrato
        if ($fecha->lt($inicioContrato) || ($finContrato && $fecha->gt($finContrato))) {
            return response()->json(['error' => 'Fecha fuera del rango del contrato'], 400);
        }

        // Buscar si ya existe asistencia para esta fecha y contrato
        $asistencia = Asistencia::where('id_contrato', $request->id_contrato)
            ->where('fecha', $request->fecha)
            ->first();

        if ($request->id_cod_asistencia) {
            if ($asistencia) {
                $asistencia->update([
                    'id_cod_asistencia' => $request->id_cod_asistencia,
                ]);
            } else {
                $asistencia = Asistencia::create([
                    'id_contrato' => $request->id_contrato,
                    'fecha' => $request->fecha,
                    'id_cod_asistencia' => $request->id_cod_asistencia,
                ]);
            }
        } else {
            // Si no hay valor, eliminar la asistencia si existe
            if ($asistencia) {
                $asistencia->delete();
            }
        }

        return response()->json(['success' => true]);
    }
}
