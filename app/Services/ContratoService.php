<?php

namespace App\Services;

use App\Models\Persona;
use App\Models\Contrato;
use App\Models\User;
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
        $contratos = Contrato::where('persona_id', $persona->id)
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
                $finEfectivo = $contrato->fecha_renuncia ?? $contrato->fin_contrato;

                if ($finEfectivo && $fechaInicioCarbon->between($contrato->inicio_contrato, $finEfectivo)) {
                    $puedeCrear = false;
                    $contratoActivo = $contrato;
                    $mensaje = 'La fecha de inicio se solapa con un contrato existente que finalizó el ' . $finEfectivo->format('d/m/Y') . '.';
                    break;
                }
                if (!$finEfectivo && $fechaInicioCarbon->gte($contrato->inicio_contrato)) {
                    $puedeCrear = false;
                    $contratoActivo = $contrato;
                    $mensaje = 'La persona ya tiene un contrato activo indefinido. Debe finalizarlo antes de crear uno nuevo.';
                    break;
                }
            }

            // --- Lógica de Clasificación (si no hay solapamiento) ---
            if ($puedeCrear) {
                $ultimoContrato = $contratos->first();
                $finContratoOriginal = $ultimoContrato->fin_contrato;
                $fechaRenuncia = $ultimoContrato->fecha_renuncia;

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
                    'id' => $contratoActivo->id,
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
            'persona_id' => $persona->id,
            'fecha_inicio' => $fechaInicio,
            'tipo_movimiento' => $tipoMovimiento,
            'expires_at' => now()->addMinutes(30)
        ]);

        // 6. Si tiene historial, obtener datos del ultimo contrato para pre-cargar
        $datosUltimoContrato = null;
        if ($tieneHistorial) {
            $ultimoContrato = $contratos->first();

            // Obtener el ultimo movimiento del contrato (el mas reciente)
            $ultimoMovimiento = \App\Models\ContratoMovimiento::where('contrato_id', $ultimoContrato->id)
                ->orderBy('inicio', 'desc')
                ->first();

            // Usar datos del movimiento si existe, sino del contrato
            $datosUltimoContrato = [
                'cargo_id' => $ultimoMovimiento->cargo_id ?? $ultimoContrato->cargo_id,
                'planilla_id' => $ultimoMovimiento->planilla_id ?? $ultimoContrato->planilla_id,
                'fondo_pensiones_id' => $ultimoMovimiento->fondo_pensiones_id ?? $ultimoContrato->fondo_pensiones_id,
                'condicion_id' => $ultimoMovimiento->condicion_id ?? $ultimoContrato->condicion_id,
                'banco_id' => $ultimoMovimiento->banco_id ?? $ultimoContrato->banco_id,
                'moneda_id' => $ultimoMovimiento->moneda_id ?? $ultimoContrato->moneda_id,
                'centro_costo_id' => $ultimoMovimiento->centro_costo_id ?? $ultimoContrato->centro_costo_id,
                'familia_id' => $ultimoMovimiento->familia_id ?? $ultimoContrato->familia_id,
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
            'persona_id' => $persona->id,
            'tipo_movimiento' => $tipoMovimiento,
            'tiene_historial' => $tieneHistorial,
            'total_contratos' => $contratos->count(),
            'datos_ultimo_contrato' => $datosUltimoContrato,
            'persona' => [
                'persona_id' => $persona->id,
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
        ->where('persona_id', $idPersona)
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

            // Campos compartidos entre contrato y movimiento inicial
            $camposComunes = [
                'cargo_id'               => $datos['cargo_id'],
                'planilla_id'            => $datos['planilla_id'],
                'fondo_pensiones_id'     => $datos['fondo_pensiones_id'],
                'condicion_id'           => $datos['condicion_id'],
                'asignacion_familiar'    => $datos['asignacion_familiar'] ?? false,
                'haber_basico'           => $datos['haber_basico'],
                'movilidad'              => $datos['movilidad'] ?? 0,
                'banco_id'               => $datos['banco_id'],
                'numero_cuenta'          => $datos['numero_cuenta'] ?? null,
                'codigo_interbancario'   => $datos['codigo_interbancario'] ?? null,
                'numero_cuenta_cts'      => $datos['numero_cuenta_cts'] ?? null,
                'codigo_interbancario_cts' => $datos['codigo_interbancario_cts'] ?? null,
                'moneda_id'              => $datos['moneda_id'],
                'familia_id'             => $datos['familia_id'] ?? null,
                'centro_costo_id'        => $datos['centro_costo_id'],
                'suspension_renta'       => $datos['suspension_renta'] ?? false,
            ];

            // 1. Crear el contrato
            $contrato = Contrato::create(array_merge($camposComunes, [
                'persona_id'      => $datos['persona_id'],
                'inicio_contrato' => $datos['inicio_contrato'],
                'fin_contrato'    => $datos['fin_contrato'],
                'periodo_prueba'  => $datos['periodo_prueba'] ?? false,
            ]));

            // 2. Crear el movimiento inicial
            \App\Models\ContratoMovimiento::create(array_merge($camposComunes, [
                'contrato_id'    => $contrato->id,
                'inicio'         => $datos['inicio_contrato'],
                'fin'            => $datos['fin_contrato'],
                'tipo_movimiento'=> $tipoMovimiento,
            ]));

            // 3. Crear cuenta de usuario si no existe aún
            $persona = Persona::find($datos['persona_id']);
            if ($persona && $persona->numero_documento) {
                $existeUser = User::where('numero_documento', $persona->numero_documento)->exists();
                if (!$existeUser) {
                    $primerNombre = explode(' ', trim($persona->nombres))[0] ?? '';
                    User::create([
                        'name'             => trim(implode(' ', array_filter([
                            $persona->apellido_paterno,
                            $persona->apellido_materno,
                            $primerNombre,
                        ]))),
                        'numero_documento' => $persona->numero_documento,
                        'email'            => "{$persona->numero_documento}@amg.local",
                        'password'         => 'password123',
                        'activo'           => true,
                    ]);
                }
            }

            \DB::commit();

            return [
                'ok' => true,
                'success' => true,
                'mensaje' => 'Contrato creado exitosamente',
                'contrato_id' => $contrato->id
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
