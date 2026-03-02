<?php

namespace App\Console\Commands;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Console\Command;

class CrearUsuariosDesdeContratos extends Command
{
    protected $signature   = 'usuarios:crear-desde-contratos
                                {--dry-run    : Muestra qué se crearía sin ejecutar nada}
                                {--fix-nombres : Corrige el name de usuarios ya creados a apellidos + primer nombre}';

    protected $description = 'Crea cuentas de usuario para todas las personas que hayan tenido algún contrato';

    public function handle(): int
    {
        if ($this->option('fix-nombres')) {
            return $this->fixNombres();
        }

        $personas = Persona::whereHas('contratos')
            ->orderBy('apellido_paterno')
            ->orderBy('nombres')
            ->get();

        $total = $personas->count();

        if ($total === 0) {
            $this->warn('No se encontraron personas con contratos.');
            return 0;
        }

        $this->info("Personas con contrato encontradas: {$total}");

        if ($this->option('dry-run')) {
            $this->warn('-- MODO DRY RUN: no se creará nada --');
        }

        $creados    = 0;
        $existentes = 0;
        $errores    = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($personas as $persona) {
            $bar->advance();

            if (!$persona->numero_documento) {
                $errores++;
                continue;
            }

            if (User::where('numero_documento', $persona->numero_documento)->exists()) {
                $existentes++;
                continue;
            }

            if ($this->option('dry-run')) {
                $creados++;
                continue;
            }

            try {
                $primerNombre = explode(' ', trim($persona->nombres))[0] ?? '';
                $nombre = trim(implode(' ', array_filter([
                    $persona->apellido_paterno,
                    $persona->apellido_materno,
                    $primerNombre,
                ])));

                User::create([
                    'name'             => $nombre,
                    'numero_documento' => $persona->numero_documento,
                    'email'            => "{$persona->numero_documento}@amg.local",
                    'password'         => 'password123',
                    'activo'           => true,
                ]);

                $creados++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("  Error con DNI {$persona->numero_documento}: {$e->getMessage()}");
                $errores++;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Resultado', 'Cantidad'],
            [
                ['Cuentas creadas',    $creados],
                ['Ya tenían cuenta',   $existentes],
                ['Sin documento / err', $errores],
                ['Total procesados',   $total],
            ]
        );

        if ($this->option('dry-run')) {
            $this->warn("Ejecuta sin --dry-run para crear las {$creados} cuentas.");
        } else {
            $this->info("Listo. Contraseña por defecto: password123");
        }

        return 0;
    }

    private function fixNombres(): int
    {
        $personas = Persona::whereHas('contratos')->get();

        $this->info("Corrigiendo nombres de {$personas->count()} personas...");

        $actualizados = 0;
        $sinUsuario   = 0;

        $bar = $this->output->createProgressBar($personas->count());
        $bar->start();

        foreach ($personas as $persona) {
            $bar->advance();

            if (!$persona->numero_documento) continue;

            $user = User::where('numero_documento', $persona->numero_documento)->first();

            if (!$user) {
                $sinUsuario++;
                continue;
            }

            $primerNombre = explode(' ', trim($persona->nombres))[0] ?? '';
            $nombreCorrecto = trim(implode(' ', array_filter([
                $persona->apellido_paterno,
                $persona->apellido_materno,
                $primerNombre,
            ])));

            if ($user->name !== $nombreCorrecto) {
                $user->update(['name' => $nombreCorrecto]);
                $actualizados++;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Resultado', 'Cantidad'],
            [
                ['Nombres actualizados', $actualizados],
                ['Sin usuario en sistema', $sinUsuario],
            ]
        );

        return 0;
    }
}
