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
            'contrato_id'          => "required|exists:{$conn}.nomina.fact_contratos,id",
            'tipo_movimiento'      => 'required|string|max:50',
            'cargo_id'             => "nullable|exists:{$conn}.nomina.dim_cargos,id",
            'planilla_id'          => "nullable|exists:{$conn}.nomina.dim_planillas,id",
            'inicio'               => 'required|date',
            'fin'                  => 'nullable|date|after_or_equal:inicio',
            'haber_basico'         => 'required|numeric|min:0',
            'movilidad'            => 'nullable|numeric|min:0',
            'asignacion_familiar'  => 'required|boolean',
            'suspension_renta'     => 'nullable|boolean',
            'fondo_pensiones_id'   => "nullable|exists:{$conn}.nomina.dim_fondos_pensiones,id",
            'condicion_id'         => "nullable|exists:{$conn}.nomina.dim_condiciones,id",
            'banco_id'             => "nullable|exists:{$conn}.nomina.dim_bancos,id",
            'centro_costo_id'      => "nullable|exists:{$conn}.nomina.dim_centro_costos,id",
            'familia_id'           => "nullable|exists:{$conn}.nomina.dim_familias,id",
            'moneda_id'            => "nullable|exists:{$conn}.nomina.dim_monedas,id",
            'numero_cuenta'            => 'nullable|string|max:100',
            'codigo_interbancario'     => 'nullable|string|max:20',
            'numero_cuenta_cts'        => 'nullable|string|max:50',
            'codigo_interbancario_cts' => 'nullable|string|max:30',
        ]);

        $contrato     = \App\Models\Contrato::findOrFail($validated['contrato_id']);
        $nuevoInicio  = \Carbon\Carbon::parse($validated['inicio']);

        // Validar que el inicio no sea anterior al inicio del contrato
        if ($nuevoInicio->lt($contrato->inicio_contrato)) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha de inicio no puede ser anterior al inicio del contrato (' . $contrato->inicio_contrato->format('d/m/Y') . ').',
            ], 422);
        }

        // Validar que no exista otro movimiento con la misma fecha de inicio
        $duplicado = ContratoMovimiento::where('contrato_id', $contrato->id)
            ->where('inicio', $nuevoInicio->toDateString())
            ->exists();

        if ($duplicado) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un movimiento con la fecha de inicio ' . $nuevoInicio->format('d/m/Y') . '. Elija una fecha diferente.',
            ], 422);
        }

        DB::transaction(function () use ($validated, $contrato, $nuevoInicio) {
            // 1. Buscar el movimiento vigente y cerrar su fecha fin
            $movimientoVigente = ContratoMovimiento::where('contrato_id', $contrato->id)
                ->where('inicio', '<=', $nuevoInicio->toDateString())
                ->where(fn ($q) => $q->whereNull('fin')->orWhere('fin', '>=', $nuevoInicio->toDateString()))
                ->first();

            if ($movimientoVigente) {
                $movimientoVigente->update([
                    'fin' => $nuevoInicio->copy()->subDay()->toDateString(),
                ]);
            }

            // 2. Crear el nuevo movimiento heredando el fin del ancla
            ContratoMovimiento::create([
                'contrato_id'              => $contrato->id,
                'tipo_movimiento'          => $validated['tipo_movimiento'],
                'cargo_id'                 => $validated['cargo_id'],
                'planilla_id'              => $validated['planilla_id'],
                'fondo_pensiones_id'       => $validated['fondo_pensiones_id'],
                'condicion_id'             => $validated['condicion_id'],
                'asignacion_familiar'      => $validated['asignacion_familiar'],
                'haber_basico'             => $validated['haber_basico'],
                'movilidad'                => $validated['movilidad'] ?? 0,
                'banco_id'                 => $validated['banco_id'],
                'numero_cuenta'            => $validated['numero_cuenta'] ?? null,
                'codigo_interbancario'     => $validated['codigo_interbancario'] ?? null,
                'numero_cuenta_cts'        => $validated['numero_cuenta_cts'] ?? null,
                'codigo_interbancario_cts' => $validated['codigo_interbancario_cts'] ?? null,
                'moneda_id'                => $validated['moneda_id'],
                'familia_id'               => $validated['familia_id'] ?? null,
                'centro_costo_id'          => $validated['centro_costo_id'],
                'suspension_renta'         => $validated['suspension_renta'] ?? false,
                'inicio'                   => $nuevoInicio->toDateString(),
                'fin'                      => $contrato->fin_contrato?->toDateString(),
                'estado'                   => true,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Movimiento registrado correctamente',
        ]);
    }

    public function update(Request $request, $id)
    {
        $conn       = config('database.default');
        $movimiento = ContratoMovimiento::with('contrato')->findOrFail($id);
        $hoy        = now()->toDateString();

        $esVigente = $movimiento->inicio->toDateString() <= $hoy
            && ($movimiento->fin === null || $movimiento->fin->toDateString() >= $hoy);

        // Campos económicos editables en cualquier movimiento (corrección de errores)
        $reglas = [
            'cargo_id'             => "nullable|exists:{$conn}.nomina.dim_cargos,id",
            'planilla_id'          => "nullable|exists:{$conn}.nomina.dim_planillas,id",
            'haber_basico'         => 'required|numeric|min:0',
            'movilidad'            => 'nullable|numeric|min:0',
            'asignacion_familiar'  => 'required|boolean',
            'suspension_renta'     => 'nullable|boolean',
            'fondo_pensiones_id'   => "nullable|exists:{$conn}.nomina.dim_fondos_pensiones,id",
            'condicion_id'         => "nullable|exists:{$conn}.nomina.dim_condiciones,id",
            'banco_id'             => "nullable|exists:{$conn}.nomina.dim_bancos,id",
            'centro_costo_id'      => "nullable|exists:{$conn}.nomina.dim_centro_costos,id",
            'familia_id'           => "nullable|exists:{$conn}.nomina.dim_familias,id",
            'moneda_id'            => "nullable|exists:{$conn}.nomina.dim_monedas,id",
            'numero_cuenta'        => 'nullable|string|max:100',
            'codigo_interbancario' => 'nullable|string|max:20',
        ];

        // Solo el movimiento vigente puede cambiar su fecha fin
        // (y arrastra el cambio al ancla)
        if ($esVigente) {
            $reglas['fin'] = 'nullable|date|after:' . $movimiento->inicio->toDateString();
        }

        $validated = $request->validate($reglas);

        DB::transaction(function () use ($movimiento, $validated, $esVigente) {
            $movimiento->update($validated);

            // Si es el vigente y cambió el fin → sincronizar con el ancla
            if ($esVigente && array_key_exists('fin', $validated)) {
                $movimiento->contrato->update([
                    'fin_contrato' => $validated['fin'],
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
            $contrato = $movimiento->contrato;

            DB::transaction(function () use ($movimiento, $contrato) {
                // Movimiento inmediatamente anterior al que se elimina
                $anterior = ContratoMovimiento::where('contrato_id', $contrato->id)
                    ->where('inicio', '<', $movimiento->inicio)
                    ->orderBy('inicio', 'desc')
                    ->first();

                // Movimiento inmediatamente posterior al que se elimina
                $siguiente = ContratoMovimiento::where('contrato_id', $contrato->id)
                    ->where('inicio', '>', $movimiento->inicio)
                    ->orderBy('inicio', 'asc')
                    ->first();

                $movimiento->delete();

                // Restaurar continuidad del timeline SCD2
                if ($anterior) {
                    $nuevoFin = $siguiente
                        ? $siguiente->inicio->copy()->subDay()->toDateString()
                        : $contrato->fin_contrato?->toDateString();

                    $anterior->update(['fin' => $nuevoFin]);
                }
            });

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
