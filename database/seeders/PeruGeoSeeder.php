<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeruGeoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $peruId = DB::table('bronze.dim_paises')
            ->where('nombre', 'Peru')
            ->value('id');

        if (!$peruId) {
            $peruId = DB::table('bronze.dim_paises')->insertGetId([
                'nombre' => 'Peru',
                'codigo_pais' => '+51',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $departamentosPath = base_path('database/seeders/data/departamentos_peru.csv');
        $distritosPath = base_path('database/seeders/data/distritos_peru.csv');

        // Clear existing Peru geo data
        DB::table('bronze.dim_distritos')->delete();
        DB::table('bronze.dim_departamentos')->where('pais_id', $peruId)->delete();

        // Load departamentos with fixed IDs
        $this->setIdentityInsert('bronze.dim_departamentos', true);
        $this->seedDepartamentos($departamentosPath, $peruId, $now);
        $this->setIdentityInsert('bronze.dim_departamentos', false);

        // Load distritos (ID autogenerado)
        $this->seedDistritos($distritosPath, $now);
    }

    private function seedDepartamentos(string $path, int $peruId, $now): void
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("No se pudo abrir el archivo: {$path}");
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return;
        }

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (!$data) {
                continue;
            }

            $batch[] = [
                'id' => (int) $data['department_id'],
                'pais_id' => $peruId,
                'nombre' => $data['name'],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 200) {
                DB::table('bronze.dim_departamentos')->insert($batch);
                $batch = [];
            }
        }

        if ($batch) {
            DB::table('bronze.dim_departamentos')->insert($batch);
        }

        fclose($handle);
    }

    private function seedDistritos(string $path, $now): void
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("No se pudo abrir el archivo: {$path}");
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return;
        }

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (!$data) {
                continue;
            }

            $batch[] = [
                'departamento_id' => (int) $data['department_id'],
                'nombre' => $data['name'],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 300) {
                DB::table('bronze.dim_distritos')->insert($batch);
                $batch = [];
            }
        }

        if ($batch) {
            DB::table('bronze.dim_distritos')->insert($batch);
        }

        fclose($handle);
    }

    private function setIdentityInsert(string $table, bool $enabled): void
    {
        $state = $enabled ? 'ON' : 'OFF';
        $wrapped = $this->wrapTable($table);

        try {
            DB::statement("SET IDENTITY_INSERT {$wrapped} {$state}");
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            if (stripos($message, 'does not have the identity property') !== false) {
                return;
            }

            throw $e;
        }
    }

    private function wrapTable(string $table): string
    {
        [$schema, $name] = explode('.', $table, 2);
        return "[{$schema}].[{$name}]";
    }
}
