<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Persona;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Muestra el panel principal con métricas.
     */
    public function index()
    {
        // 1. Métricas de Personas
        $totalPersonas = Persona::count();
        // Ejemplo: Personas registradas este mes
        $nuevasPersonas = Persona::whereMonth('fecha_registro', now()->month)
                                 ->whereYear('fecha_registro', now()->year)
                                 ->count();

        // 2. Métricas de Contratos (Ejemplo: Activos)
        // Asumiendo que 'estado' 1 es activo
        $contratosActivos = Contrato::where('estado', 1)->count();

        // 3. Empaquetar métricas para la vista
        $metrics = [
            'empleados_total' => $totalPersonas,
            'nuevos_mes' => $nuevasPersonas,
            'contratos_activos' => $contratosActivos,
            // Aquí tu equipo puede agregar más: nómina total, vencimientos, etc.
        ];

        return view('dashboard.index', compact('metrics'));
    }
}