<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index()
    {
        // Verificar permiso de ver roles
        abort_unless(auth()->user()->can('roles.view'), 403);

        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy(function($permission) {
            return explode('.', $permission->name)[0]; // Agrupar por módulo (personas, contratos, etc)
        });

        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        // Verificar permiso
        abort_unless(auth()->user()->can('roles.manage'), 403);

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        // No permitir crear un rol llamado Administrador
        if (strtolower($request->name) === 'administrador') {
            return response()->json([
                'error' => 'No se puede crear un rol con el nombre Administrador'
            ], 403);
        }

        $role = Role::create(['name' => $request->name]);

        if ($request->permissions) {
            $role->givePermissionTo($request->permissions);
        }

        activity('roles')
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->withProperties([
                'attributes' => [
                    'nombre' => $role->name,
                    'permisos' => $role->permissions->pluck('name')->toArray(),
                ],
            ])
            ->event('created')
            ->log('Rol creado');

        return response()->json([
            'success' => true,
            'message' => "Rol '{$request->name}' creado correctamente",
            'role' => $role->load('permissions')
        ]);
    }

    /**
     * Update role permissions.
     */
    public function updatePermissions(Request $request, $roleId)
    {
        // Verificar permiso
        abort_unless(auth()->user()->can('roles.manage'), 403);

        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $role = Role::findOrFail($roleId);

        // No permitir modificar rol Administrador (solo Administrador puede hacerlo)
        if ($role->name === 'Administrador' && !auth()->user()->hasRole('Administrador')) {
            return response()->json([
                'error' => 'No se puede modificar el rol Administrador'
            ], 403);
        }

        $permisosAntes = $role->permissions->pluck('name')->toArray();

        // Sincronizar permisos
        $role->syncPermissions($request->permissions ?? []);

        activity('roles')
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->withProperties([
                'old' => ['permisos' => $permisosAntes],
                'attributes' => ['permisos' => $role->fresh()->permissions->pluck('name')->toArray()],
            ])
            ->event('updated')
            ->log('Permisos actualizados');

        return response()->json([
            'success' => true,
            'message' => "Permisos actualizados para el rol '{$role->name}'",
            'permissions' => $role->permissions->pluck('name')
        ]);
    }

    /**
     * Delete a role.
     */
    public function destroy($roleId)
    {
        // Verificar permiso
        abort_unless(auth()->user()->can('roles.manage'), 403);

        $role = Role::findOrFail($roleId);

        // No permitir eliminar Administrador nunca
        if ($role->name === 'Administrador') {
            return response()->json([
                'error' => 'No se puede eliminar el rol Administrador'
            ], 403);
        }

        // Verificar que no tenga usuarios asignados
        $usersCount = $role->users()->count();
        if ($usersCount > 0) {
            return response()->json([
                'error' => "No se puede eliminar el rol porque tiene {$usersCount} usuario(s) asignado(s)"
            ], 403);
        }

        $roleName = $role->name;
        $rolePermisos = $role->permissions->pluck('name')->toArray();
        $role->delete();

        activity('roles')
            ->causedBy(auth()->user())
            ->withProperties([
                'old' => [
                    'nombre' => $roleName,
                    'permisos' => $rolePermisos,
                ],
            ])
            ->event('deleted')
            ->log('Rol eliminado');

        return response()->json([
            'success' => true,
            'message' => "Rol '{$roleName}' eliminado correctamente"
        ]);
    }

    /**
     * Get role details with users (AJAX)
     */
    public function show($roleId)
    {
        // Verificar permiso
        abort_unless(auth()->user()->can('roles.view'), 403);

        $role = Role::with(['permissions', 'users'])->findOrFail($roleId);

        return response()->json([
            'role' => $role,
            'users_count' => $role->users->count(),
            'permissions' => $role->permissions->pluck('name')
        ]);
    }
}
