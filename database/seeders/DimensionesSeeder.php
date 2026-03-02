<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DimensionesSeeder extends Seeder
{
    protected string $filePath = 'database/seeders/data/dimensiones.xlsx';

    // Columnas que son fechas
    protected array $columnasFecha = ['fecha_inicio', 'fecha_fin', 'fundacion', 'fecha_creacion'];

    // Columnas que son decimales
    protected array $columnasDecimal = ['aporte', 'prima', 'comision', 'horas_regulares', 'horas_nocturnas', 'factor_regular', 'factor_nocturno'];

    public function run(): void
    {
        $spreadsheet = IOFactory::load(base_path($this->filePath));

        foreach ($spreadsheet->getSheetNames() as $tabla) {
            $this->seedHoja($spreadsheet, $tabla);
        }
    }

    protected function seedHoja($spreadsheet, string $tabla): void
    {
        $worksheet = $spreadsheet->getSheetByName($tabla);

        // Leer encabezados de la fila 1
        $encabezados = [];
        foreach ($worksheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $valor = $cell->getValue();
                if (empty($valor)) break;
                $encabezados[] = trim($valor);
            }
        }

        // Leer datos desde fila 2
        $datos = [];
        foreach ($worksheet->getRowIterator(2) as $row) {
            $fila = [];
            $colIndex = 0;
            $filaVacia = true;

            foreach ($row->getCellIterator('A', chr(64 + count($encabezados))) as $cell) {
                if ($colIndex >= count($encabezados)) break;

                $columna = $encabezados[$colIndex];
                $valor = $cell->getValue();

                // Parsear fechas
                if (in_array($columna, $this->columnasFecha) && !empty($valor)) {
                    $valor = is_numeric($valor)
                        ? Date::excelToDateTimeObject($valor)->format('Y-m-d')
                        : date('Y-m-d', strtotime($valor));
                }

                // Parsear decimales
                if (in_array($columna, $this->columnasDecimal)) {
                    $valor = $valor !== null && $valor !== '' ? (float) $valor : null;
                }

                // Limpiar strings
                if (is_string($valor)) { $valor = trim($valor); }
                if (!empty($valor) || $valor === 0 || $valor === '0' || $valor === 0.0) { $filaVacia = false; }

                $fila[$columna] = $valor;
                $colIndex++;
            }

            if ($filaVacia) continue;

            $datos[] = $fila;
        }

        if (!empty($datos)) {
            $nombreCompletoTabla = "nomina.dim_{$tabla}";
            DB::table($nombreCompletoTabla)->insert($datos);
            $this->command->info("{$nombreCompletoTabla}: " . count($datos) . " registros.");
        }
    }
}