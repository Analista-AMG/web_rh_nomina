<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campana;
use App\Models\Empresa;
use App\Models\UserAsignacion;
use Illuminate\Http\Request;

class CampanaController extends Controller
{
    // ── Helpers de scope ──────────────────────────────────────────────────────

    private function esAdmin(): bool
    {
        return auth()->user()->hasRole('Administrador');
    }

    private function esJO(): bool
    {
        return UserAsignacion::where('user_id', auth()->id())
            ->where('rol', UserAsignacion::ROL_JEFE_OPERACIONES)
            ->vigentes()
            ->exists();
    }

    /** Admin o JO pueden crear campañas raíz. */
    private function puedeCrearRaiz(): bool
    {
        return $this->esAdmin() || $this->esJO();
    }

    /**
     * IDs de campañas que el usuario puede gestionar.
     * Admin → null (sin filtro). Coordinador/JO → sus campañas + subs.
     */
    private function campanaIdsPermitidos(): ?array
    {
        if ($this->esAdmin()) {
            return null;
        }

        $asignaciones = UserAsignacion::where('user_id', auth()->id())
            ->whereIn('rol', [UserAsignacion::ROL_COORDINADOR, UserAsignacion::ROL_JEFE_OPERACIONES])
            ->vigentes()
            ->get();

        if ($asignaciones->isEmpty()) {
            return [];
        }

        $ids = [];
        foreach ($asignaciones as $a) {
            $campana = Campana::with('subcampanas')->find($a->campana_id);
            if ($campana) {
                $ids = array_merge($ids, $campana->todosLosIds());
            }
        }

        return array_unique($ids);
    }

    /**
     * IDs de empresas bajo las cuales el JO puede crear campañas raíz.
     * Admin → null (todas). JO → empresas de sus campañas asignadas.
     */
    private function empresaIdsPermitidos(): ?array
    {
        if ($this->esAdmin()) {
            return null;
        }

        $ids = UserAsignacion::where('user_id', auth()->id())
            ->where('rol', UserAsignacion::ROL_JEFE_OPERACIONES)
            ->vigentes()
            ->with('campana')
            ->get()
            ->pluck('campana.empresa_id')
            ->filter()
            ->unique()
            ->map(fn($id) => (int) $id)
            ->values()
            ->toArray();

        return $ids;
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function index()
    {
        $permitidos = $this->campanaIdsPermitidos();

        if ($permitidos !== null && empty($permitidos)) {
            abort(403, 'No tienes campañas asignadas para gestionar.');
        }

        $puedeCrearRaiz  = $this->puedeCrearRaiz();
        $empresasIds     = $this->empresaIdsPermitidos();

        // Empresas disponibles para crear raíz
        $empresasQuery = Empresa::orderBy('nombre');
        if ($empresasIds !== null) {
            $empresasQuery->whereIn('id', $empresasIds);
        }
        $empresas = $puedeCrearRaiz ? $empresasQuery->get(['id', 'nombre']) : collect();

        // Campañas raíz visibles
        $query = Campana::with(['empresa', 'subcampanas'])
            ->whereNull('parent_id')
            ->orderBy('empresa_id')
            ->orderBy('nombre');

        if ($permitidos !== null) {
            $query->whereIn('id', $permitidos);
        }

        $campanas = $query->get();

        // Raíces disponibles como parent para sub-campañas
        $raicesQuery = Campana::whereNull('parent_id')->orderBy('nombre');
        if ($permitidos !== null) {
            $raicesQuery->whereIn('id', $permitidos);
        }
        $raices = $raicesQuery->get(['id', 'nombre', 'empresa_id']);

        return view('admin.campanas.index', [
            'campanas'       => $campanas,
            'empresas'       => $empresas,
            'raices'         => $raices,
            'esAdmin'        => $this->esAdmin(),
            'puedeCrearRaiz' => $puedeCrearRaiz,
        ]);
    }

    public function store(Request $request)
    {
        $permitidos  = $this->campanaIdsPermitidos();
        $empresasIds = $this->empresaIdsPermitidos();

        if ($permitidos !== null && empty($permitidos) && !$this->puedeCrearRaiz()) {
            abort(403);
        }

        $request->validate([
            'empresa_id'   => $this->puedeCrearRaiz() ? 'nullable|integer' : 'nullable',
            'nombre'       => 'required|string|max:200',
            'parent_id'    => 'nullable|integer',
            'fecha_inicio' => 'nullable|date',
        ]);

        $esSubcampana = !empty($request->parent_id);

        if ($esSubcampana) {
            // Validar que el parent esté en el scope del usuario
            $padre = Campana::find($request->parent_id);
            abort_unless($padre, 422);
            if ($permitidos !== null) {
                abort_unless(in_array((int)$padre->id, $permitidos), 403);
            }
            $empresaId = $padre->empresa_id;
        } else {
            // Crear raíz — solo Admin y JO
            abort_unless($this->puedeCrearRaiz(), 403);
            abort_unless(!empty($request->empresa_id), 422);

            $empresa = Empresa::find($request->empresa_id);
            abort_unless($empresa, 422);

            // JO solo puede crear bajo sus empresas
            if ($empresasIds !== null) {
                abort_unless(in_array((int)$empresa->id, $empresasIds), 403);
            }

            $empresaId = $empresa->id;
        }

        Campana::create([
            'empresa_id'   => $empresaId,
            'parent_id'    => $request->parent_id ?: null,
            'nombre'       => $request->nombre,
            'fecha_inicio' => $request->fecha_inicio ?: null,
            'activo'       => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Campaña creada correctamente']);
    }

    public function update(Request $request, $id)
    {
        $campana    = Campana::findOrFail($id);
        $permitidos = $this->campanaIdsPermitidos();

        if ($permitidos !== null) {
            abort_unless(in_array((int)$campana->id, $permitidos), 403);
        }

        $request->validate([
            'nombre'       => 'required|string|max:200',
            'fecha_inicio' => 'nullable|date',
        ]);

        $campana->update([
            'nombre'       => $request->nombre,
            'fecha_inicio' => $request->fecha_inicio ?: null,
        ]);

        return response()->json(['success' => true, 'message' => 'Campaña actualizada correctamente']);
    }

    public function toggle($id)
    {
        $campana    = Campana::findOrFail($id);
        $permitidos = $this->campanaIdsPermitidos();

        if ($permitidos !== null) {
            abort_unless(in_array((int)$campana->id, $permitidos), 403);
        }

        abort_if($campana->fecha_fin !== null, 422, 'No se puede modificar una campaña cerrada.');

        $campana->update(['activo' => !$campana->activo]);

        $estado = $campana->activo ? 'activada' : 'desactivada';

        return response()->json([
            'success' => true,
            'message' => "Campaña {$estado}",
            'activo'  => $campana->activo,
        ]);
    }

    public function cerrar($id)
    {
        // Solo JO y Admin pueden cerrar definitivamente
        abort_unless($this->puedeCrearRaiz(), 403);

        $campana    = Campana::findOrFail($id);
        $permitidos = $this->campanaIdsPermitidos();

        if ($permitidos !== null) {
            abort_unless(in_array((int)$campana->id, $permitidos), 403);
        }

        abort_if($campana->fecha_fin !== null, 422, 'La campaña ya está cerrada.');

        $campana->update([
            'fecha_fin' => now()->toDateString(),
            'activo'    => false,
        ]);

        return response()->json(['success' => true, 'message' => 'Campaña cerrada definitivamente.']);
    }
}
