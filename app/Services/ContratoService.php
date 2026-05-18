<?php

namespace App\Services;

use App\Models\Persona;
use App\Models\Contrato;
use App\Models\ContratoMovimiento;
use App\Models\Planilla;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ContratoService
{
    /**
     * Evalua si se puede crear un contrato para una persona en una fecha determinada.
     *
     * @param string $numeroDocumento
     * @param string $fechaInicio
     * @param string $regimen  Régimen de la planilla elegida (General, MYPE, RHE)
     * @return array
     */
    public function evaluarContrato(string $numeroDocumento, string $fechaInicio, string $regimen): array
    {
        // 1. Buscar la persona por numero de documento
        $persona = Persona::where('numero_documento', $numeroDocumento)->first();

        if (!$persona) {
            return [
                'ok'          => false,
                'error'       => 'No se encontró una persona con el número de documento ingresado.',
                'puede_crear' => false,
            ];
        }

        // 2. Obtener todos los contratos de la persona con su último movimiento
        $contratos = Contrato::where('persona_id', $persona->id)
            ->with(['movimientos' => fn ($q) => $q->orderBy('inicio', 'desc')->limit(1)])
            ->orderBy('inicio_contrato', 'desc')
            ->get();

        $esRheNuevo        = $regimen === 'RHE';
        $fechaInicioCarbon = Carbon::parse($fechaInicio);
        $tieneHistorial    = $contratos->isNotEmpty();
        $tipoMovimiento    = 'Contrato inicial';
        $puedeCrear        = true;
        $mensaje           = '';
        $contratoActivo    = null;

        if ($tieneHistorial) {
            $tipoMovimiento = 'Contrato por reingreso';

            // --- Verificación de Solapamiento (solo contra contratos del mismo régimen) ---
            foreach ($contratos as $contrato) {
                $ultimoMov      = $contrato->movimientos->first();
                $planillaExist  = $ultimoMov ? Planilla::find($ultimoMov->planilla_id) : null;
                $esRheExistente = $planillaExist?->tipo_pago === 'RXH';

                // Planilla y RHE pueden coexistir — no validar entre tipos distintos
                if ($esRheExistente !== $esRheNuevo) {
                    continue;
                }

                $finEfectivo = $contrato->fecha_renuncia ?? $contrato->fin_contrato;

                if ($finEfectivo && $fechaInicioCarbon->between($contrato->inicio_contrato, $finEfectivo)) {
                    $puedeCrear     = false;
                    $contratoActivo = $contrato;
                    $mensaje        = 'La fecha de inicio se solapa con un contrato existente que finalizó el ' . $finEfectivo->format('d/m/Y') . '.';
                    break;
                }

                if (!$finEfectivo && $fechaInicioCarbon->gte($contrato->inicio_contrato)) {
                    $puedeCrear     = false;
                    $contratoActivo = $contrato;
                    $mensaje        = 'La persona ya tiene un contrato activo indefinido de este tipo. Debe finalizarlo antes de crear uno nuevo.';
                    break;
                }
            }

            // --- Clasificación del tipo de movimiento (si no hay solapamiento) ---
            if ($puedeCrear) {
                // Buscar el contrato más reciente del mismo régimen para clasificar
                $ultimoDelMismoTipo = $contratos->first(function ($contrato) use ($esRheNuevo) {
                    $ultimoMov     = $contrato->movimientos->first();
                    $planilla      = $ultimoMov ? Planilla::find($ultimoMov->planilla_id) : null;
                    return ($planilla?->tipo_pago === 'RXH') === $esRheNuevo;
                });

                if ($ultimoDelMismoTipo) {
                    $finContratoOriginal = $ultimoDelMismoTipo->fin_contrato;
                    $fechaRenuncia       = $ultimoDelMismoTipo->fecha_renuncia;

                    if ($fechaRenuncia && $finContratoOriginal && $fechaInicioCarbon->between($fechaRenuncia->copy()->addDay(), $finContratoOriginal)) {
                        $tipoMovimiento = 'Contrato por baja';
                    } elseif (!$fechaRenuncia && $finContratoOriginal && $fechaInicioCarbon->eq($finContratoOriginal->copy()->addDay())) {
                        $tipoMovimiento = 'Contrato por renovación';
                    }
                }
            }
        }

        if (!$puedeCrear) {
            return [
                'ok'             => false,
                'error'          => $mensaje,
                'puede_crear'    => false,
                'contrato_activo' => $contratoActivo ? [
                    'id'     => $contratoActivo->id,
                    'inicio' => $contratoActivo->inicio_contrato,
                    'fin'    => $contratoActivo->fin_contrato,
                ] : null,
            ];
        }

        // 3. Generar token de seguridad
        $token = Str::random(40);

        session()->put('contrato_token', [
            'token'           => $token,
            'persona_id'      => $persona->id,
            'fecha_inicio'    => $fechaInicio,
            'tipo_movimiento' => $tipoMovimiento,
            'regimen'         => $regimen,
            'expires_at'      => now()->addMinutes(30),
        ]);

        // 4. Pre-cargar datos del último movimiento del mismo régimen para el formulario
        $datosUltimoContrato = null;
        if ($tieneHistorial) {
            $ultimoDelMismoTipo = $contratos->first(function ($contrato) use ($esRheNuevo) {
                $ultimoMov = $contrato->movimientos->first();
                $planilla  = $ultimoMov ? Planilla::find($ultimoMov->planilla_id) : null;
                return ($planilla?->tipo_pago === 'RXH') === $esRheNuevo;
            });

            if ($ultimoDelMismoTipo) {
                $ultimoMovimiento = $ultimoDelMismoTipo->movimientos->first();

                if ($ultimoMovimiento) {
                    $datosUltimoContrato = [
                        'cargo_id'                 => $ultimoMovimiento->cargo_id,
                        'planilla_id'              => $ultimoMovimiento->planilla_id,
                        'fondo_pensiones_id'       => $ultimoMovimiento->fondo_pensiones_id,
                        'condicion_id'             => $ultimoMovimiento->condicion_id,
                        'banco_id'                 => $ultimoMovimiento->banco_id,
                        'moneda_id'                => $ultimoMovimiento->moneda_id,
                        'centro_costo_id'          => $ultimoMovimiento->centro_costo_id,
                        'familia_id'               => $ultimoMovimiento->familia_id,
                        'haber_basico'             => $ultimoMovimiento->haber_basico,
                        'movilidad'                => $ultimoMovimiento->movilidad ?? 0,
                        'asignacion_familiar'      => $ultimoMovimiento->asignacion_familiar ?? false,
                        'numero_cuenta'            => $ultimoMovimiento->numero_cuenta,
                        'codigo_interbancario'     => $ultimoMovimiento->codigo_interbancario,
                        'numero_cuenta_cts'        => $ultimoMovimiento->numero_cuenta_cts,
                        'codigo_interbancario_cts' => $ultimoMovimiento->codigo_interbancario_cts,
                        'periodo_prueba'           => false,
                    ];
                }
            }
        }

        return [
            'ok'                    => true,
            'puede_crear'           => true,
            'token'                 => $token,
            'persona_id'            => $persona->id,
            'tipo_movimiento'       => $tipoMovimiento,
            'tiene_historial'       => $tieneHistorial,
            'total_contratos'       => $contratos->count(),
            'datos_ultimo_contrato' => $datosUltimoContrato,
            'persona' => [
                'persona_id'       => $persona->id,
                'numero_documento' => $persona->numero_documento,
                'tipo_documento'   => $persona->tipo_documento,
                'nombres'          => $persona->nombres,
                'apellido_paterno' => $persona->apellido_paterno,
                'apellido_materno' => $persona->apellido_materno,
                'nombre_completo'  => $persona->nombre_completo,
            ],
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
            'movimientoActual.cargo',
            'movimientoActual.planilla',
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

            // 1. Crear el ancla (solo campos de identidad)
            $contrato = Contrato::create([
                'persona_id'      => $datos['persona_id'],
                'inicio_contrato' => $datos['inicio_contrato'],
                'fin_contrato'    => $datos['fin_contrato'],
                'periodo_prueba'  => $datos['periodo_prueba'] ?? false,
            ]);

            // 2. Crear el movimiento inicial (toda la información económica/operativa)
            ContratoMovimiento::create([
                'contrato_id'              => $contrato->id,
                'tipo_movimiento'          => $tipoMovimiento,
                'cargo_id'                 => $datos['cargo_id'],
                'planilla_id'              => $datos['planilla_id'],
                'fondo_pensiones_id'       => $datos['fondo_pensiones_id'],
                'condicion_id'             => $datos['condicion_id'],
                'asignacion_familiar'      => $datos['asignacion_familiar'] ?? false,
                'haber_basico'             => $datos['haber_basico'],
                'movilidad'                => $datos['movilidad'] ?? 0,
                'banco_id'                 => $datos['banco_id'],
                'numero_cuenta'            => $datos['numero_cuenta'] ?? null,
                'codigo_interbancario'     => $datos['codigo_interbancario'] ?? null,
                'numero_cuenta_cts'        => $datos['numero_cuenta_cts'] ?? null,
                'codigo_interbancario_cts' => $datos['codigo_interbancario_cts'] ?? null,
                'moneda_id'                => $datos['moneda_id'],
                'familia_id'               => $datos['familia_id'] ?? null,
                'centro_costo_id'          => $datos['centro_costo_id'],
                'suspension_renta'         => $datos['suspension_renta'] ?? false,
                'inicio'                   => $datos['inicio_contrato'],
                'fin'                      => $datos['fin_contrato'],
                'estado'                   => true,
            ]);

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
