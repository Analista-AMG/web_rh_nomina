<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasRole('Administrador'), 403);

        $empresas = Empresa::withCount('campanas')->orderBy('nombre')->get();

        return view('admin.empresas.index', compact('empresas'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole('Administrador'), 403);

        $request->validate([
            'nombre'       => 'required|string|max:200',
            'fecha_inicio' => 'nullable|date',
        ]);

        Empresa::create([
            'nombre'       => $request->nombre,
            'fecha_inicio' => $request->fecha_inicio ?: null,
            'activo'       => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Empresa creada correctamente']);
    }

    public function update(Request $request, $id)
    {
        abort_unless(auth()->user()->hasRole('Administrador'), 403);

        $empresa = Empresa::findOrFail($id);

        $request->validate([
            'nombre'       => 'required|string|max:200',
            'fecha_inicio' => 'nullable|date',
        ]);

        $empresa->update([
            'nombre'       => $request->nombre,
            'fecha_inicio' => $request->fecha_inicio ?: null,
        ]);

        return response()->json(['success' => true, 'message' => 'Empresa actualizada correctamente']);
    }

    public function toggle($id)
    {
        abort_unless(auth()->user()->hasRole('Administrador'), 403);

        $empresa = Empresa::findOrFail($id);
        abort_if($empresa->fecha_fin !== null, 422, 'No se puede modificar una empresa cerrada.');

        $empresa->update(['activo' => !$empresa->activo]);

        $estado = $empresa->activo ? 'activada' : 'desactivada';

        return response()->json([
            'success' => true,
            'message' => "Empresa {$estado}",
            'activo'  => $empresa->activo,
        ]);
    }

    public function cerrar($id)
    {
        abort_unless(auth()->user()->hasRole('Administrador'), 403);

        $empresa = Empresa::findOrFail($id);
        abort_if($empresa->fecha_fin !== null, 422, 'La empresa ya está cerrada.');

        $empresa->update([
            'fecha_fin' => now()->toDateString(),
            'activo'    => false,
        ]);

        return response()->json(['success' => true, 'message' => 'Empresa cerrada definitivamente.']);
    }
}
