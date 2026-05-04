<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonaRequest;
use App\Http\Requests\UpdatePersonaRequest;
use App\Models\Persona;
use App\Models\Reniec;
use App\Services\JerarquiaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PersonaController extends Controller
{
    public function __construct(protected JerarquiaService $jerarquia) {}

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('personas.view'), 403);

        $personaIds = $this->jerarquia->personaIdsVisibles(auth()->user());

        $personas = $this->applyFilters(
            $this->jerarquia->aplicarFiltroPersonas(
                Persona::with('contratos')->orderByDesc('created_at'),
                $personaIds,
                'id'
            ),
            $request
        )->paginate(7)->appends($request->all());

        $hoy = now();
        $kpis = [
            'total'   => $this->jerarquia->aplicarFiltroPersonas(Persona::query(), $personaIds, 'id')->count(),
            'nuevas'  => $this->jerarquia->aplicarFiltroPersonas(Persona::query(), $personaIds, 'id')
                             ->whereMonth('created_at', $hoy->month)->whereYear('created_at', $hoy->year)->count(),
            'activas' => $this->jerarquia->aplicarFiltroPersonas(Persona::query(), $personaIds, 'id')
                             ->whereHas('contratos', fn($q) => $q->activos())->count(),
        ];

        $paises        = DB::table('nomina.dim_paises')->orderBy('nombre')->get();
        $departamentos = DB::table('nomina.dim_departamentos')->orderBy('nombre')->get();
        $provincias    = DB::table('nomina.dim_provincias')->orderBy('nombre')->get();

        // Solo los distritos de las personas paginadas (máx 7), no los ~1,700 del catálogo completo
        $distritosIds = $personas->pluck('distrito')->filter()->unique()->toArray();
        $distritos = $distritosIds
            ? DB::table('nomina.dim_distritos')->whereIn('id', $distritosIds)->pluck('nombre', 'id')
            : collect();

        return view('personas.index', compact('personas', 'kpis', 'paises', 'departamentos', 'provincias', 'distritos'));
    }

    public function exportExcel(Request $request)
    {
        abort_unless(auth()->user()->can('personas.view'), 403);

        $personaIds = $this->jerarquia->personaIdsVisibles(auth()->user());

        $personas = $this->applyFilters(
            $this->jerarquia->aplicarFiltroPersonas(
                Persona::orderByDesc('created_at'),
                $personaIds,
                'id'
            ),
            $request
        )->get();

        $paises        = DB::table('nomina.dim_paises')->pluck('nombre', 'id')->all();
        $departamentos = DB::table('nomina.dim_departamentos')->pluck('nombre', 'id')->all();
        $provincias    = DB::table('nomina.dim_provincias')->pluck('nombre', 'id')->all();
        $distritos     = DB::table('nomina.dim_distritos')->pluck('nombre', 'id')->all();

        return response()->streamDownload(
            fn() => $this->generateExcel($personas, $paises, $departamentos, $provincias, $distritos),
            'personas.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function store(StorePersonaRequest $request)
    {
        Persona::create($request->validated());

        return response()->json(['success' => true, 'message' => 'Persona registrada correctamente']);
    }

    public function update(UpdatePersonaRequest $request, Persona $persona)
    {
        $persona->update($request->validated());

        return response()->json(['success' => true, 'message' => 'Persona actualizada correctamente']);
    }

    public function destroy(Persona $persona)
    {
        abort_unless(auth()->user()->can('personas.delete'), 403);

        if ($persona->contratos()->exists()) {
            return response()->json([
                'success' => false,
                'error'   => 'No se puede eliminar la persona porque tiene contratos asociados. Primero elimine sus contratos.',
            ], 422);
        }

        $persona->delete();

        return response()->json(['success' => true, 'message' => 'Persona eliminada correctamente']);
    }

    public function lookupReniec(string $numeroDocumento)
    {
        abort_unless(auth()->user()->can('personas.create'), 403);

        $doc = $this->sanitizeDocumento($numeroDocumento);
        if ($doc === '') {
            return response()->json(['found' => false], 400);
        }

        $row = Reniec::where('nro_documento', $doc)
            ->select('ap_pat', 'ap_mat', 'nombres', 'fecha_nac')
            ->first();

        if (!$row) {
            return response()->json(['found' => false], 404);
        }

        return response()->json([
            'found' => true,
            'data'  => [
                'apellido_paterno' => $row->ap_pat,
                'apellido_materno' => $row->ap_mat,
                'nombres'          => $row->nombres,
                'fecha_nacimiento' => $row->fecha_nac->format('Y-m-d'),
            ],
        ]);
    }

    public function checkDocumento(string $numeroDocumento, Request $request)
    {
        abort_unless(
            auth()->user()->can('personas.create') || auth()->user()->can('personas.edit'),
            403
        );

        $doc = $this->sanitizeDocumento($numeroDocumento);
        if ($doc === '') {
            return response()->json(['exists' => false], 400);
        }

        $query = Persona::where('numero_documento', $doc);

        if ($excludeId = $request->query('exclude_id')) {
            $query->where('id', '!=', $excludeId);
        }

        return response()->json(['exists' => $query->exists()]);
    }

    // -------------------------------------------------------------------------
    // Métodos privados
    // -------------------------------------------------------------------------

    private function applyFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('search_name')) {
            $term = $request->search_name;
            $query->where(fn($q) => $q
                ->where('nombres', 'like', "%{$term}%")
                ->orWhere('apellido_paterno', 'like', "%{$term}%")
                ->orWhere('apellido_materno', 'like', "%{$term}%")
            );
        }

        if ($request->filled('search_doc')) {
            $query->where('numero_documento', 'like', "%{$request->search_doc}%");
        }

        return $query;
    }

    private function sanitizeDocumento(string $value): string
    {
        return preg_replace('/\D/', '', $value);
    }

    private function generateExcel($personas, array $paises, array $departamentos, array $provincias, array $distritos): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setTitle('Personas');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Personas');

        $rows = [[
            'ID Persona', 'Numero Documento', 'Apellido Paterno', 'Apellido Materno',
            'Nombres', 'Tipo Documento', 'Fecha Nacimiento', 'Genero',
            'Pais', 'Departamento', 'Provincia', 'Distrito',
            'Numero Telefonico', 'Correo Personal', 'Correo Corporativo', 'Direccion', 'Fecha Registro',
        ]];

        foreach ($personas as $persona) {
            $rows[] = [
                $persona->id,
                $persona->numero_documento,
                $persona->apellido_paterno,
                $persona->apellido_materno,
                $persona->nombres,
                $persona->tipo_documento,
                $persona->fecha_nacimiento?->format('d/m/Y') ?? '',
                $this->mapGenero($persona->genero),
                $this->mapLookup($persona->nacionalidad, $paises),
                $this->mapLookup($persona->departamento, $departamentos),
                $this->mapLookup($persona->provincia, $provincias),
                $this->mapLookup($persona->distrito, $distritos),
                $persona->numero_telefonico,
                $persona->correo_electronico_personal,
                $persona->correo_electronico_corporativo,
                $persona->direccion,
                $persona->created_at?->format('d/m/Y H:i:s') ?? '',
            ];
        }

        $sheet->fromArray($rows, null, 'A1', true);

        (new Xlsx($spreadsheet))->save('php://output');
    }

    private function mapGenero(mixed $value): string
    {
        return match((int) $value) {
            1 => 'Masculino',
            2 => 'Femenino',
            default => '',
        };
    }

    private function mapLookup(mixed $value, array $map): string
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        return $map[(int) $value] ?? (string) $value;
    }
}