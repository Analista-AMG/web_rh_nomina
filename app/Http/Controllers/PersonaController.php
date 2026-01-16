<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PersonaController extends Controller
{
    public function index(Request $request)
    {
        // Verificar permiso
        abort_unless(auth()->user()->can('personas.view'), 403);

        // Iniciamos la consulta
        $query = Persona::with('contratos');

        // Filtro por Nombre (Busca en Nombres o Apellidos)
        if ($request->filled('search_name')) {
            $term = $request->search_name;
            $query->where(function($q) use ($term) {
                $q->where('nombres', 'like', "%{$term}%")
                  ->orWhere('apellido_paterno', 'like', "%{$term}%")
                  ->orWhere('apellido_materno', 'like', "%{$term}%");
            });
        }

        // Filtro por Documento
        if ($request->filled('search_doc')) {
            $query->where('numero_documento', 'like', "%{$request->search_doc}%");
        }

        // Paginamos el resultado filtrado
        // appends($request->all()) es CLAVE: mantiene los filtros en los enlaces de la paginación
        $personas = $query->paginate(7)->appends($request->all());

        // --- KPIs (Calculados sobre el TOTAL, no sobre la búsqueda) ---
        
        // Total Real
        $totalPersonas = Persona::count();
        
        // Nuevas
        $nuevas = Persona::whereMonth('fecha_registro', Carbon::now()->month)
                         ->whereYear('fecha_registro', Carbon::now()->year)
                         ->count();

        // Activas (Cálculo optimizado)
        $hoy = Carbon::now()->format('Y-m-d');
        $activas = Persona::whereHas('contratos', function($q) use ($hoy) {
            $q->where('inicio_contrato', '<=', $hoy)
              ->where(function($sub) use ($hoy) {
                  $sub->whereNull('fecha_renuncia')
                      ->whereNull('fin_contrato')
                      ->orWhere('fecha_renuncia', '>=', $hoy)
                      ->orWhere('fin_contrato', '>=', $hoy);
              });
        })->count();

        $kpis = [
            'total' => $totalPersonas,
            'nuevas' => $nuevas,
            'activas' => $activas,
        ];

        $paises = DB::table('bronze.dim_paises')->orderBy('nombre')->get();
        $departamentos = DB::table('bronze.dim_departamentos')->orderBy('nombre')->get();
        $distritos = DB::table('bronze.dim_distritos')->orderBy('nombre')->get();

        return view('personas.index', compact('personas', 'kpis', 'paises', 'departamentos', 'distritos'));
    }

    public function create()
    {
        return view('personas.create');
    }

    public function store(Request $request)
    {
        // Verificar permiso
        abort_unless(auth()->user()->can('personas.create'), 403);

        $validated = $request->validate([
            'nombres' => 'required|max:255',
            'apellido_paterno' => 'required|max:255',
            'numero_documento' => 'required|unique:bronze.dim_persona,numero_documento',
            // Agrega más validaciones según necesites
        ]);

        Persona::create($request->all());

        return redirect()->route('personas.index')->with('success', 'Persona registrada correctamente.');
    }

    public function update(Request $request, $id)
    {
        // Verificar permiso
        if (auth()->user()->cannot('personas.edit')) {
            return response()->json(['error' => 'No tienes permiso para editar personas'], 403);
        }

        // Validamos los datos básicos
        $validated = $request->validate([
            'numero_documento' => 'required|max:20',
            'nombres' => 'required|max:255',
            'apellido_paterno' => 'required|max:255',
            'apellido_materno' => 'required|max:255',
            'tipo_documento' => 'required',
            // Agrega más validaciones según necesites
        ]);

        $persona = Persona::findOrFail($id);
        
        $persona->update([
            'numero_documento' => $request->numero_documento,
            'tipo_documento' => $request->tipo_documento,
            'nacionalidad' => $request->nacionalidad,
            'nombres' => $request->nombres,
            'apellido_paterno' => $request->apellido_paterno,
            'apellido_materno' => $request->apellido_materno,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'genero' => $request->genero,
            'correo_electronico_personal' => $request->correo_electronico_personal,
            'correo_electronico_corporativo' => $request->correo_electronico_corporativo,
            'direccion' => $request->direccion,
        ]);

        return response()->json(['success' => true, 'message' => 'Persona actualizada correctamente']);
    }
}
