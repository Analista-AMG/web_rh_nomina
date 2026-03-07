<?php

namespace App\Http\Middleware;

use App\Models\UserAsignacion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarAsignacion
{
    private const EXCLUIDAS = [
        'sin-asignacion',
        'logout',
        'profile.*',
        'password.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs(...self::EXCLUIDAS)) {
            return $next($request);
        }

        $user = $request->user();

        if ($user->hasRole('Administrador')) {
            return $next($request);
        }

        // Colaboradores: cachear en sesión para evitar query por request (se invalida al logout)
        // Supervisores hacia arriba: query siempre (son pocos y sus cambios deben ser inmediatos)
        $esColaborador = $user->hasRole('Colaborador');
        $cacheKey      = 'asignaciones_vigentes_' . $user->id;

        if ($esColaborador && $request->session()->has($cacheKey)) {
            $asignaciones = $request->session()->get($cacheKey);
        } else {
            $asignaciones = UserAsignacion::where('user_id', $user->id)
                ->whereNull('fecha_fin')
                ->get();

            if ($esColaborador) {
                $request->session()->put($cacheKey, $asignaciones);
            }
        }

        if ($asignaciones->isEmpty()) {
            return redirect()->route('sin-asignacion', ['estado' => 'sin_asignacion']);
        }

        // Si tiene al menos una asignación vigente (aprobada + activa), puede pasar
        if ($asignaciones->contains(fn ($a) =>
            $a->estado === UserAsignacion::ESTADO_APROBADO && $a->activo
        )) {
            return $next($request);
        }

        // Sin ninguna vigente: determinar el mejor estado para mostrar
        if ($asignaciones->contains('estado', UserAsignacion::ESTADO_PENDIENTE)) {
            return redirect()->route('sin-asignacion', ['estado' => 'pendiente']);
        }

        if ($asignaciones->every(fn ($a) =>
            $a->estado === UserAsignacion::ESTADO_APROBADO && !$a->activo
        )) {
            return redirect()->route('sin-asignacion', ['estado' => 'suspendido']);
        }

        $rechazada = $asignaciones->firstWhere('estado', UserAsignacion::ESTADO_RECHAZADO);
        if ($rechazada) {
            return redirect()->route('sin-asignacion', [
                'estado' => 'rechazado',
                'motivo' => $rechazada->motivo_rechazo,
            ]);
        }

        return redirect()->route('sin-asignacion', ['estado' => 'sin_asignacion']);
    }
}
