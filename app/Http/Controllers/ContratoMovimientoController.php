<?php

namespace App\Http\Controllers;

use App\Models\ContratoMovimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContratoMovimientoController extends Controller
{
    public function store(Request $request)
    {
        $conn = config('database.default');

        $validated = $request->validate([
            'contrato_id'        => "required|exists:{$conn}.nomina.fact_contratos,id",
            'tipo_movimiento'    => 'required|string|max:50',
            'cargo_id'           => "nullable|exists:{$conn}.nomina.dim_cargos,id",
            'planilla_id'        => "nullable|exists:{$conn}.nomina.dim_planillas,id",
            'inicio'             => 'required|date',
            'fin'                => 'nullable|date|after_or_equal:inicio',
            'haber_basico'       => 'required|numeric|min:0',
            'movilidad'          => 'nullable|numeric|min:0',
            'asignacion_familiar'=> 'required|boolean',
            'fondo_pensiones_id' => "nullable|exists:{$conn}.nomina.dim_fondos_pensiones,id",
            'condicion_id'       => "nullable|exists:{$conn}.nomina.dim_condiciones,id",
            'banco_id'           => "nullable|exists:{$conn}.nomina.dim_bancos,id",
            'centro_costo_id'    => "nullable|exists:{$conn}.nomina.dim_centro_costos,id",
            'familia_id'         => "nullable|exists:{$conn}.nomina.dim_familias,id",
            'moneda_id'          => "nullable|exists:{$conn}.nomina.dim_monedas,id",
        ]);

        ContratoMovimiento::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Movimiento registrado correctamente',
        ]);
    }

    public function update(Request $request, $id)
    {
        $conn = config('database.default');

        $validated = $request->validate([
            'cargo_id'           => "nullable|exists:{$conn}.nomina.dim_cargos,id",
            'planilla_id'        => "nullable|exists:{$conn}.nomina.dim_planillas,id",
            'inicio'             => 'nullable|date',
            'fin'                => 'nullable|date|after_or_equal:inicio',
            'haber_basico'       => 'required|numeric|min:0',
            'movilidad'          => 'nullable|numeric|min:0',
            'asignacion_familiar'=> 'required|boolean',
            'fondo_pensiones_id' => "nullable|exists:{$conn}.nomina.dim_fondos_pensiones,id",
            'condicion_id'       => "nullable|exists:{$conn}.nomina.dim_condiciones,id",
            'banco_id'           => "nullable|exists:{$conn}.nomina.dim_bancos,id",
            'centro_costo_id'    => "nullable|exists:{$conn}.nomina.dim_centro_costos,id",
            'familia_id'         => "nullable|exists:{$conn}.nomina.dim_familias,id",
            'moneda_id'          => "nullable|exists:{$conn}.nomina.dim_monedas,id",
        ]);

        $movimiento = ContratoMovimiento::findOrFail($id);

        DB::transaction(function () use ($movimiento, $validated) {
            $movimiento->update($validated);

            if (in_array($movimiento->tipo_movimiento, ContratoMovimiento::TIPOS_SISTEMA)) {
                $movimiento->contrato->update([
                    'cargo_id'            => $movimiento->cargo_id,
                    'planilla_id'         => $movimiento->planilla_id,
                    'fondo_pensiones_id'  => $movimiento->fondo_pensiones_id,
                    'condicion_id'        => $movimiento->condicion_id,
                    'asignacion_familiar' => $movimiento->asignacion_familiar,
                    'haber_basico'        => $movimiento->haber_basico,
                    'movilidad'           => $movimiento->movilidad,
                    'banco_id'            => $movimiento->banco_id,
                    'moneda_id'           => $movimiento->moneda_id,
                    'centro_costo_id'     => $movimiento->centro_costo_id,
                    'familia_id'          => $movimiento->familia_id,
                    'inicio_contrato'     => $movimiento->inicio,
                    'fin_contrato'        => $movimiento->fin,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Movimiento actualizado correctamente',
        ]);
    }

    public function destroy($id)
    {
        $movimiento = ContratoMovimiento::findOrFail($id);

        if ($movimiento->tipo_movimiento === 'Movimiento Regular') {
            $movimiento->delete();

            return response()->json([
                'success' => true,
                'message' => 'Movimiento eliminado correctamente',
            ]);
        }

        // Tipo de sistema: eliminar el contrato completo con todos sus movimientos
        $contrato   = $movimiento->contrato;
        $persona    = $contrato->persona;
        $movimientos = $contrato->movimientos;

        $afectadoNombre    = $persona?->nombre_completo;
        $afectadoDocumento = $persona?->numero_documento;

        $contratoData    = $contrato->toArray();
        $movimientosData = $movimientos->keyBy('id')->map(fn($m) => $m->toArray())->all();

        DB::transaction(function () use ($contrato, $movimientos, $movimientosData, $contratoData, $afectadoNombre, $afectadoDocumento) {
            foreach ($movimientos as $mov) {
                $mov->disableLogging();
            }
            $contrato->disableLogging();

            foreach ($movimientos as $mov) {
                $mov->delete();
            }
            $contrato->delete();

            foreach ($movimientos as $mov) {
                activity('movimientos')
                    ->performedOn($mov)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'old'                 => $movimientosData[$mov->id],
                        'afectado_nombre'     => $afectadoNombre,
                        'afectado_documento'  => $afectadoDocumento,
                    ])
                    ->event('deleted')
                    ->log('Movimiento eliminado');
            }

            activity('contratos')
                ->performedOn($contrato)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old'                => $contratoData,
                    'afectado_nombre'    => $afectadoNombre,
                    'afectado_documento' => $afectadoDocumento,
                ])
                ->event('deleted')
                ->log('Contrato eliminado');
        });

        return response()->json([
            'success' => true,
            'message' => 'Contrato y todos sus movimientos eliminados correctamente',
            'redirect' => true,
        ]);
    }
}
