<?php

use App\Http\Controllers\PersonaController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // 1. Página de Inicio (Menú Principal)
    Route::get('/', function () {
        return view('home');
    })->name('home');

    // 2. Dashboard (Métricas)
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // 3. Rutas de Personas
    Route::resource('personas', PersonaController::class);

    // 4. Rutas de Contratos
    Route::resource('contratos', App\Http\Controllers\ContratoController::class);

    // 5. Rutas de Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';