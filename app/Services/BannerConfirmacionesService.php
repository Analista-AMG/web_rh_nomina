<?php

namespace App\Services;

use App\Models\Contrato;
use App\Models\ControlConfirmacion;
use App\Models\Persona;
use App\Models\UserAsignacion;
use App\Models\Scopes\AlcanceUsuarioScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BannerConfirmacionesService
{
    public function calcular(): ?object
    {
        $user = auth()->user();
        if (!$user) return null;

        // Días 1-3 del mes: notificar sobre el mes anterior
        $hoy    = now();
        $periodo = $hoy->day <= 3
            ? $hoy->copy()->subMonth()->format('Y-m')
            : $hoy->format('Y-m');

        return $this->computar($user, $periodo);
    }

    private function computar($user, string $periodo): object
    {
        $inicio = Carbon::parse($periodo . '-01')->startOfDay();
        $fin    = $inicio->copy()->endOfMonth()->endOfDay();

        $esRrhh        = $user->hasRole('Administrador') || $user->hasRole('Recursos Humanos');
        $pendientes    = 0;
        $movCambio     = 0;
        $requierenRrhh = 0;

        if ($esRrhh) {
            $requierenRrhh = ControlConfirmacion::where('periodo', $periodo)
                ->where('estado', ControlConfirmacion::ESTADO_REQUIERE_ACTUALIZACION)
                ->count();
        } else {
            $subordinados = UserAsignacion::where('estado', UserAsignacion::ESTADO_APROBADO)
                ->where('superior_id', $user->id)
                ->where('fecha_inicio', '<=', $fin->toDateString())
                ->where(fn($q) => $q->whereNull('fecha_fin')
                                    ->orWhere('fecha_fin', '>=', $inicio->toDateString()))
                ->with('usuario')
                ->get();

            if ($subordinados->isEmpty()) {
                return $this->objeto($periodo, $esRrhh);
            }

            // Excluir personas que ya tienen nuevo supervisor en el periodo
            $conNuevoSup = UserAsignacion::where('estado', UserAsignacion::ESTADO_APROBADO)
                ->whereIn('user_id', $subordinados->pluck('user_id')->unique())
                ->where('superior_id', '!=', $user->id)
                ->where('fecha_inicio', '<=', $fin->toDateString())
                ->where(fn($q) => $q->whereNull('fecha_fin')
                                    ->orWhere('fecha_fin', '>=', $inicio->toDateString()))
                ->pluck('user_id')->unique();

            $subordinados = $subordinados->reject(fn($a) => $conNuevoSup->contains($a->user_id));

            if ($subordinados->isEmpty()) {
                return $this->objeto($periodo, $esRrhh);
            }

            $docs = $subordinados->map(fn($a) => $a->usuario?->numero_documento)->filter()->unique()->values();

            $personaIds = Persona::withoutGlobalScope(AlcanceUsuarioScope::class)
                ->whereIn('numero_documento', $docs)
                ->pluck('id');

            if ($personaIds->isEmpty()) {
                return $this->objeto($periodo, $esRrhh);
            }

            $contratoIds = Contrato::withoutGlobalScope(AlcanceUsuarioScope::class)
                ->whereIn('persona_id', $personaIds)
                ->where('inicio_contrato', '<=', $fin->toDateString())
                ->where(fn($q) => $q->whereNull('fin_contrato')
                                    ->orWhere('fin_contrato', '>=', $inicio->toDateString()))
                ->where(fn($q) => $q->whereNull('fecha_renuncia')
                                    ->orWhere('fecha_renuncia', '>=', $inicio->toDateString()))
                ->pluck('id');

            if ($contratoIds->isEmpty()) {
                return $this->objeto($periodo, $esRrhh);
            }

            // Pendientes: contratos sin confirmación activa (no confirmado ni requiere_actualizacion)
            $confirmadosIds = ControlConfirmacion::where('periodo', $periodo)
                ->whereIn('contrato_id', $contratoIds)
                ->whereIn('estado', [
                    ControlConfirmacion::ESTADO_CONFIRMADO,
                    ControlConfirmacion::ESTADO_REQUIERE_ACTUALIZACION,
                ])
                ->pluck('contrato_id');

            $pendientes = $contratoIds->diff($confirmadosIds)->count();

            // movCambio: confirmados cuyo movimiento cambió después del snapshot
            $confirmadosIds2 = ControlConfirmacion::where('periodo', $periodo)
                ->whereIn('contrato_id', $contratoIds)
                ->where('estado', ControlConfirmacion::ESTADO_CONFIRMADO)
                ->pluck('contrato_id');

            if ($confirmadosIds2->isNotEmpty()) {
                $ids = $confirmadosIds2->map(fn($id) => (int) $id)->implode(',');
                // Replica exacta de movimientoCambio(): detecta tanto nuevo row SCD2
                // como edición directa de campos en el mismo movimiento (mismo id).
                $result = DB::selectOne("
                    SELECT COUNT(*) AS cnt
                    FROM dbo.control_confirmaciones_personal conf
                    CROSS APPLY (
                        SELECT TOP 1 id, planilla_id, cargo_id, centro_costo_id,
                                     familia_id, condicion_id, haber_basico
                        FROM nomina.fact_contratos_movimientos
                        WHERE contrato_id = conf.contrato_id
                          AND inicio <= ?
                          AND (fin IS NULL OR fin >= ?)
                        ORDER BY inicio DESC
                    ) mov_actual
                    WHERE conf.periodo = ?
                      AND conf.contrato_id IN ({$ids})
                      AND conf.estado = 'confirmado'
                      AND (
                        conf.movimiento_id    != mov_actual.id
                        OR conf.planilla_id     != mov_actual.planilla_id
                        OR conf.cargo_id        != mov_actual.cargo_id
                        OR conf.centro_costo_id != mov_actual.centro_costo_id
                        OR conf.familia_id      != mov_actual.familia_id
                        OR conf.condicion_id    != mov_actual.condicion_id
                        OR conf.haber_basico    != mov_actual.haber_basico
                      )
                ", [$fin->toDateString(), $inicio->toDateString(), $periodo]);

                $movCambio = (int) ($result?->cnt ?? 0);
            }
        }

        return (object) compact('periodo', 'pendientes', 'movCambio', 'requierenRrhh', 'esRrhh');
    }

    private function objeto(string $periodo, bool $esRrhh): object
    {
        return (object) [
            'periodo'       => $periodo,
            'pendientes'    => 0,
            'movCambio'     => 0,
            'requierenRrhh' => 0,
            'esRrhh'        => $esRrhh,
        ];
    }
}
