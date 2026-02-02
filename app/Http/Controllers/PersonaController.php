<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Persona;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PersonaController extends Controller
{
    public function index(Request $request)
    {
        // Verificar permiso
        abort_unless(auth()->user()->can('personas.view'), 403);

        // Iniciamos la consulta
        $query = Persona::with('contratos')
            ->orderByDesc('fecha_registro');

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
        $personas = $query->paginate(7)->appends($request->all());

        // KPIs (calculados sobre el total)
        $totalPersonas = Persona::count();
        $nuevas = Persona::whereMonth('fecha_registro', Carbon::now()->month)
                         ->whereYear('fecha_registro', Carbon::now()->year)
                         ->count();

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
        $provincias = DB::table('bronze.dim_provincias')->orderBy('nombre')->get();
        $distritos = DB::table('bronze.dim_distritos')
    ->select('id', 'nombre', 'provincia_id')
    ->get();


        return view('personas.index', compact('personas', 'kpis', 'paises', 'departamentos', 'provincias', 'distritos'));
    }

    public function exportExcel(Request $request)
    {
        abort_unless(auth()->user()->can('personas.view'), 403);

        $query = Persona::with('contratos')
            ->orderByDesc('fecha_registro');

        if ($request->filled('search_name')) {
            $term = $request->search_name;
            $query->where(function ($q) use ($term) {
                $q->where('nombres', 'like', "%{$term}%")
                  ->orWhere('apellido_paterno', 'like', "%{$term}%")
                  ->orWhere('apellido_materno', 'like', "%{$term}%");
            });
        }

        if ($request->filled('search_doc')) {
            $query->where('numero_documento', 'like', "%{$request->search_doc}%");
        }

        $personas = $query->get();

        $paises = DB::table('bronze.dim_paises')->pluck('nombre', 'id')->all();
        $departamentos = DB::table('bronze.dim_departamentos')->pluck('nombre', 'id')->all();
        $provincias = DB::table('bronze.dim_provincias')->pluck('nombre', 'id')->all();
        $distritos = DB::table('bronze.dim_distritos')->pluck('nombre', 'id')->all();

        $mapGenero = function ($value) {
            $val = is_numeric($value) ? (int) $value : $value;
            if ($val === 1) return 'Masculino';
            if ($val === 2) return 'Femenino';
            if ($val === 3) return 'Otros';
            return $value ?? '';
        };

        $mapLookup = function ($value, array $map) {
            $text = $value ?? '';
            if ($text === '') {
                return '';
            }
            if (is_numeric($text) && isset($map[(int) $text])) {
                return $map[(int) $text];
            }
            return $text;
        };

        $callback = function () use (
            $personas,
            $mapGenero,
            $mapLookup,
            $paises,
            $departamentos,
            $provincias,
            $distritos
        ) {
            $sheet = new Spreadsheet();
            $sheet->getProperties()->setTitle('Personas');
            $active = $sheet->getActiveSheet();
            $active->setTitle('Personas');

            $rows = [[
                'ID Persona',
                'Numero Documento',
                'Apellido Paterno',
                'Apellido Materno',
                'Nombres',
                'Tipo Documento',
                'Fecha Nacimiento',
                'Genero',
                'Pais',
                'Departamento',
                'Provincia',
                'Distrito',
                'Numero Telefonico',
                'Correo Personal',
                'Correo Corporativo',
                'Direccion',
                'Fecha Registro',
            ]];

            foreach ($personas as $persona) {
                $fecha = $persona->fecha_nacimiento
                    ? \Carbon\Carbon::parse($persona->fecha_nacimiento)->format('d/m/Y')
                    : '';
                $fechaRegistro = $persona->fecha_registro
                    ? \Carbon\Carbon::parse($persona->fecha_registro)->format('d/m/Y H:i:s')
                    : '';

                $rows[] = [
                    $persona->id_persona ?? '',
                    $persona->numero_documento ?? '',
                    $persona->apellido_paterno ?? '',
                    $persona->apellido_materno ?? '',
                    $persona->nombres ?? '',
                    $persona->tipo_documento ?? '',
                    $fecha,
                    $mapGenero($persona->genero ?? ''),
                    $mapLookup($persona->pais ?? '', $paises),
                    $mapLookup($persona->departamento ?? '', $departamentos),
                    $mapLookup($persona->provincia ?? '', $provincias),
                    $mapLookup($persona->distrito ?? '', $distritos),
                    $persona->numero_telefonico ?? '',
                    $persona->correo_electronico_personal ?? '',
                    $persona->correo_electronico_corporativo ?? '',
                    $persona->direccion ?? '',
                    $fechaRegistro,
                ];
            }

            $active->fromArray($rows, null, 'A1', true);

            $writer = new Xlsx($sheet);
            $writer->save('php://output');
        };

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return response()->streamDownload($callback, 'personas.xlsx', $headers);
    }

    public function create()
    {
        return view('personas.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('personas.create'), 403);

        $uniqueTable = config('database.default').'.bronze.dim_persona';
        $maxBirthDate = now()->subYears(18)->format('Y-m-d');
        $validated = $request->validate(
            [
                'tipo_documento' => 'required',
                'numero_documento' => [
                    'required',
                    'max:20',
                    Rule::unique($uniqueTable, 'numero_documento'),
                ],
                'nombres' => 'required|max:255',
                'apellido_paterno' => 'required|max:255',
                'apellido_materno' => 'nullable|max:255',
                'fecha_nacimiento' => 'nullable|date|before_or_equal:'.$maxBirthDate,
                'genero' => 'nullable',
                'pais' => 'nullable',
                'departamento' => 'nullable',
                'provincia' => 'nullable',
                'distrito' => 'nullable',
                'direccion' => 'nullable|max:255',
                'numero_telefonico' => 'nullable|regex:/^\\d{1,9}$/',
                'correo_electronico_personal' => 'nullable|email|max:255',
                'correo_electronico_corporativo' => 'nullable|email|max:255',
            ],
            [
                'numero_documento.unique' => 'Persona ya se encuentra en la base de datos',
                'fecha_nacimiento.before_or_equal' => 'La persona debe ser mayor de 18 anos',
                'numero_telefonico.regex' => 'El numero telefonico debe tener maximo 9 digitos',
            ]
        );

        Persona::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Persona registrada correctamente',
        ]);
    }

    public function update(Request $request, $id)
    {
        if (auth()->user()->cannot('personas.edit')) {
            return response()->json(['error' => 'No tienes permiso para editar personas'], 403);
        }

        $uniqueTable = config('database.default').'.bronze.dim_persona';
        $maxBirthDate = now()->subYears(18)->format('Y-m-d');
        $validated = $request->validate(
            [
                'tipo_documento' => 'required',
                'numero_documento' => [
                    'required',
                    'max:20',
                    Rule::unique($uniqueTable, 'numero_documento')->ignore($id, 'id_persona'),
                ],
                'nombres' => 'required|max:255',
                'apellido_paterno' => 'required|max:255',
                'apellido_materno' => 'nullable|max:255',
                'fecha_nacimiento' => 'nullable|date|before_or_equal:'.$maxBirthDate,
                'genero' => 'nullable',
                'pais' => 'nullable',
                'departamento' => 'nullable',
                'provincia' => 'nullable',
                'distrito' => 'nullable',
                'direccion' => 'nullable|max:255',
                'numero_telefonico' => 'nullable|regex:/^\\d{1,9}$/',
                'correo_electronico_personal' => 'nullable|email|max:255',
                'correo_electronico_corporativo' => 'nullable|email|max:255',
            ],
            [
                'numero_documento.unique' => 'Persona ya se encuentra en la base de datos',
                'fecha_nacimiento.before_or_equal' => 'La persona debe ser mayor de 18 anos',
                'numero_telefonico.regex' => 'El numero telefonico debe tener maximo 9 digitos',
            ]
        );

        $persona = Persona::findOrFail($id);
        $persona->update($validated);

        return response()->json(['success' => true, 'message' => 'Persona actualizada correctamente']);
    }

    public function lookupReniec(string $numeroDocumento)
    {
        abort_unless(auth()->user()->can('personas.create'), 403);

        $doc = preg_replace('/\D/', '', $numeroDocumento);
        if ($doc === '') {
            return response()->json(['found' => false], 400);
        }

        $row = DB::table('gold.reniec')
            ->select(['ap_pat', 'ap_mat', 'nombres', 'fecha_nac'])
            ->where('nro_documento', $doc)
            ->first();

        if (!$row) {
            return response()->json(['found' => false], 404);
        }

        return response()->json([
            'found' => true,
            'data' => [
                'apellido_paterno' => $row->ap_pat,
                'apellido_materno' => $row->ap_mat,
                'nombres' => $row->nombres,
                'fecha_nacimiento' => $row->fecha_nac,
            ],
        ]);
    }

    public function checkDocumento(string $numeroDocumento, Request $request)
    {
        abort_unless(
            auth()->user()->can('personas.create') || auth()->user()->can('personas.edit'),
            403
        );

        $doc = preg_replace('/\D/', '', $numeroDocumento);
        if ($doc === '') {
            return response()->json(['exists' => false], 400);
        }

        $query = DB::table('bronze.dim_persona')->where('numero_documento', $doc);
        $excludeId = $request->query('exclude_id');
        if ($excludeId) {
            $query->where('id_persona', '!=', $excludeId);
        }

        return response()->json(['exists' => $query->exists()]);
    }
}
