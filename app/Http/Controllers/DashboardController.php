<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Persona;
use App\Services\JerarquiaService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected JerarquiaService $jerarquia) {}

    public function index()
    {
        $user = auth()->user();
        $personaIds = $this->jerarquia->personaIdsVisibles($user);
        $hoy = now();

        $pBase = fn() => $this->jerarquia->aplicarFiltroPersonas(Persona::query(), $personaIds, 'id');
        $cBase = fn() => $this->jerarquia->aplicarFiltroPersonas(Contrato::query(), $personaIds, 'persona_id');

        $metrics = [
            'empleados_total'      => $pBase()->count(),
            'nuevos_mes'           => $pBase()->whereMonth('created_at', $hoy->month)->whereYear('created_at', $hoy->year)->count(),
            'contratos_activos'    => $cBase()->activos()->count(),
            'contratos_por_vencer' => $cBase()->activos()
                ->whereRaw(Contrato::FIN_EFECTIVO . ' BETWEEN ? AND ?', [
                    $hoy->toDateString(),
                    $hoy->copy()->addDays(30)->toDateString(),
                ])
                ->count(),
        ];

        return view('dashboard.index', compact('metrics'));
    }
}