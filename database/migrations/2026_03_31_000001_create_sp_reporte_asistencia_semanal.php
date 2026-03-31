<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE OR ALTER PROCEDURE dbo.sp_reporte_asistencia_semanal
                @fecha_inicio DATE,
                @fecha_fin    DATE = NULL   -- opcional: límite de días a evaluar; si NULL usa la fecha actual
            AS
            BEGIN
                SET NOCOUNT ON;
                SET DATEFIRST 1; -- Lunes = 1

                DECLARE @lunes_inicio DATE = DATEADD(DAY, 1 - DATEPART(WEEKDAY, @fecha_inicio), @fecha_inicio);
                DECLARE @limite       DATE = ISNULL(@fecha_fin, CAST(GETDATE() AS DATE));

                ;WITH semanas AS (
                    SELECT
                        @lunes_inicio                    AS semana_ini,
                        DATEADD(DAY, 6, @lunes_inicio)   AS semana_fin
                    UNION ALL
                    SELECT
                        DATEADD(WEEK, 1, semana_ini),
                        DATEADD(WEEK, 1, semana_fin)
                    FROM semanas
                    WHERE DATEADD(WEEK, 1, semana_ini) <= @limite
                ),

                -- Días individuales (solo hasta @limite, semana final puede ser parcial)
                dias AS (
                    SELECT
                        s.semana_ini,
                        s.semana_fin,
                        DATEADD(DAY, n.n, s.semana_ini) AS fecha
                    FROM semanas s
                    CROSS JOIN (VALUES(0),(1),(2),(3),(4),(5),(6)) AS n(n)
                    WHERE DATEADD(DAY, n.n, s.semana_ini) <= @limite
                ),

                -- Personas con al menos un contrato que intersecta la semana
                personas_semana AS (
                    SELECT DISTINCT
                        d.semana_ini,
                        d.semana_fin,
                        fc.persona_id
                    FROM dias d
                    JOIN nomina.fact_contratos fc
                        ON  fc.inicio_contrato <= d.semana_fin
                        AND (
                                (fc.fecha_renuncia IS NULL AND fc.fin_contrato IS NULL)
                            OR  COALESCE(fc.fecha_renuncia, fc.fin_contrato) >= d.semana_ini
                        )
                ),

                -- Código por día por persona
                detalle_dia AS (
                    SELECT
                        ps.semana_ini,
                        ps.persona_id,
                        d.fecha,
                        CASE
                            WHEN fc.id IS NULL           THEN 'x'
                            WHEN fa.id IS NULL           THEN '-'
                            ELSE ia.codigo_asistencia
                        END AS codigo
                    FROM personas_semana ps
                    JOIN  dias d
                        ON  d.semana_ini = ps.semana_ini
                    LEFT JOIN nomina.fact_contratos fc
                        ON  fc.persona_id      = ps.persona_id
                        AND fc.inicio_contrato <= d.fecha
                        AND (
                                (fc.fecha_renuncia IS NULL AND fc.fin_contrato IS NULL)
                            OR  COALESCE(fc.fecha_renuncia, fc.fin_contrato) >= d.fecha
                        )
                    LEFT JOIN nomina.fact_asistencias fa
                        ON  fa.contrato_id = fc.id
                        AND fa.fecha       = d.fecha
                    LEFT JOIN nomina.dim_items_asistencias ia
                        ON  ia.id = fa.item_asistencia_id
                ),

                -- Asistencias agregadas por persona/semana
                asistencias_agg AS (
                    SELECT
                        semana_ini,
                        persona_id,
                        STRING_AGG(codigo, ',') WITHIN GROUP (ORDER BY fecha) AS asistencias
                    FROM detalle_dia
                    GROUP BY semana_ini, persona_id
                ),

                -- MIN/MAX de contratos que intersectan la semana
                contratos_semana AS (
                    SELECT
                        ps.semana_ini,
                        ps.persona_id,
                        MIN(fc.inicio_contrato)                         AS inicio_contrato,
                        MAX(COALESCE(fc.fecha_renuncia, fc.fin_contrato)) AS fin_contrato
                    FROM personas_semana ps
                    JOIN nomina.fact_contratos fc
                        ON  fc.persona_id      = ps.persona_id
                        AND fc.inicio_contrato <= ps.semana_fin
                        AND (
                                (fc.fecha_renuncia IS NULL AND fc.fin_contrato IS NULL)
                            OR  COALESCE(fc.fecha_renuncia, fc.fin_contrato) >= ps.semana_ini
                        )
                    GROUP BY ps.semana_ini, ps.persona_id
                ),

                -- Centros de costo únicos por persona/semana
                centros_semana AS (
                    SELECT
                        sub.semana_ini,
                        sub.persona_id,
                        STRING_AGG(cc.nombre_centro_costo, ', ')
                            WITHIN GROUP (ORDER BY cc.nombre_centro_costo) AS nombre_centro_costos
                    FROM (
                        SELECT DISTINCT ps.semana_ini, ps.persona_id, fc.centro_costo_id
                        FROM personas_semana ps
                        JOIN nomina.fact_contratos fc
                            ON  fc.persona_id      = ps.persona_id
                            AND fc.inicio_contrato <= ps.semana_fin
                            AND (
                                    (fc.fecha_renuncia IS NULL AND fc.fin_contrato IS NULL)
                                OR  COALESCE(fc.fecha_renuncia, fc.fin_contrato) >= ps.semana_ini
                            )
                        WHERE fc.centro_costo_id IS NOT NULL
                    ) sub
                    JOIN nomina.dim_centro_costos cc ON cc.id = sub.centro_costo_id
                    GROUP BY sub.semana_ini, sub.persona_id
                ),

                -- Familias únicas por persona/semana
                familias_semana AS (
                    SELECT
                        sub.semana_ini,
                        sub.persona_id,
                        STRING_AGG(f.nombre_familia, ', ')
                            WITHIN GROUP (ORDER BY f.nombre_familia) AS nombre_familia
                    FROM (
                        SELECT DISTINCT ps.semana_ini, ps.persona_id, fc.familia_id
                        FROM personas_semana ps
                        JOIN nomina.fact_contratos fc
                            ON  fc.persona_id      = ps.persona_id
                            AND fc.inicio_contrato <= ps.semana_fin
                            AND (
                                    (fc.fecha_renuncia IS NULL AND fc.fin_contrato IS NULL)
                                OR  COALESCE(fc.fecha_renuncia, fc.fin_contrato) >= ps.semana_ini
                            )
                        WHERE fc.familia_id IS NOT NULL
                    ) sub
                    JOIN nomina.dim_familias f ON f.id = sub.familia_id
                    GROUP BY sub.semana_ini, sub.persona_id
                )

                SELECT
                    p.id                                                          AS persona_id,
                    p.numero_documento,
                    CONCAT(p.apellido_paterno, ' ', p.apellido_materno, ' ', p.nombres) AS colaborador,
                    cs.inicio_contrato,
                    cs.fin_contrato,
                    ps.semana_ini                                                 AS fecha_inicio,
                    ps.semana_fin                                                 AS fecha_fin,
                    CASE WHEN CHARINDEX('-', aa.asistencias) > 0 THEN 0 ELSE 1 END AS status,
                    aa.asistencias,
                    CASE WHEN CHARINDEX(',D,', ',' + aa.asistencias + ',') > 0
                         THEN 1 ELSE 0 END                                        AS obsv_desc,
                    ISNULL(ccs.nombre_centro_costos, '-')                         AS nombre_centro_costos,
                    ISNULL(fs.nombre_familia,        '-')                         AS nombre_familia
                FROM (
                    SELECT DISTINCT semana_ini, semana_fin, persona_id
                    FROM personas_semana
                ) ps
                JOIN  nomina.dim_personas p   ON  p.id  = ps.persona_id
                JOIN  contratos_semana    cs  ON  cs.semana_ini  = ps.semana_ini  AND cs.persona_id  = ps.persona_id
                JOIN  asistencias_agg     aa  ON  aa.semana_ini  = ps.semana_ini  AND aa.persona_id  = ps.persona_id
                LEFT JOIN centros_semana  ccs ON ccs.semana_ini  = ps.semana_ini  AND ccs.persona_id = ps.persona_id
                LEFT JOIN familias_semana fs  ON  fs.semana_ini  = ps.semana_ini  AND fs.persona_id  = ps.persona_id
                ORDER BY
                    p.apellido_paterno,
                    p.apellido_materno,
                    p.nombres,
                    ps.semana_ini
                OPTION (MAXRECURSION 500);
            END
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS dbo.sp_reporte_asistencia_semanal');
    }
};
