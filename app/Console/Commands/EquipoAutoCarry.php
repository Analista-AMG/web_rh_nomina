<?php

namespace App\Console\Commands;

use App\Models\EquipoDia;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EquipoAutoCarry extends Command
{
    protected $signature = 'equipo:auto-carry
                            {--fecha= : Fecha origen Y-m-d (default: hoy)}';

    protected $description = 'Copia las asignaciones de equipo de un día al día siguiente';

    public function handle(): int
    {
        $desde = $this->option('fecha')
            ? Carbon::parse($this->option('fecha'))->toDateString()
            : Carbon::today()->toDateString();

        $hasta = Carbon::parse($desde)->addDay()->toDateString();

        $this->info("Auto-carry: {$desde} → {$hasta}");

        $yaAsignados = EquipoDia::where('fecha', $hasta)
            ->pluck('id_contrato')
            ->toArray();

        $asignacionesOrigen = EquipoDia::where('fecha', $desde)
            ->whereNotIn('id_contrato', $yaAsignados)
            ->get();

        if ($asignacionesOrigen->isEmpty()) {
            $this->info('No hay asignaciones nuevas que copiar.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($asignacionesOrigen as $asignacion) {
            EquipoDia::create([
                'fecha'       => $hasta,
                'user_id'     => $asignacion->user_id,
                'id_contrato' => $asignacion->id_contrato,
            ]);
            $count++;
        }

        $this->info("✓ {$count} asignaciones copiadas a {$hasta}.");
        return self::SUCCESS;
    }
}
