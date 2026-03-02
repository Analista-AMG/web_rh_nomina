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

        $asignacion = UserAsignacion::where('user_id', $user->id)
            ->whereNull('fecha_fin')
            ->orderByRaw("CASE estado
                WHEN 'aprobado'   THEN 1
                WHEN 'pendiente'  THEN 2
                WHEN 'rechazado'  THEN 3
                ELSE 4 END")
            ->first();

        if (!$asignacion) {
            return redirect()->route('sin-asignacion', ['estado' => 'sin_asignacion']);
        }

        if ($asignacion->estado === UserAsignacion::ESTADO_RECHAZADO) {
            return redirect()->route('sin-asignacion', [
                'estado' => 'rechazado',
                'motivo' => $asignacion->motivo_rechazo,
            ]);
        }

        if ($asignacion->estado === UserAsignacion::ESTADO_PENDIENTE) {
            return redirect()->route('sin-asignacion', ['estado' => 'pendiente']);
        }

        if ($asignacion->estado === UserAsignacion::ESTADO_APROBADO && !$asignacion->activo) {
            return redirect()->route('sin-asignacion', ['estado' => 'suspendido']);
        }

        return $next($request);
    }
}
