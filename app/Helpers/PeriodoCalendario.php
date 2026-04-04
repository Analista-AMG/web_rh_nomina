<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Lógica pura de períodos calendario — sin DB.
 *
 * Reglas:
 *   Q1 = día 01 al 12 del mes
 *   Q2 = día 13 al último día del mes
 */
class PeriodoCalendario
{
    // ── Período actual ────────────────────────────────────────────────────────

    /** Retorna el período del mes de hoy en formato 'YYYY-MM'. */
    public static function periodoActual(): string
    {
        return Carbon::today()->format('Y-m');
    }

    /** Retorna la quincena actual (1 o 2) basada en el día de hoy. */
    public static function quincenaActual(): int
    {
        return Carbon::today()->day <= 12 ? 1 : 2;
    }

    // ── Fechas de quincena ────────────────────────────────────────────────────

    /** Fecha de inicio de una quincena (Carbon). */
    public static function inicio(string $periodo, int $quincena): Carbon
    {
        $base = Carbon::parse($periodo . '-01');
        return $quincena === 1 ? $base : $base->copy()->setDay(13);
    }

    /** Fecha de fin de una quincena (Carbon). */
    public static function fin(string $periodo, int $quincena): Carbon
    {
        $base = Carbon::parse($periodo . '-01');
        return $quincena === 1 ? $base->copy()->setDay(12) : $base->copy()->endOfMonth()->startOfDay();
    }

    // ── Navegación de períodos ────────────────────────────────────────────────

    /** Período anterior en formato 'YYYY-MM'. */
    public static function anterior(string $periodo): string
    {
        return Carbon::parse($periodo . '-01')->subMonth()->format('Y-m');
    }

    /** Período siguiente en formato 'YYYY-MM'. */
    public static function siguiente(string $periodo): string
    {
        return Carbon::parse($periodo . '-01')->addMonth()->format('Y-m');
    }

    // ── Búsqueda por fecha ────────────────────────────────────────────────────

    /** Período 'YYYY-MM' al que pertenece una fecha. */
    public static function periodoDeF(string $fecha): string
    {
        return Carbon::parse($fecha)->format('Y-m');
    }

    /** Quincena (1 o 2) a la que pertenece una fecha. */
    public static function quincenaDeF(string $fecha): int
    {
        return Carbon::parse($fecha)->day <= 12 ? 1 : 2;
    }

    // ── Objeto período ────────────────────────────────────────────────────────

    /**
     * Retorna un objeto con la misma interfaz que el modelo Pago:
     *   ->id, ->periodo, ->quincena, ->inicio (Carbon), ->fin (Carbon)
     *
     * El id es sintético: YYYYMMQ (ej. 20260401 para ABR-2026 Q1).
     */
    public static function objeto(string $periodo, int $quincena): object
    {
        return (object) [
            'id'       => (int) (str_replace('-', '', $periodo) . $quincena),
            'periodo'  => $periodo,
            'quincena' => $quincena,
            'inicio'   => static::inicio($periodo, $quincena),
            'fin'      => static::fin($periodo, $quincena),
        ];
    }

    /** Objeto del período/quincena activos hoy. */
    public static function hoy(): object
    {
        return static::objeto(static::periodoActual(), static::quincenaActual());
    }

    /**
     * Encuentra el objeto período que contiene una fecha dada.
     * Si la fecha no pertenece a ningún período generado, retorna null.
     */
    public static function encontrarPorFecha(string $fecha, Collection $periodos): ?object
    {
        $d = Carbon::parse($fecha)->startOfDay();
        return $periodos->first(
            fn($p) => Carbon::parse($p->inicio)->startOfDay()->lte($d)
                   && Carbon::parse($p->fin)->startOfDay()->gte($d)
        );
    }

    // ── Listado de períodos ───────────────────────────────────────────────────

    /**
     * Genera una colección de objetos período para los últimos $meses meses
     * (ambas quincenas), ordenada desc por periodo+quincena.
     */
    public static function listar(int $meses = 24): Collection
    {
        $periodos = collect();
        $base     = Carbon::today()->startOfMonth();

        for ($i = 0; $i < $meses; $i++) {
            $p = $base->copy()->subMonths($i)->format('Y-m');
            $periodos->push(static::objeto($p, 1));
            $periodos->push(static::objeto($p, 2));
        }

        return $periodos; // ya viene desc (Q1 primero dentro del mes → invertir si se desea)
    }

    /**
     * Igual que listar() pero ordenado: Q2 antes que Q1 dentro del mismo mes
     * (espejo del ORDER BY periodo DESC, quincena DESC que se usaba antes).
     */
    public static function listarDesc(int $meses = 24): Collection
    {
        $periodos = collect();
        $base     = Carbon::today()->startOfMonth();

        for ($i = 0; $i < $meses; $i++) {
            $p = $base->copy()->subMonths($i)->format('Y-m');
            $periodos->push(static::objeto($p, 2));
            $periodos->push(static::objeto($p, 1));
        }

        return $periodos;
    }

    // ── Formato ───────────────────────────────────────────────────────────────

    /**
     * Etiqueta de mes: "ABR-2026".
     * Usa Carbon con locale 'es' para el nombre del mes.
     */
    public static function labelMes(string $periodo): string
    {
        $dt  = Carbon::parse($periodo . '-01')->locale('es');
        $mes = rtrim(mb_strtoupper($dt->isoFormat('MMM')), '.');
        return $mes . '-' . $dt->format('Y');
    }
}
