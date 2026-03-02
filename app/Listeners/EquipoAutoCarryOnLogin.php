<?php

namespace App\Listeners;

use App\Models\EquipoDia;
use App\Models\UserAsignacion;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Artisan;

class EquipoAutoCarryOnLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (!UserAsignacion::where('superior_id', $user->id)->vigentes()->exists()) {
            return;
        }

        $hoy = Carbon::today()->toDateString();

        $this->carryParaSupervisor($user->id, $hoy);
    }

    private function carryParaSupervisor(int $supervisorId, string $hoy): void
    {
        $tieneEquipoHoy = EquipoDia::where('supervisor_id', $supervisorId)
            ->where('fecha', $hoy)
            ->exists();

        if (!$tieneEquipoHoy) {
            Artisan::call('equipo:auto-carry', ['--supervisor' => $supervisorId]);
        }

        $subordinados = UserAsignacion::where('superior_id', $supervisorId)
            ->vigentes()
            ->pluck('user_id')
            ->unique();

        foreach ($subordinados as $subId) {
            $tieneSubordinados = UserAsignacion::where('superior_id', $subId)
                ->vigentes()
                ->exists();

            if ($tieneSubordinados) {
                $this->carryParaSupervisor((int) $subId, $hoy);
            }
        }
    }
}
