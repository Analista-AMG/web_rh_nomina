<?php

use App\Http\Controllers\PersonaController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // 1. Página de Inicio (Menú Principal)
    Route::get('/', function () {
        return view('home');
    })->name('home');

    // 2. Dashboard (Métricas) - Requiere permiso
    Route::middleware(['permission:dashboard.view'])
        ->get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
        ->name('dashboard');

    // 3. Rutas de Personas - Protegidas por permisos
    Route::middleware(['permission:personas.view'])->group(function () {
        Route::get('/personas', [PersonaController::class, 'index'])->name('personas.index');
        Route::get('/personas/{persona}', [PersonaController::class, 'show'])->name('personas.show');
    });

    Route::middleware(['permission:personas.create'])
        ->post('/personas', [PersonaController::class, 'store'])
        ->name('personas.store');

    Route::middleware(['permission:personas.edit'])->group(function () {
        Route::get('/personas/{persona}/edit', [PersonaController::class, 'edit'])->name('personas.edit');
        Route::put('/personas/{persona}', [PersonaController::class, 'update'])->name('personas.update');
        Route::patch('/personas/{persona}', [PersonaController::class, 'update']);
    });

    Route::middleware(['permission:personas.delete'])
        ->delete('/personas/{persona}', [PersonaController::class, 'destroy'])
        ->name('personas.destroy');

    // 4. Rutas de Contratos - Protegidas por permisos
    Route::middleware(['permission:contratos.view'])->group(function () {
        Route::get('/contratos', [App\Http\Controllers\ContratoController::class, 'index'])->name('contratos.index');
        Route::get('/contratos/{contrato}', [App\Http\Controllers\ContratoController::class, 'show'])->name('contratos.show');
    });

    Route::middleware(['permission:contratos.create'])
        ->post('/contratos', [App\Http\Controllers\ContratoController::class, 'store'])
        ->name('contratos.store');

    Route::middleware(['permission:contratos.edit'])->group(function () {
        Route::get('/contratos/{contrato}/edit', [App\Http\Controllers\ContratoController::class, 'edit'])->name('contratos.edit');
        Route::put('/contratos/{contrato}', [App\Http\Controllers\ContratoController::class, 'update'])->name('contratos.update');
        Route::patch('/contratos/{contrato}', [App\Http\Controllers\ContratoController::class, 'update']);
    });

    Route::middleware(['permission:contratos.delete'])
        ->delete('/contratos/{contrato}', [App\Http\Controllers\ContratoController::class, 'destroy'])
        ->name('contratos.destroy');

    // 5. Rutas de Asistencia - Protegidas por permisos
    Route::middleware(['permission:asistencia.view'])->group(function () {
        Route::get('/asistencia', [App\Http\Controllers\AsistenciaController::class, 'index'])->name('asistencia.index');
    });

    Route::middleware(['permission:asistencia.edit'])
        ->post('/asistencia/guardar', [App\Http\Controllers\AsistenciaController::class, 'guardar'])
        ->name('asistencia.guardar');

    // 6. Rutas de Perfil (sin restricciones)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 6. Rutas de Administración (solo super_admin)
    Route::middleware(['role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
        // Gestión de Usuarios
        Route::get('/users', [App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/assign-role', [App\Http\Controllers\Admin\UserManagementController::class, 'assignRole'])->name('users.assign-role');
        Route::post('/users/{user}/remove-role', [App\Http\Controllers\Admin\UserManagementController::class, 'removeRole'])->name('users.remove-role');
        Route::post('/users/{user}/sync-roles', [App\Http\Controllers\Admin\UserManagementController::class, 'syncRoles'])->name('users.sync-roles');
        Route::get('/users/{user}/permissions', [App\Http\Controllers\Admin\UserManagementController::class, 'permissions'])->name('users.permissions');

        // Gestión de Roles
        Route::get('/roles', [App\Http\Controllers\Admin\RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [App\Http\Controllers\Admin\RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{role}', [App\Http\Controllers\Admin\RoleController::class, 'show'])->name('roles.show');
        Route::put('/roles/{role}/permissions', [App\Http\Controllers\Admin\RoleController::class, 'updatePermissions'])->name('roles.update-permissions');
        Route::delete('/roles/{role}', [App\Http\Controllers\Admin\RoleController::class, 'destroy'])->name('roles.destroy');
    });
});

require __DIR__.'/auth.php';