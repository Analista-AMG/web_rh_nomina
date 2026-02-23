<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Auto-carry: 3 intentos en el día D+1, copiando D → D+1 (idempotente)
        $schedule->command('equipo:auto-carry', ['--fecha' => \Carbon\Carbon::yesterday()->toDateString()])->dailyAt('00:30');
        $schedule->command('equipo:auto-carry', ['--fecha' => \Carbon\Carbon::yesterday()->toDateString()])->dailyAt('03:00');
        $schedule->command('equipo:auto-carry', ['--fecha' => \Carbon\Carbon::yesterday()->toDateString()])->dailyAt('06:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // Registrar middleware de Spatie Permission
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
