<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Services\ContratoService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ContratoController extends Controller
{
    protected $contratoService;

    public function __construct(ContratoService $contratoService)
    {
        $this->contratoService = $contratoService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Verificar permiso
        abort_unless(auth()->user()->can('contratos.view'), 403);

        // Iniciamos la consulta con relaciones para evitar N+1
        $query = Contrato::with([
            'persona',
            'cargo',
            'planilla',
            'fondoPensiones',
            'banco',
            'condicion',
            'moneda',
            'centroCosto',
            'movimientos.planilla',
            'movimientos.fondoPensiones',
            'movimientos.cargo',
            'movimientos.banco',
            'movimientos.condicion',
            'movimientos.moneda',
            'movimientos.centroCosto'
        ]);

        // Filtro por Nombre de Empleado
        if ($request->filled('search_name')) {
            $term = $request->search_name;
            $query->whereHas('persona', function($q) use ($term) {
                $q->where('nombres', 'like', "%{$term}%")
                  ->orWhere('apellido_paterno', 'like', "%{$term}%")
                  ->orWhere('apellido_materno', 'like', "%{$term}%");
            });
        }

        // Filtro por Documento
        if ($request->filled('search_doc')) {
            $term = $request->search_doc;
            $query->whereHas('persona', function($q) use ($term) {
                $q->where('numero_documento', 'like', "%{$term}%");
            });
        }

        // Ordenar: por fecha inicio descendente
        $query->orderBy('inicio_contrato', 'desc');

        // Paginación
        $contratos = $query->paginate(7)->appends($request->all());

        // --- KPIs ---
        $hoy = Carbon::now();
        
        // 1. Total Contratos Históricos
        $total = Contrato::count();

        // 2. Activos
        $activos = Contrato::activos()->count();

        // 3. Por Vencer
        $porVencer = Contrato::activos() // Contratos activos según nuestro scope
            ->whereNotNull('fin_contrato') // Deben tener una fecha de fin definida
            ->whereBetween('fin_contrato', [$hoy->copy()->addDay(), $hoy->copy()->addDays(30)]) // Que estén entre mañana y los próximos 30 días
            ->count();

        $kpis = [
            'total' => $total,
            'activos' => $activos,
            'por_vencer' => $porVencer,
        ];

        return view('contratos.index', compact('contratos', 'kpis'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Necesitamos listas para los selects
        // $personas = \App\Models\Persona::select('id_persona', 'nombres', 'apellido_paterno')->get();
        // $cargos = \App\Models\Cargo::all();
        // return view('contratos.create', compact('personas', 'cargos'));
        
        return view('contratos.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Verificar permiso
        abort_unless(auth()->user()->can('contratos.create'), 403);

        // Validar token de sesion
        $tokenData = session()->get('contrato_token');
        if (!$tokenData || $tokenData['token'] !== $request->token) {
            return response()->json([
                'ok' => false,
                'error' => 'Token invalido o expirado. Inicie el proceso nuevamente.'
            ], 400);
        }

        // Verificar que no haya expirado
        if (now()->isAfter($tokenData['expires_at'])) {
            session()->forget('contrato_token');
            return response()->json([
                'ok' => false,
                'error' => 'La sesion ha expirado. Inicie el proceso nuevamente.'
            ], 400);
        }

        // Validar datos (validaciones basicas, la integridad referencial se maneja en BD)
        $validated = $request->validate([
            'token' => 'required|string',
            'id_persona' => 'required|integer',
            'id_cargo' => 'required|integer',
            'id_planilla' => 'required|integer',
            'id_fp' => 'required|integer',
            'id_condicion' => 'required|integer',
            'id_banco' => 'required|integer',
            'id_moneda' => 'required|integer',
            'id_centro_costo' => 'required|integer',
            'inicio_contrato' => 'required|date',
            'fin_contrato' => 'required|date|after:inicio_contrato',
            'haber_basico' => 'required|numeric|min:0',
            'asignacion_familiar' => 'nullable',
            'movilidad' => 'nullable|numeric|min:0',
            'numero_cuenta' => 'required|string|max:100',
            'codigo_interbancario' => 'required|string|max:20',
            'numero_cuenta_cts' => 'nullable|string|max:50',
            'codigo_interbancario_cts' => 'nullable|string|max:500',
            'periodo_prueba' => 'nullable',
        ]);

        $resultado = $this->contratoService->crearContrato($validated, $tokenData['tipo_movimiento']);

        // Limpiar token de sesion
        session()->forget('contrato_token');

        return response()->json($resultado);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Verificar permiso
        if (auth()->user()->cannot('contratos.edit')) {
            return response()->json(['error' => 'No tienes permiso para editar contratos'], 403);
        }

        // Implementar lógica de actualización
        $contrato = Contrato::findOrFail($id);

        $contrato->update([
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'haber_basico' => $request->haber_basico,
        ]);

        return response()->json(['success' => true, 'message' => 'Contrato actualizado correctamente']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Verificar permiso
        abort_unless(auth()->user()->can('contratos.delete'), 403);

        // Implementar lógica de eliminación
        // ...
    }

    /**
     * Dar de baja a un contrato.
     */
    public function darDeBaja(Request $request, $id)
    {
        if (auth()->user()->cannot('contratos.baja')) {
            return response()->json(['error' => 'No tienes permiso para dar de baja'], 403);
        }

        $validated = $request->validate([
            'fecha_renuncia' => 'required|date',
        ]);

        $contrato = Contrato::findOrFail($id);

        // Validar que la fecha esté dentro del rango del contrato
        $inicio = $contrato->inicio_contrato;
        $fin = $contrato->fin_contrato;

        if ($validated['fecha_renuncia'] < $inicio) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha de baja no puede ser anterior al inicio del contrato.',
            ], 422);
        }

        if ($fin && $validated['fecha_renuncia'] > $fin) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha de baja no puede ser posterior al fin del contrato.',
            ], 422);
        }

        $isUpdate = !is_null($contrato->fecha_renuncia);

        $contrato->update(['fecha_renuncia' => $validated['fecha_renuncia']]);

        return response()->json([
            'success' => true,
            'message' => $isUpdate
                ? 'Fecha de baja actualizada correctamente.'
                : 'Baja registrada correctamente.',
        ]);
    }

    /**
     * Store a new movement for a contract.
     */
    public function storeMovimiento(Request $request)
    {
        abort_unless(auth()->user()->can('contratos.create'), 403);

        $conn = config('database.default');

        $validated = $request->validate([
            'id_contrato' => "required|exists:{$conn}.bronze.fact_contratos,id_contrato",
            'tipo_movimiento' => 'required|string|max:50',
            'id_cargo' => "nullable|exists:{$conn}.bronze.dim_cargo,id_cargo",
            'id_planilla' => "nullable|exists:{$conn}.bronze.dim_planilla,id_planilla",
            'inicio' => 'required|date',
            'fin' => 'nullable|date|after_or_equal:inicio',
            'haber_basico' => 'required|numeric|min:0',
            'movilidad' => 'nullable|numeric|min:0',
            'asignacion_familiar' => 'required|boolean',
            'id_fp' => "nullable|exists:{$conn}.bronze.dim_fondo_pensiones,id_fondo",
            'id_condicion' => "nullable|exists:{$conn}.bronze.dim_condicion,id_condicion",
            'id_banco' => "nullable|exists:{$conn}.bronze.dim_banco,id_banco",
            'id_centro_costo' => "nullable|exists:{$conn}.bronze.dim_centro_costo,id_centro_costo",
            'id_moneda' => "nullable|exists:{$conn}.bronze.dim_moneda,id_moneda",
        ]);

        $validated['fecha_insercion'] = now();

        \App\Models\ContratoMovimiento::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Movimiento registrado correctamente',
        ]);
    }

    /**
     * Update the specified movement.
     */
    public function updateMovimiento(Request $request, $id)
    {
        // Verificar permiso
        if (auth()->user()->cannot('contratos.edit')) {
            return response()->json(['error' => 'No tienes permiso para editar movimientos'], 403);
        }

        // Prefijo de conexión para validaciones exists con schema
        $conn = config('database.default');

        // Validar datos
        $validated = $request->validate([
            'id_cargo' => "nullable|exists:{$conn}.bronze.dim_cargo,id_cargo",
            'id_planilla' => "nullable|exists:{$conn}.bronze.dim_planilla,id_planilla",
            'inicio' => 'nullable|date',
            'fin' => 'nullable|date|after_or_equal:inicio',
            'haber_basico' => 'required|numeric|min:0',
            'movilidad' => 'nullable|numeric|min:0',
            'asignacion_familiar' => 'required|boolean',
            'id_fp' => "nullable|exists:{$conn}.bronze.dim_fondo_pensiones,id_fondo",
            'id_condicion' => "nullable|exists:{$conn}.bronze.dim_condicion,id_condicion",
            'id_banco' => "nullable|exists:{$conn}.bronze.dim_banco,id_banco",
            'id_centro_costo' => "nullable|exists:{$conn}.bronze.dim_centro_costo,id_centro_costo",
            'id_moneda' => "nullable|exists:{$conn}.bronze.dim_moneda,id_moneda",
        ]);

        // Buscar el movimiento
        $movimiento = \App\Models\ContratoMovimiento::findOrFail($id);

        // Tipos de movimiento que sincronizan con el contrato padre
        $tiposSincronizables = [
            'Contrato inicial',
            'Contrato por reingreso',
            'Contrato por baja',
            'Contrato por renovación',
        ];

        \DB::beginTransaction();
        try {
            // Actualizar el movimiento
            $movimiento->update($validated);

            // Si el tipo de movimiento es uno generado por el sistema, sincronizar al contrato padre
            if (in_array($movimiento->tipo_movimiento, $tiposSincronizables)) {
                $contrato = $movimiento->contrato;

                $contrato->update([
                    'id_cargo'            => $movimiento->id_cargo,
                    'id_planilla'         => $movimiento->id_planilla,
                    'id_fp'               => $movimiento->id_fp,
                    'id_condicion'        => $movimiento->id_condicion,
                    'asignacion_familiar' => $movimiento->asignacion_familiar,
                    'haber_basico'        => $movimiento->haber_basico,
                    'movilidad'           => $movimiento->movilidad,
                    'id_banco'            => $movimiento->id_banco,
                    'id_moneda'           => $movimiento->id_moneda,
                    'id_centro_costo'     => $movimiento->id_centro_costo,
                    'inicio_contrato'     => $movimiento->inicio,
                    'fin_contrato'        => $movimiento->fin,
                ]);
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Movimiento actualizado correctamente',
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Delete movement or entire contract depending on type.
     */
    public function destroyMovimiento($id)
    {
        if (auth()->user()->cannot('contratos.delete')) {
            return response()->json(['error' => 'No tienes permiso para eliminar'], 403);
        }

        $movimiento = \App\Models\ContratoMovimiento::findOrFail($id);

        if ($movimiento->tipo_movimiento === 'Movimiento Regular') {
            $movimiento->delete();

            return response()->json([
                'success' => true,
                'message' => 'Movimiento eliminado correctamente',
            ]);
        }

        // Tipo de sistema: eliminar todos los movimientos y el contrato
        $contrato = $movimiento->contrato;
        $persona = $contrato->persona;
        $movimientos = $contrato->movimientos;

        $afectadoNombre = $persona
            ? $persona->apellido_paterno . ' ' . $persona->apellido_materno . ' ' . explode(' ', $persona->nombres)[0]
            : null;
        $afectadoDocumento = $persona?->numero_documento;

        // Capturar datos antes de eliminar
        $contratoData = $contrato->toArray();
        $movimientosData = $movimientos->keyBy('id_movimiento')->map(fn($m) => $m->toArray())->all();

        \DB::beginTransaction();
        try {
            // Desactivar logging automático (se logueará manualmente)
            foreach ($movimientos as $mov) {
                $mov->disableLogging();
            }
            $contrato->disableLogging();

            // Eliminar individualmente
            foreach ($movimientos as $mov) {
                $mov->delete();
            }
            $contrato->delete();

            // Loguear cada movimiento con datos del afectado
            foreach ($movimientos as $mov) {
                activity('movimientos')
                    ->performedOn($mov)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'old' => $movimientosData[$mov->id_movimiento],
                        'afectado_nombre' => $afectadoNombre,
                        'afectado_documento' => $afectadoDocumento,
                    ])
                    ->event('deleted')
                    ->log('Movimiento eliminado');
            }

            // Loguear el contrato con datos del afectado
            activity('contratos')
                ->performedOn($contrato)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old' => $contratoData,
                    'afectado_nombre' => $afectadoNombre,
                    'afectado_documento' => $afectadoDocumento,
                ])
                ->event('deleted')
                ->log('Contrato eliminado');

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contrato y todos sus movimientos eliminados correctamente',
                'redirect' => true,
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Evaluar si se puede crear un contrato (API)
     */
    public function evaluarContrato(Request $request)
    {
        $validated = $request->validate([
            'numero_documento' => 'required|string',
            'fecha_inicio' => 'required|date',
        ]);

        $resultado = $this->contratoService->evaluarContrato(
            $validated['numero_documento'],
            $validated['fecha_inicio']
        );

        return response()->json($resultado);
    }

    /**
     * Obtener historial de contratos de una persona (API)
     */
    public function obtenerHistorial(Request $request)
    {
        $validated = $request->validate([
            'id_persona' => 'required|integer',
        ]);

        // Verificar que la persona existe
        $persona = \App\Models\Persona::find($validated['id_persona']);
        if (!$persona) {
            return response()->json([
                'error' => 'Persona no encontrada'
            ], 404);
        }

        $historial = $this->contratoService->obtenerHistorial($validated['id_persona']);

        return response()->json($historial);
    }

    /**
     * Obtener la fecha de inicio del último contrato de una persona (API)
     */
    public function obtenerUltimoInicio(string $numero_documento)
    {
        $persona = \App\Models\Persona::where('numero_documento', $numero_documento)->first();

        if (!$persona) {
            return response()->json([
                'persona_nombre' => null,
                'ultimo_inicio_contrato' => null,
                'ultimo_fin_contrato' => null,
            ]);
        }

        $ultimoContrato = Contrato::where('id_persona', $persona->id_persona)
            ->orderBy('inicio_contrato', 'desc')
            ->first();
        
        $fechaFin = $ultimoContrato ? ($ultimoContrato->fecha_renuncia ?? $ultimoContrato->fin_contrato) : null;

        return response()->json([
            'persona_nombre' => $persona->nombre_completo,
            'ultimo_inicio_contrato' => $ultimoContrato ? $ultimoContrato->inicio_contrato : null,
            'ultimo_fin_contrato' => $fechaFin,
        ]);
    }
}
