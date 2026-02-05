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
        // Verificar permiso de ver usuarios
        abort_unless(auth()->user()->can('users.view'), 403);

        $users = User::with('roles')->paginate(15);

        // Si no es Administrador, ocultar el rol Administrador de la lista
        $roles = Role::when(!auth()->user()->hasRole('Administrador'), function ($query) {
            return $query->where('name', '!=', 'Administrador');
        })->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Assign role to user (AJAX)
     */
    public function assignRole(Request $request, $userId)
    {
        // Verificar permiso
        abort_unless(auth()->user()->can('users.manage'), 403);

        $request->validate([
            'role' => 'required|exists:roles,name'
        ]);

        // Solo Administrador puede asignar el rol Administrador
        if ($request->role === 'Administrador' && !auth()->user()->hasRole('Administrador')) {
            return response()->json([
                'error' => 'No tienes permiso para asignar el rol Administrador'
            ], 403);
        }

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
        abort_unless(auth()->user()->can('users.manage'), 403);

        $request->validate([
            'role' => 'required|exists:roles,name'
        ]);

        // Solo Administrador puede remover el rol Administrador
        if ($request->role === 'Administrador' && !auth()->user()->hasRole('Administrador')) {
            return response()->json([
                'error' => 'No tienes permiso para remover el rol Administrador'
            ], 403);
        }

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
        abort_unless(auth()->user()->can('users.manage'), 403);

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

        // Solo Administrador puede incluir/excluir el rol Administrador
        $roles = $request->roles ?? [];
        $userHadAdmin = $user->hasRole('Administrador');
        $rolesIncludesAdmin = in_array('Administrador', $roles);

        if (!auth()->user()->hasRole('Administrador')) {
            // Si intenta agregar o quitar Administrador, denegar
            if ($rolesIncludesAdmin || $userHadAdmin) {
                return response()->json([
                    'error' => 'No tienes permiso para modificar el rol Administrador'
                ], 403);
            }
        }

        $rolesAntes = $user->getRoleNames()->toArray();

        // Sincronizar roles (reemplaza todos)
        $user->syncRoles($roles);

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
        abort_unless(auth()->user()->can('users.view'), 403);

        $user = User::findOrFail($userId);
        $permissions = $user->getAllPermissions()->pluck('name');

        return response()->json([
            'permissions' => $permissions
        ]);
    }
}
