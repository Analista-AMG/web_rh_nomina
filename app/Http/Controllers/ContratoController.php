<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ContratoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Iniciamos la consulta con relaciones para evitar N+1
        $query = Contrato::with(['persona', 'cargo']);

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

        // Filtro por Estado (opcional, si quisieras agregar un select en la vista)
        if ($request->filled('filter_status')) {
            $query->where('estado', $request->filter_status);
        }

        // Ordenar: Activos primero, luego por fecha inicio descendente
        $query->orderBy('estado', 'desc')
              ->orderBy('inicio_contrato', 'desc');

        // Paginación
        $contratos = $query->paginate(7)->appends($request->all());

        // --- KPIs ---
        $hoy = Carbon::now();
        
        // 1. Total Contratos Históricos
        $total = Contrato::count();

        // 2. Activos (Estado = 1 y fechas válidas)
        $activos = Contrato::where('estado', 1)
            ->where('inicio_contrato', '<=', $hoy)
            ->where(function($q) use ($hoy) {
                $q->whereNull('fin_contrato')
                  ->orWhere('fin_contrato', '>=', $hoy);
            })->count();

        // 3. Por Vencer (Activos que terminan en los próximos 30 días)
        $porVencer = Contrato::where('estado', 1)
            ->whereNotNull('fin_contrato')
            ->whereBetween('fin_contrato', [$hoy, $hoy->copy()->addDays(30)])
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

    // ... Store, Show, Edit, Update, Destroy (Pendientes para tu equipo)
}