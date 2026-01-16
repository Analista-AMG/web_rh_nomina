<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $paises = [
            ['nombre' => 'Argentina', 'codigo_pais' => '+54'],
            ['nombre' => 'Bolivia', 'codigo_pais' => '+591'],
            ['nombre' => 'Brasil', 'codigo_pais' => '+55'],
            ['nombre' => 'Chile', 'codigo_pais' => '+56'],
            ['nombre' => 'Colombia', 'codigo_pais' => '+57'],
            ['nombre' => 'Costa Rica', 'codigo_pais' => '+506'],
            ['nombre' => 'Cuba', 'codigo_pais' => '+53'],
            ['nombre' => 'Ecuador', 'codigo_pais' => '+593'],
            ['nombre' => 'El Salvador', 'codigo_pais' => '+503'],
            ['nombre' => 'Guatemala', 'codigo_pais' => '+502'],
            ['nombre' => 'Honduras', 'codigo_pais' => '+504'],
            ['nombre' => 'Mexico', 'codigo_pais' => '+52'],
            ['nombre' => 'Nicaragua', 'codigo_pais' => '+505'],
            ['nombre' => 'Panama', 'codigo_pais' => '+507'],
            ['nombre' => 'Paraguay', 'codigo_pais' => '+595'],
            ['nombre' => 'Peru', 'codigo_pais' => '+51'],
            ['nombre' => 'Puerto Rico', 'codigo_pais' => '+1'],
            ['nombre' => 'Republica Dominicana', 'codigo_pais' => '+1'],
            ['nombre' => 'Uruguay', 'codigo_pais' => '+598'],
            ['nombre' => 'Venezuela', 'codigo_pais' => '+58'],
        ];

        foreach ($paises as $pais) {
            $row = DB::table('bronze.dim_paises')
                ->where('nombre', $pais['nombre'])
                ->first();

            if ($row) {
                DB::table('bronze.dim_paises')
                    ->where('id', $row->id)
                    ->update([
                        'codigo_pais' => $pais['codigo_pais'],
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('bronze.dim_paises')->insert([
                    'nombre' => $pais['nombre'],
                    'codigo_pais' => $pais['codigo_pais'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

    }
}
