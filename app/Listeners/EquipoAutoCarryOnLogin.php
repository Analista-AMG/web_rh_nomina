<?php

namespace App\Listeners;

use App\Models\EquipoDia;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;

class EquipoAutoCarryOnLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        // Solo aplica a supervisores con permiso de gestionar equipos
        if (!$user->can('equipos.manage')) {
            return;
        }

        $hoy = Carbon::today()->toDateString();
        $ayer = Carbon::yesterday()->toDateString();

        // Si ya tiene equipo hoy, no hace nada
        $tieneEquipoHoy = EquipoDia::where('user_id', $user->id)
            ->where('fecha', $hoy)
            ->exists();

        if ($tieneEquipoHoy) {
            return;
        }

        // Copiar asignaciones de ayer a hoy
        $equipoAyer = EquipoDia::where('user_id', $user->id)
            ->where('fecha', $ayer)
            ->get();

        foreach ($equipoAyer as $asignacion) {
            EquipoDia::firstOrCreate(
                ['fecha' => $hoy, 'id_contrato' => (int)$asignacion->id_contrato],
                ['user_id' => $user->id]
            );
        }
    }
}
