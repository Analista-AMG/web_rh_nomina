<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
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
            'baja',
            'movimientoActual.cargo',
            'movimientoActual.planilla',
            'movimientoActual.fondoPension',
            'movimientoActual.banco',
            'movimientoActual.condicion',
            'movimientoActual.moneda',
            'movimientoActual.centroCosto',
            'movimientoActual.familia',
            'movimientos.planilla',
            'movimientos.fondoPension',
            'movimientos.cargo',
            'movimientos.banco',
            'movimientos.condicion',
            'movimientos.moneda',
            'movimientos.centroCosto',
            'movimientos.familia',
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
     * Las fechas del contrato son inmutables desde la creación.
     * inicio_contrato nunca cambia.
     * fin_contrato solo cambia mediante baja o edición del movimiento vigente.
     */
    public function update(Request $request, $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Las fechas del contrato no son editables directamente. Use el movimiento vigente o registre una baja.',
        ], 403);
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

        $ultimoMovimiento = $contrato->movimientos()->orderBy('inicio', 'desc')->first();
        if ($ultimoMovimiento && $fechaBaja->lt($ultimoMovimiento->inicio)) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha de baja no puede ser anterior al inicio del último movimiento (' . $ultimoMovimiento->inicio->format('d/m/Y') . ').',
            ], 422);
        }

        if ($contrato->fin_contrato && $fechaBaja->gt($contrato->fin_contrato)) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha de baja no puede ser posterior al fin del contrato.',
            ], 422);
        }

        $isUpdate = $contrato->baja !== null;
        $asistenciasEliminadas = 0;

        DB::transaction(function () use ($contrato, $validated, $isUpdate, &$asistenciasEliminadas) {
            $fechaBajaStr = $validated['fecha_baja'];

            // 1. Cerrar el movimiento vigente en la fecha de baja
            $movimientoVigente = $contrato->movimientos()
                ->where('inicio', '<=', $fechaBajaStr)
                ->where(fn ($q) => $q->whereNull('fin')->orWhere('fin', '>=', $fechaBajaStr))
                ->first();

            if ($movimientoVigente) {
                $movimientoVigente->update(['fin' => $fechaBajaStr]);
            }

            // 2. Registrar fecha_renuncia en el ancla
            $contrato->update(['fecha_renuncia' => $fechaBajaStr]);

            // 3. Crear o actualizar registro en la tabla de bajas
            if ($isUpdate) {
                $contrato->baja->update([
                    'fecha_baja'           => $fechaBajaStr,
                    'motivo_baja'          => $validated['motivo_baja'],
                    'aviso_con_15_dias'    => $validated['aviso_con_15_dias'],
                    'recomienda_reingreso' => $validated['recomienda_reingreso'],
                    'observacion'          => $validated['observacion'],
                ]);
            } else {
                Baja::create([
                    'contrato_id'          => $contrato->id,
                    'fecha_baja'           => $fechaBajaStr,
                    'motivo_baja'          => $validated['motivo_baja'],
                    'aviso_con_15_dias'    => $validated['aviso_con_15_dias'],
                    'recomienda_reingreso' => $validated['recomienda_reingreso'],
                    'observacion'          => $validated['observacion'],
                ]);
            }

            // 4. Eliminar asistencias posteriores a la fecha de baja (solo de este contrato)
            $asistenciasEliminadas = Asistencia::where('contrato_id', $contrato->id)
                ->where('fecha', '>', $fechaBajaStr)
                ->delete();
        });

        $mensaje = $isUpdate ? 'Baja actualizada correctamente.' : 'Baja registrada correctamente.';
        if ($asistenciasEliminadas > 0) {
            $mensaje .= " Se eliminaron {$asistenciasEliminadas} registro(s) de asistencia posteriores a la fecha de baja.";
        }

        return response()->json([
            'success' => true,
            'message' => $mensaje,
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
            $fechaBaja = $contrato->baja->fecha_baja;

            // 1. Reabrir el movimiento que fue cerrado en la baja
            // (el que tiene fin = fecha_baja), restaurando el fin del ancla
            $movimientoCerrado = $contrato->movimientos()
                ->where('fin', $fechaBaja)
                ->orderBy('inicio', 'desc')
                ->first();

            if ($movimientoCerrado) {
                $movimientoCerrado->update(['fin' => $contrato->fin_contrato]);
            }

            // 2. Eliminar registro de baja
            $contrato->baja->delete();

            // 3. Limpiar fecha_renuncia del ancla
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
        $conn = config('database.default');

        $validated = $request->validate([
            'numero_documento' => 'required|string',
            'fecha_inicio'     => 'required|date',
            'planilla_id'      => "required|exists:{$conn}.nomina.dim_planillas,id",
        ]);

        $planilla = \App\Models\Planilla::find($validated['planilla_id']);

        $resultado = $this->contratoService->evaluarContrato(
            $validated['numero_documento'],
            $validated['fecha_inicio'],
            $planilla->regimen
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