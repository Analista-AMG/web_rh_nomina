<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users with their roles.
     */
    public function index()
    {
        // Verificar que solo super_admin puede acceder
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        $users = User::with('roles')->paginate(15);
        $roles = Role::all();

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Assign role to user (AJAX)
     */
    public function assignRole(Request $request, $userId)
    {
        // Verificar permiso
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        $request->validate([
            'role' => 'required|exists:roles,name'
        ]);

        $user = User::findOrFail($userId);

        // No permitir modificar al propio usuario
        if ($user->id === auth()->id()) {
            return response()->json([
                'error' => 'No puedes modificar tus propios roles'
            ], 403);
        }

        $rolesAntes = $user->getRoleNames()->toArray();
        $user->assignRole($request->role);

        activity('usuarios')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'old' => ['roles' => $rolesAntes],
                'attributes' => ['roles' => $user->getRoleNames()->toArray()],
            ])
            ->event('updated')
            ->log('Rol asignado');

        return response()->json([
            'success' => true,
            'message' => "Rol '{$request->role}' asignado correctamente",
            'roles' => $user->getRoleNames()
        ]);
    }

    /**
     * Remove role from user (AJAX)
     */
    public function removeRole(Request $request, $userId)
    {
        // Verificar permiso
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        $request->validate([
            'role' => 'required|exists:roles,name'
        ]);

        $user = User::findOrFail($userId);

        // No permitir modificar al propio usuario
        if ($user->id === auth()->id()) {
            return response()->json([
                'error' => 'No puedes modificar tus propios roles'
            ], 403);
        }

        $rolesAntes = $user->getRoleNames()->toArray();
        $user->removeRole($request->role);

        activity('usuarios')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'old' => ['roles' => $rolesAntes],
                'attributes' => ['roles' => $user->getRoleNames()->toArray()],
            ])
            ->event('updated')
            ->log('Rol removido');

        return response()->json([
            'success' => true,
            'message' => "Rol '{$request->role}' removido correctamente",
            'roles' => $user->getRoleNames()
        ]);
    }

    /**
     * Sync all roles for a user (AJAX)
     */
    public function syncRoles(Request $request, $userId)
    {
        // Verificar permiso
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        $request->validate([
            'roles' => 'array',
            'roles.*' => 'exists:roles,name'
        ]);

        $user = User::findOrFail($userId);

        // No permitir modificar al propio usuario
        if ($user->id === auth()->id()) {
            return response()->json([
                'error' => 'No puedes modificar tus propios roles'
            ], 403);
        }

        $rolesAntes = $user->getRoleNames()->toArray();

        // Sincronizar roles (reemplaza todos)
        $user->syncRoles($request->roles ?? []);

        activity('usuarios')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'old' => ['roles' => $rolesAntes],
                'attributes' => ['roles' => $user->getRoleNames()->toArray()],
            ])
            ->event('updated')
            ->log('Roles sincronizados');

        return response()->json([
            'success' => true,
            'message' => 'Roles actualizados correctamente',
            'roles' => $user->getRoleNames()
        ]);
    }

    /**
     * Get user permissions (AJAX)
     */
    public function permissions($userId)
    {
        // Verificar permiso
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        $user = User::findOrFail($userId);
        $permissions = $user->getAllPermissions()->pluck('name');

        return response()->json([
            'permissions' => $permissions
        ]);
    }
}
