<?php

use App\Http\Controllers\PersonaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\EmpresaController;
use App\Http\Controllers\Admin\CampanaController;
use App\Http\Controllers\Admin\AsignacionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'asignacion'])->group(function () {
    // Sin asignación activa — excluida del middleware internamente
    Route::get('/sin-asignacion', function () {
        return view('sin-asignacion', ['estado' => request('estado'), 'motivo' => request('motivo')]);
    })->name('sin-asignacion');

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
        Route::get('/personas/export', [PersonaController::class, 'exportExcel'])->name('personas.export');
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
    Route::middleware(['permission:contratos.view'])
        ->get('/contratos', [App\Http\Controllers\ContratoController::class, 'index'])
        ->name('contratos.index');

    Route::middleware(['permission:contratos.create'])
        ->post('/contratos', [App\Http\Controllers\ContratoController::class, 'store'])
        ->name('contratos.store');

    Route::middleware(['permission:contratos.edit'])->group(function () {
        Route::put('/contratos/{contrato}', [App\Http\Controllers\ContratoController::class, 'update'])->name('contratos.update');
        Route::patch('/contratos/{contrato}', [App\Http\Controllers\ContratoController::class, 'update']);
    });

    // Rutas para Dar de Baja
    Route::middleware(['permission:contratos.baja'])->group(function () {
        Route::put('/contratos/{contrato}/baja', [App\Http\Controllers\ContratoController::class, 'darDeBaja'])
            ->name('contratos.baja');
        Route::delete('/contratos/{contrato}/baja', [App\Http\Controllers\ContratoController::class, 'eliminarBaja'])
            ->name('contratos.baja.destroy');
    });

    // Rutas para Movimientos de Contratos
    Route::middleware(['permission:contratos.create'])
        ->post('/contratos/movimientos', [App\Http\Controllers\ContratoMovimientoController::class, 'store'])
        ->name('contratos.movimientos.store');

    Route::middleware(['permission:contratos.edit'])
        ->put('/contratos/movimientos/{movimiento}', [App\Http\Controllers\ContratoMovimientoController::class, 'update'])
        ->name('contratos.movimientos.update');

    // 5. Rutas de Asistencia - Protegidas por permisos
    Route::middleware(['permission:asistencia.view'])->group(function () {
        Route::get('/asistencia', [App\Http\Controllers\AsistenciaController::class, 'index'])->name('asistencia.index');
    });

    Route::middleware(['permission:contratos.delete'])
        ->delete('/contratos/movimientos/{movimiento}', [App\Http\Controllers\ContratoMovimientoController::class, 'destroy'])
        ->name('contratos.movimientos.destroy');

    Route::middleware(['permission:asistencia.edit'])
        ->post('/asistencia/guardar', [App\Http\Controllers\AsistenciaController::class, 'guardar'])
        ->name('asistencia.guardar');

    // 6. Rutas de Adicionales - Protegidas por permisos
    Route::middleware(['permission:adicionales.view'])->group(function () {
        Route::get('/adicionales', [App\Http\Controllers\AdicionalController::class, 'index'])->name('adicionales.index');
    });

    Route::middleware(['permission:adicionales.edit'])
        ->post('/adicionales/guardar', [App\Http\Controllers\AdicionalController::class, 'guardar'])
        ->name('adicionales.guardar');

    Route::middleware(['permission:adicionales.edit'])
        ->post('/adicionales/importar', [App\Http\Controllers\AdicionalController::class, 'importConsolidado'])
        ->name('adicionales.importar');

    // 7. Rutas de Cálculos - Restringidas a Admin y Jefe Operaciones
    Route::middleware(['role:Administrador|Jefe Operaciones', 'permission:calculos.view'])->group(function () {
        Route::get('/calculos', [App\Http\Controllers\CalculoController::class, 'index'])->name('calculos.index');
        Route::get('/calculos/resultados', [App\Http\Controllers\CalculoController::class, 'obtenerResultados'])->name('calculos.resultados');
        Route::get('/calculos/exportar', [App\Http\Controllers\CalculoController::class, 'exportar'])->name('calculos.exportar');
    });

    Route::middleware(['role:Administrador', 'permission:calculos.execute'])
        ->post('/calculos/ejecutar', [App\Http\Controllers\CalculoController::class, 'ejecutar'])
        ->name('calculos.ejecutar');

    // 8. Rutas de Equipos
    Route::middleware(['role:Administrador'])
        ->post('/equipos/auto-carry', [App\Http\Controllers\PrestamoController::class, 'autoCarry'])
        ->name('equipos.auto-carry');

    Route::middleware(['permission:equipos.manage'])->group(function () {
        // Préstamos multi-día
        Route::get('/equipos', [App\Http\Controllers\PrestamoController::class, 'index'])->name('equipos.index');
        Route::get('/equipos/buscar-solicitar', [App\Http\Controllers\PrestamoController::class, 'buscarParaSolicitar'])->name('equipos.buscar-solicitar');
        Route::post('/equipos/prestamos', [App\Http\Controllers\PrestamoController::class, 'crearPrestamo'])->name('equipos.prestamos.crear');
        Route::delete('/equipos/prestamos/{prestamo}', [App\Http\Controllers\PrestamoController::class, 'cancelarPrestamo'])->name('equipos.prestamos.cancelar');
        Route::post('/equipos/prestamos/{prestamo}/aprobar', [App\Http\Controllers\PrestamoController::class, 'aprobarPrestamo'])->name('equipos.prestamos.aprobar');
        Route::post('/equipos/prestamos/{prestamo}/rechazar', [App\Http\Controllers\PrestamoController::class, 'rechazarPrestamo'])->name('equipos.prestamos.rechazar');
        Route::post('/equipos/prestamos/{prestamo}/anular', [App\Http\Controllers\PrestamoController::class, 'anularPrestamo'])->name('equipos.prestamos.anular');
    });

    // 6. Rutas de Perfil (sin restricciones)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('password.update');

    // 6. Rutas de Administración (permisos granulares)
    Route::prefix('admin')->name('admin.')->group(function () {
        // Gestión de Usuarios
        Route::middleware(['permission:users.view'])->group(function () {
            Route::get('/users', [App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('users.index');
            Route::get('/users/{user}/permissions', [App\Http\Controllers\Admin\UserManagementController::class, 'permissions'])->name('users.permissions');
        });
        Route::middleware(['permission:users.manage'])->group(function () {
            Route::post('/users/{user}/assign-role', [App\Http\Controllers\Admin\UserManagementController::class, 'assignRole'])->name('users.assign-role');
            Route::post('/users/{user}/remove-role', [App\Http\Controllers\Admin\UserManagementController::class, 'removeRole'])->name('users.remove-role');
            Route::post('/users/{user}/sync-roles', [App\Http\Controllers\Admin\UserManagementController::class, 'syncRoles'])->name('users.sync-roles');
        });
        Route::middleware(['role:Administrador'])
            ->post('/users/{user}/reset-password', [App\Http\Controllers\Admin\UserManagementController::class, 'resetPassword'])
            ->name('users.reset-password');

        // Auditoría
        Route::middleware(['permission:audit.view'])
            ->get('/audit', [App\Http\Controllers\Admin\AuditController::class, 'index'])->name('audit.index');

        // Empresas — solo Administrador
        Route::middleware(['role:Administrador'])->group(function () {
            Route::get('/empresas', [EmpresaController::class, 'index'])->name('empresas.index');
            Route::post('/empresas', [EmpresaController::class, 'store'])->name('empresas.store');
            Route::put('/empresas/{id}', [EmpresaController::class, 'update'])->name('empresas.update');
            Route::patch('/empresas/{id}/toggle', [EmpresaController::class, 'toggle'])->name('empresas.toggle');
            Route::patch('/empresas/{id}/cerrar', [EmpresaController::class, 'cerrar'])->name('empresas.cerrar');
        });

        // Asignaciones
        Route::middleware(['permission:asignaciones.view'])->group(function () {
            Route::get('/asignaciones/usuarios-disponibles', [AsignacionController::class, 'usuariosDisponibles'])->name('asignaciones.usuarios-disponibles');
            Route::get('/asignaciones/superiores-disponibles', [AsignacionController::class, 'superioresDisponibles'])->name('asignaciones.superiores-disponibles');
            Route::get('/asignaciones', [AsignacionController::class, 'index'])->name('asignaciones.index');
        });
        Route::middleware(['permission:asignaciones.manage'])->group(function () {
            Route::post('/asignaciones', [AsignacionController::class, 'store'])->name('asignaciones.store');
            Route::patch('/asignaciones/{id}/aprobar', [AsignacionController::class, 'aprobar'])->name('asignaciones.aprobar');
            Route::patch('/asignaciones/{id}/rechazar', [AsignacionController::class, 'rechazar'])->name('asignaciones.rechazar');
            Route::patch('/asignaciones/{id}/pausar', [AsignacionController::class, 'pausar'])->name('asignaciones.pausar');
            Route::patch('/asignaciones/{id}/cerrar', [AsignacionController::class, 'cerrar'])->name('asignaciones.cerrar');
            Route::patch('/asignaciones/{id}/transferir', [AsignacionController::class, 'transferir'])->name('asignaciones.transferir');
            Route::patch('/asignaciones/{id}/editar', [AsignacionController::class, 'editar'])->name('asignaciones.editar');
        });

        // Campañas
        Route::middleware(['permission:campanas.view'])->group(function () {
            Route::get('/campanas', [CampanaController::class, 'index'])->name('campanas.index');
        });
        Route::middleware(['permission:campanas.manage'])->group(function () {
            Route::post('/campanas', [CampanaController::class, 'store'])->name('campanas.store');
            Route::put('/campanas/{id}', [CampanaController::class, 'update'])->name('campanas.update');
            Route::patch('/campanas/{id}/toggle', [CampanaController::class, 'toggle'])->name('campanas.toggle');
            Route::patch('/campanas/{id}/cerrar', [CampanaController::class, 'cerrar'])->name('campanas.cerrar');
        });

        // Gestión de Roles
        Route::middleware(['permission:roles.view'])->group(function () {
            Route::get('/roles', [App\Http\Controllers\Admin\RoleController::class, 'index'])->name('roles.index');
            Route::get('/roles/{role}', [App\Http\Controllers\Admin\RoleController::class, 'show'])->name('roles.show');
        });
        Route::middleware(['permission:roles.manage'])->group(function () {
            Route::post('/roles', [App\Http\Controllers\Admin\RoleController::class, 'store'])->name('roles.store');
            Route::put('/roles/{role}/permissions', [App\Http\Controllers\Admin\RoleController::class, 'updatePermissions'])->name('roles.update-permissions');
            Route::delete('/roles/{role}', [App\Http\Controllers\Admin\RoleController::class, 'destroy'])->name('roles.destroy');
        });
    });

    // API Routes para cargar datos de tablas dimension
    Route::prefix('api')->group(function () {
        Route::get('/cargos', function () {
            return response()->json(\App\Models\Cargo::select('id as id_cargo', 'nombre_cargo')->get());
        });
        Route::get('/planillas', function () {
            return response()->json(\App\Models\Planilla::select('id as id_planilla', 'nombre_planilla')->get());
        });
        Route::get('/fondos-pensiones', function () {
            return response()->json(\App\Models\FondoPension::select('id as id_fondo', 'fondo_pension')->get());
        });
        Route::get('/condiciones', function () {
            return response()->json(\App\Models\Condicion::select('id as id_condicion', 'nombre_condicion')->get());
        });
        Route::get('/bancos', function () {
            return response()->json(\App\Models\Banco::select('id as id_banco', 'nombre_banco')->get());
        });
        Route::get('/centros-costo', function () {
            return response()->json(\App\Models\CentroCosto::select('id as id_centro_costo', 'nombre_centro_costo as nombre')->get());
        });
        Route::get('/familias', function () {
            return response()->json(\App\Models\Familia::select('id as id_familia', 'nombre_familia as nombre')->get());
        });
        Route::get('/monedas', function () {
            return response()->json(\App\Models\Moneda::select('id as id_moneda', 'nombre_moneda')->get());
        });

        Route::get('/distritos', function () {
            $provinciaId = request('provincia_id');
            if (!$provinciaId) return response()->json([]);
            return response()->json(
                \Illuminate\Support\Facades\DB::table('nomina.dim_distritos')
                    ->select('id', 'nombre')
                    ->where('provincia_id', $provinciaId)
                    ->orderBy('nombre')
                    ->get()
            );
        });

        Route::get('/personas/reniec/{numero_documento}', [PersonaController::class, 'lookupReniec'])
            ->name('personas.reniec');
        Route::get('/personas/check-document/{numero_documento}', [PersonaController::class, 'checkDocumento'])
            ->name('personas.check-document');

        // API para flujo de creacion de contratos
        Route::post('/contratos/evaluar', [App\Http\Controllers\ContratoController::class, 'evaluarContrato']);
        Route::post('/contratos/historial', [App\Http\Controllers\ContratoController::class, 'obtenerHistorial']);
        Route::get('/personas/{numero_documento}/ultimo-inicio', [App\Http\Controllers\ContratoController::class, 'obtenerUltimoInicio']);
    });
});

require __DIR__.'/auth.php';
