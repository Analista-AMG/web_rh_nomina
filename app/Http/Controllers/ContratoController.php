<?php

namespace App\Http\Controllers;

use App\Models\Baja;
use App\Models\Contrato;
use App\Models\Persona;
use App\Services\ContratoService;
use App\Services\JerarquiaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContratoController extends Controller
{
    public function __construct(
        protected ContratoService $contratoService,
        protected JerarquiaService $jerarquia,
    ) {}


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $personaIds = $this->jerarquia->personaIdsVisibles(auth()->user());

        // Iniciamos la consulta con relaciones para evitar N+1
        $query = Contrato::with([
            'persona',
            'cargo',
            'planilla',
            'fondoPension',
            'banco',
            'condicion',
            'moneda',
            'centroCosto',
            'familia',
            'baja',
            'movimientos.planilla',
            'movimientos.fondoPension',
            'movimientos.cargo',
            'movimientos.banco',
            'movimientos.condicion',
            'movimientos.moneda',
            'movimientos.familia'
        ]);

        $this->jerarquia->aplicarFiltroPersonas($query, $personaIds, 'persona_id');

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

        // --- KPIs filtrados ---
        $hoy = Carbon::now();

        $kpiBase   = $this->jerarquia->aplicarFiltroPersonas(Contrato::query(), $personaIds, 'persona_id');
        $kpiActivo = $this->jerarquia->aplicarFiltroPersonas(Contrato::activos(), $personaIds, 'persona_id');

        $kpis = [
            'total'      => (clone $kpiBase)->count(),
            'activos'    => (clone $kpiActivo)->count(),
            'por_vencer' => (clone $kpiActivo)
                ->whereRaw(Contrato::FIN_EFECTIVO . " BETWEEN ? AND ?", [
                    $hoy->toDateString(),
                    $hoy->copy()->addDays(30)->toDateString(),
                ])
                ->count(),
        ];

        return view('contratos.index', compact('contratos', 'kpis'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
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
            'persona_id' => 'required|integer',
            'cargo_id' => 'required|integer',
            'planilla_id' => 'required|integer',
            'fondo_pensiones_id' => 'required|integer',
            'condicion_id' => 'required|integer',
            'banco_id' => 'required|integer',
            'moneda_id' => 'required|integer',
            'centro_costo_id' => 'required|integer',
            'familia_id' => 'required|integer',
            'inicio_contrato' => 'required|date',
            'fin_contrato' => 'required|date|after:inicio_contrato',
            'haber_basico' => 'required|numeric|min:0',
            'asignacion_familiar' => 'nullable',
            'movilidad' => 'nullable|numeric|min:0',
            'numero_cuenta' => 'required|string|max:100',
            'codigo_interbancario' => 'required|string|max:20',
            'numero_cuenta_cts' => 'nullable|string|max:50',
            'codigo_interbancario_cts' => 'nullable|string|max:500',
            'periodo_prueba'   => 'nullable',
            'suspension_renta' => 'nullable|boolean',
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
        $contrato = Contrato::findOrFail($id);

        $contrato->update([
            'inicio_contrato' => $request->inicio_contrato,
            'fin_contrato'    => $request->fin_contrato,
            'haber_basico'    => $request->haber_basico,
        ]);

        return response()->json(['success' => true, 'message' => 'Contrato actualizado correctamente']);
    }

    /**
     * Dar de baja a un contrato.
     */
    public function darDeBaja(Request $request, $id)
    {
        $validated = $request->validate([
            'fecha_baja' => 'required|date',
            'motivo_baja' => 'required|string|max:255',
            'aviso_con_15_dias' => 'required|boolean',
            'recomienda_reingreso' => 'required|boolean',
            'observacion' => 'nullable|string|max:1000',
        ]);

        $contrato = Contrato::with('baja')->findOrFail($id);

        $fechaBaja = Carbon::parse($validated['fecha_baja']);

        if ($fechaBaja->lt($contrato->inicio_contrato)) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha de baja no puede ser anterior al inicio del contrato.',
            ], 422);
        }

        if ($contrato->fin_contrato && $fechaBaja->gt($contrato->fin_contrato)) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha de baja no puede ser posterior al fin del contrato.',
            ], 422);
        }

        $isUpdate = $contrato->baja !== null;

        DB::transaction(function () use ($contrato, $validated, $isUpdate) {
            // Actualizar fecha_renuncia en el contrato
            $contrato->update(['fecha_renuncia' => $validated['fecha_baja']]);

            // Crear o actualizar registro en la tabla de bajas
            if ($isUpdate) {
                $contrato->baja->update([
                    'fecha_baja' => $validated['fecha_baja'],
                    'motivo_baja' => $validated['motivo_baja'],
                    'aviso_con_15_dias' => $validated['aviso_con_15_dias'],
                    'recomienda_reingreso' => $validated['recomienda_reingreso'],
                    'observacion' => $validated['observacion'],
                ]);
            } else {
                Baja::create([
                    'contrato_id' => $contrato->id,
                    'fecha_baja' => $validated['fecha_baja'],
                    'motivo_baja' => $validated['motivo_baja'],
                    'aviso_con_15_dias' => $validated['aviso_con_15_dias'],
                    'recomienda_reingreso' => $validated['recomienda_reingreso'],
                    'observacion' => $validated['observacion'],
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => $isUpdate
                ? 'Baja actualizada correctamente.'
                : 'Baja registrada correctamente.',
        ]);
    }

    /**
     * Eliminar la baja de un contrato.
     */
    public function eliminarBaja($id)
    {
        $contrato = Contrato::with('baja')->findOrFail($id);

        if (!$contrato->baja) {
            return response()->json([
                'success' => false,
                'message' => 'Este contrato no tiene una baja registrada.',
            ], 422);
        }

        DB::transaction(function () use ($contrato) {
            // Eliminar registro de baja
            $contrato->baja->delete();

            // Limpiar fecha_renuncia del contrato
            $contrato->update(['fecha_renuncia' => null]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Baja eliminada correctamente. El contrato ha sido reactivado.',
        ]);
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
            'persona_id' => 'required|integer',
        ]);

        // Verificar que la persona existe
        $persona = Persona::find($validated['persona_id']);
        if (!$persona) {
            return response()->json([
                'error' => 'Persona no encontrada'
            ], 404);
        }

        $historial = $this->contratoService->obtenerHistorial($validated['persona_id']);

        return response()->json($historial);
    }

    /**
     * Obtener la fecha de inicio del último contrato de una persona (API)
     */
    public function obtenerUltimoInicio(string $numero_documento)
    {
        $persona = Persona::where('numero_documento', $numero_documento)->first();

        if (!$persona) {
            return response()->json([
                'persona_nombre' => null,
                'ultimo_inicio_contrato' => null,
                'ultimo_fin_contrato' => null,
            ]);
        }

        $ultimoContrato = Contrato::where('persona_id', $persona->id)
            ->orderBy('inicio_contrato', 'desc')
            ->first();
        
        $fechaFin = $ultimoContrato ? ($ultimoContrato->fecha_renuncia ?? $ultimoContrato->fin_contrato) : null;

        return response()->json([
            'persona_nombre'         => $persona->nombre_completo,
            'ultimo_inicio_contrato' => $ultimoContrato?->inicio_contrato?->format('Y-m-d'),
            'ultimo_fin_contrato'    => $fechaFin?->format('Y-m-d'),
        ]);
    }
}