<?php

namespace App\Services;

use App\Models\Persona;
use App\Models\Contrato;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ContratoService
{
    /**
     * Evalua si se puede crear un contrato para una persona en una fecha determinada.
     * Retorna informacion sobre el tipo de movimiento y datos de la persona.
     *
     * @param string $numeroDocumento
     * @param string $fechaInicio
     * @return array
     */
    public function evaluarContrato(string $numeroDocumento, string $fechaInicio): array
    {
        // 1. Buscar la persona por numero de documento
        $persona = Persona::where('numero_documento', $numeroDocumento)->first();

        if (!$persona) {
            return [
                'ok' => false,
                'error' => 'No se encontro una persona con el numero de documento ingresado.',
                'puede_crear' => false
            ];
        }

        // 2. Obtener contratos de la persona
        $contratos = Contrato::where('id_persona', $persona->id_persona)
            ->orderBy('inicio_contrato', 'desc')
            ->get();

        $fechaInicioCarbon = Carbon::parse($fechaInicio);
        $tieneHistorial = $contratos->isNotEmpty();
        $tipoMovimiento = 'Contrato inicial'; // 1. Regla Base: Contrato Inicial
        $puedeCrear = true;
        $mensaje = '';
        $contratoActivo = null;

        if ($tieneHistorial) {
            // Si tiene historial, la base es Reingreso, que puede ser sobreescrita por reglas más específicas.
            $tipoMovimiento = 'Contrato por reingreso';

            // --- Verificación de Solapamiento ---
            foreach ($contratos as $contrato) {
                $inicioContrato = Carbon::parse($contrato->inicio_contrato);
                $finEfectivo = $contrato->fecha_renuncia ? Carbon::parse($contrato->fecha_renuncia) : ($contrato->fin_contrato ? Carbon::parse($contrato->fin_contrato) : null);

                if ($finEfectivo && $fechaInicioCarbon->between($inicioContrato, $finEfectivo)) {
                    $puedeCrear = false;
                    $mensaje = 'La fecha de inicio se solapa con un contrato existente que finalizó el ' . $finEfectivo->format('d/m/Y') . '.';
                    break;
                }
                if (!$finEfectivo && $fechaInicioCarbon->gte($inicioContrato)) {
                    $puedeCrear = false;
                    $mensaje = 'La persona ya tiene un contrato activo indefinido. Debe finalizarlo antes de crear uno nuevo.';
                    break;
                }
            }

            // --- Lógica de Clasificación (si no hay solapamiento) ---
            if ($puedeCrear) {
                $ultimoContrato = $contratos->first();
                $finContratoOriginal = $ultimoContrato->fin_contrato ? Carbon::parse($ultimoContrato->fin_contrato) : null;
                $fechaRenuncia = $ultimoContrato->fecha_renuncia ? Carbon::parse($ultimoContrato->fecha_renuncia) : null;

                // 2. Regla "Contrato por baja" (Máxima prioridad si hay historial)
                if ($fechaRenuncia && $finContratoOriginal && $fechaInicioCarbon->between($fechaRenuncia->copy()->addDay(), $finContratoOriginal)) {
                    $tipoMovimiento = 'Contrato por baja';
                }
                // 3. Regla "Contrato por renovación"
                elseif (!$fechaRenuncia && $finContratoOriginal && $fechaInicioCarbon->eq($finContratoOriginal->copy()->addDay())) {
                    $tipoMovimiento = 'Contrato por renovación';
                }
                // 4. Si no es ninguna de las anteriores, se queda como "Contrato por reingreso" (ya asignado).
            }
        }

        if (!$puedeCrear) {
            return [
                'ok' => false,
                'error' => $mensaje,
                'puede_crear' => false,
                'contrato_activo' => $contratoActivo ? [
                    'id' => $contratoActivo->id_contrato,
                    'inicio' => $contratoActivo->inicio_contrato,
                    'fin' => $contratoActivo->fin_contrato
                ] : null
            ];
        }

        // 5. Generar token de seguridad para el siguiente paso
        $token = Str::random(40);

        // Guardar token en sesion para validar en el store
        session()->put('contrato_token', [
            'token' => $token,
            'id_persona' => $persona->id_persona,
            'fecha_inicio' => $fechaInicio,
            'tipo_movimiento' => $tipoMovimiento,
            'expires_at' => now()->addMinutes(30)
        ]);

        // 6. Si tiene historial, obtener datos del ultimo contrato para pre-cargar
        $datosUltimoContrato = null;
        if ($tieneHistorial) {
            $ultimoContrato = $contratos->first();

            // Obtener el ultimo movimiento del contrato (el mas reciente)
            $ultimoMovimiento = \App\Models\ContratoMovimiento::where('id_contrato', $ultimoContrato->id_contrato)
                ->orderBy('inicio', 'desc')
                ->first();

            // Usar datos del movimiento si existe, sino del contrato
            $datosUltimoContrato = [
                'id_cargo' => $ultimoMovimiento->id_cargo ?? $ultimoContrato->id_cargo,
                'id_planilla' => $ultimoMovimiento->id_planilla ?? $ultimoContrato->id_planilla,
                'id_fp' => $ultimoMovimiento->id_fp ?? $ultimoContrato->id_fp,
                'id_condicion' => $ultimoMovimiento->id_condicion ?? $ultimoContrato->id_condicion,
                'id_banco' => $ultimoMovimiento->id_banco ?? $ultimoContrato->id_banco,
                'id_moneda' => $ultimoMovimiento->id_moneda ?? $ultimoContrato->id_moneda,
                'id_centro_costo' => $ultimoMovimiento->id_centro_costo ?? $ultimoContrato->id_centro_costo,
                'id_familia' => $ultimoMovimiento->id_familia ?? $ultimoContrato->id_familia,
                'haber_basico' => $ultimoMovimiento->haber_basico ?? $ultimoContrato->haber_basico,
                'movilidad' => $ultimoMovimiento->movilidad ?? $ultimoContrato->movilidad ?? 0,
                'asignacion_familiar' => $ultimoMovimiento->asignacion_familiar ?? $ultimoContrato->asignacion_familiar ?? false,
                'numero_cuenta' => $ultimoMovimiento->numero_cuenta ?? $ultimoContrato->numero_cuenta,
                'codigo_interbancario' => $ultimoMovimiento->codigo_interbancario ?? $ultimoContrato->codigo_interbancario,
                'numero_cuenta_cts' => $ultimoMovimiento->numero_cuenta_cts ?? $ultimoContrato->numero_cuenta_cts,
                'codigo_interbancario_cts' => $ultimoMovimiento->codigo_interbancario_cts ?? $ultimoContrato->codigo_interbancario_cts,
                'periodo_prueba' => false, // Normalmente no aplica en renovaciones/reingresos
            ];
        }

        return [
            'ok' => true,
            'puede_crear' => true,
            'token' => $token,
            'id_persona' => $persona->id_persona,
            'tipo_movimiento' => $tipoMovimiento,
            'tiene_historial' => $tieneHistorial,
            'total_contratos' => $contratos->count(),
            'datos_ultimo_contrato' => $datosUltimoContrato,
            'persona' => [
                'id_persona' => $persona->id_persona,
                'numero_documento' => $persona->numero_documento,
                'tipo_documento' => $persona->tipo_documento,
                'nombres' => $persona->nombres,
                'apellido_paterno' => $persona->apellido_paterno,
                'apellido_materno' => $persona->apellido_materno,
                'nombre_completo' => $persona->nombre_completo
            ]
        ];
    }

    /**
     * Obtiene el historial de contratos de una persona con sus movimientos.
     *
     * @param int $idPersona
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerHistorial(int $idPersona)
    {
        return Contrato::with([
            'persona',
            'cargo',
            'movimientos.planilla',
            'movimientos.cargo'
        ])
        ->where('id_persona', $idPersona)
        ->orderBy('inicio_contrato', 'desc')
        ->limit(5)
        ->get();
    }

    /**
     * Crea un nuevo contrato con su movimiento inicial.
     *
     * @param array $datos
     * @param string $tipoMovimiento
     * @return array
     */
    public function crearContrato(array $datos, string $tipoMovimiento): array
    {
        try {
            \DB::beginTransaction();

            // 1. Crear el contrato
            $contrato = Contrato::create([
                'id_persona' => $datos['id_persona'],
                'id_cargo' => $datos['id_cargo'],
                'id_planilla' => $datos['id_planilla'],
                'id_fp' => $datos['id_fp'],
                'id_condicion' => $datos['id_condicion'],
                'asignacion_familiar' => $datos['asignacion_familiar'] ?? false,
                'haber_basico' => $datos['haber_basico'],
                'movilidad' => $datos['movilidad'] ?? 0,
                'id_banco' => $datos['id_banco'],
                'numero_cuenta' => $datos['numero_cuenta'] ?? null,
                'codigo_interbancario' => $datos['codigo_interbancario'] ?? null,
                'numero_cuenta_cts' => $datos['numero_cuenta_cts'] ?? null,
                'codigo_interbancario_cts' => $datos['codigo_interbancario_cts'] ?? null,
                'id_moneda' => $datos['id_moneda'],
                'id_familia' => $datos['id_familia'] ?? null,
                'inicio_contrato' => $datos['inicio_contrato'],
                'fin_contrato' => $datos['fin_contrato'],
                'periodo_prueba' => $datos['periodo_prueba'] ?? false,
                'id_centro_costo' => $datos['id_centro_costo'],
                'fecha_insercion' => now(),
            ]);

            // 2. Crear el movimiento inicial
            \App\Models\ContratoMovimiento::create([
                'id_contrato' => $contrato->id_contrato,
                'id_cargo' => $datos['id_cargo'],
                'id_planilla' => $datos['id_planilla'],
                'id_fp' => $datos['id_fp'],
                'id_condicion' => $datos['id_condicion'],
                'asignacion_familiar' => $datos['asignacion_familiar'] ?? false,
                'haber_basico' => $datos['haber_basico'],
                'movilidad' => $datos['movilidad'] ?? 0,
                'id_banco' => $datos['id_banco'],
                'numero_cuenta' => $datos['numero_cuenta'] ?? null,
                'codigo_interbancario' => $datos['codigo_interbancario'] ?? null,
                'numero_cuenta_cts' => $datos['numero_cuenta_cts'] ?? null,
                'codigo_interbancario_cts' => $datos['codigo_interbancario_cts'] ?? null,
                'id_moneda' => $datos['id_moneda'],
                'id_familia' => $datos['id_familia'] ?? null,
                'inicio' => $datos['inicio_contrato'],
                'fin' => $datos['fin_contrato'],
                'id_centro_costo' => $datos['id_centro_costo'],
                'tipo_movimiento' => $tipoMovimiento,
                'fecha_insercion' => now(),
            ]);

            \DB::commit();

            return [
                'ok' => true,
                'success' => true,
                'mensaje' => 'Contrato creado exitosamente',
                'id_contrato' => $contrato->id_contrato
            ];

        } catch (\Exception $e) {
            \DB::rollBack();

            return [
                'ok' => false,
                'error' => 'Error al crear el contrato: ' . $e->getMessage()
            ];
        }
    }
}
