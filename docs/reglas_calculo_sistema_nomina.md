# Reglas de Cálculo — Sistema de Nómina
**Versión:** 1.0 — Mayo 2026
**Estado:** En validación con RRHH

---

## 1. Alcance

El sistema automatiza el cálculo mensual de nómina para las tres empresas del grupo:

| Empresa | Regímenes |
|---|---|
| AMG International | General, MYPE |
| Nexo BPO | MYPE |
| STH | General (NEUSOFT), MYPE (NEUSOFT, SERVICIOS JP, TVVENTAS), RHE |

**Regímenes:**
- **FT / Planilla Fija (General y MYPE):** Trabajadores en planilla con AFP u ONP, ESSALUD a cargo del empleador y todas las provisiones de ley.
- **RHE (Recibo por Honorarios):** Sin AFP/ONP, sin ESSALUD, sin provisiones. Aplica IR 4ta categoría si corresponde.

---

## 2. Períodos y parámetros legales vigentes (desde enero 2026)

| Parámetro | Valor |
|---|---|
| Remuneración Mínima Vital (RMV) | S/ 1,130.00 |
| Tasa ESSALUD | 9% |
| Tasa IR 4ta categoría (RHE) | 8% |
| Umbral IR 4ta | Total Haberes ≥ S/ 1,501 |

---

## 3. Reglas de cálculo por concepto

### 3.1 Días trabajados

La base es los **días del mes calendario** (28 a 31 según el mes). Se cuentan los días con los siguientes códigos de asistencia:

| Grupo | Códigos | Efecto |
|---|---|---|
| Asistencia normal | A, A1, A2, A3, A4, D, LC, TR, C | Cuentan y pagan haber básico |
| Medio día | MD | Cuenta como día completo; se aplica descuento de medio día aparte |
| Licencias con goce | — | Ver nota |
| No cuentan | FI, FJ, LS, DM, V | No generan haber básico |

> **DM (Descanso Médico)** y **V (Vacaciones):** no generan haber básico proporcional, pero sí generan los conceptos específicos de Descanso Médico y Remuneración Vacacional respectivamente.

### 3.2 Haber Básico Proporcional

```
Haber Básico = (Remuneración acordada / días_mes) × días trabajados
```

Aplica a todos los regímenes.

### 3.3 Asignación Familiar

Solo FT (General y MYPE). Aplica si el trabajador tiene hijos en el registro.

```
Asignación Familiar = RMV × 10% = S/ 113.00 (proporcional a días trabajados)
```

Afecto AFP/ONP y ESSALUD.

### 3.4 Remuneración Vacacional

Solo FT. Se paga por cada día marcado como **V** en asistencia.

```
Rem. Vacacional = (Remuneración acordada / días_mes) × días V
```

Afecto AFP/ONP y ESSALUD.

### 3.5 Descanso Médico

Solo FT. Se paga por cada día marcado como **DM** en asistencia.

```
Descanso Médico = (Remuneración acordada / días_mes) × días DM
```

**Inafecto** AFP/ONP y ESSALUD.

### 3.6 Feriado

Se paga **el doble** del valor diario por cada día feriado trabajado (código **TF**).

```
Pago Feriado = (Haber Básico / días del mes) × días TF × 2
```

Afecto AFP/ONP y ESSALUD.

> **Pendiente de confirmación RRHH (ítem P-1):** Para los cargos SISTEMAS y ASISTENTE DE TI en NEUSOFT, el sistema actualmente calcula feriado en S/ 0. Necesitamos confirmar si esta excepción es correcta y el motivo.

### 3.7 Descuento por Medio Día (MD)

Cuando un trabajador registra un día como **MD** (trabajo medio día), cobra el día completo en haber básico proporcional, pero se aplica el siguiente descuento:

```
Descuento MD = (Haber Básico / días_mes / 2) × cantidad de MD
```

El descuento va en Total Descuentos.

### 3.8 Comisiones y Bonificaciones (AMG y NEXO)

Ingresadas manualmente por RRHH en el sistema como adicionales.

**AMG (General y MYPE):** `COMISIÓN` y `BONIFICACIÓN` son columnas separadas. Ambas son afectas a AFP/ONP y ESSALUD.

**NEXO MYPE:** La planilla tiene una sola columna `COMISIÓN / CAPACITACIÓN` que fusiona ambos conceptos. Actualmente el sistema recibe ese valor como un único adicional tipo `COMISION` y lo trata como **afecto a AFP/ONP y ESSALUD en su totalidad**.

> **Pendiente de confirmación RRHH (ítem P-2):** La CAPACITACIÓN debería ser inafecta, pero al estar fusionada con la COMISIÓN en un solo monto no es posible separar la afectación. Evaluar si RRHH ingresará ambos conceptos por separado en la BD.

---

## 4. Bonos especiales — NEUSOFT

### 4.1 Bono de Nocturnidad

Para trabajadores con turnos nocturnos. El sistema calcula tres componentes que se suman:

| Componente | Descripción | Fuente |
|---|---|---|
| Horas nocturnas | Monto por hora × total de horas nocturnas en el período | Calculado automáticamente (fn_calc_horas_nocturnas) |
| Proporcional noches | `monto_acuerdo / días_mes × días con turno nocturno` | Calculado automáticamente (fn_calc_proporcional) |
| Manual | Ingresado como adicional BONIFICACION_NOCTURNA | Ingreso RRHH |

> **Regla para días de descanso (D):** Si el descanso cae después de una secuencia predominantemente nocturna, hereda la clasificación nocturna. Si la secuencia previa era mayoritariamente diurna, el descanso es diurno. Esto evita que un trabajador que cambió de turno antes del descanso reciba bono nocturno incorrecto.

Afecto AFP/ONP y ESSALUD.

### 4.2 Bono de Rendimiento

| Fuente | Campaña | Descripción |
|---|---|---|
| Proporcional a días (PROPORCIONAL_DIAS) | CAINIAO | `monto_acuerdo / días_mes × días laborales efectivos` |
| Bono externo Excel | ALIEXPRESS | Monto ingresado desde Excel resumen de pagos (BONO_ALIEXPRESS) |
| Fijo mensual (FIJO) | SISTEMAS / ASISTENTE DE TI | S/ 360 fijo — **no se prorratea** aunque el trabajador no haya trabajado el mes completo |
| Manual | Todos | Ingresado como adicional BONO_REGULAR_MAQUETA o BONIFICACION |

> **Decisión de negocio documentada:** Los conceptos FIJO (Bono Rendimiento S/360 y Movilidad Fija S/500 para SISTEMAS/ASISTENTE DE TI) **no se prorratean** por días trabajados. Se pagan siempre completos.

Afecto AFP/ONP y ESSALUD.

### 4.3 Maqueta Inafecto

Concepto inafecto a AFP/ONP y ESSALUD. Puede provenir de:
- Bono externo Excel (MAQUETA_INAFECTO en fn_calc_externo)
- Adicional manual (MAQUETA_INAFECTO)

---

## 5. Movilidad — reglas por campaña

La movilidad es el concepto más variado entre planillas. **En todos los casos es inafecto a AFP/ONP y ESSALUD.**

| Planilla / Campaña | Régimen | Cómo se calcula | Nombre en planilla |
|---|---|---|---|
| AMG, NEXO, SERVICIOS JP MYPE, TVVENTAS MYPE | FT | Manual por RRHH | MOVILIDAD SUPEDITADA A LA ASISTENCIA |
| NEUSOFT — ALIEXPRESS 1ST y 2ND | FT General/MYPE | Pagada semana a semana; se registra en haberes Y se descuenta como ADELANTO (efecto neto S/ 0) | MOVILIDAD |
| NEUSOFT — ALIEXPRESS 3RD | FT MYPE | `S/ 250 / días_mes × días con turno nocturno` | MOVILIDAD |
| NEUSOFT — SISTEMAS / ASISTENTE DE TI | FT MYPE | S/ 500 fijo mensual completo (no se prorratea) | MOVILIDAD |
| NEUSOFT — CAINIAO | RHE | `S/ 250 / días_mes × días con turno nocturno` | MOVILIDADES |
| TVVENTAS | RHE | `S/ 150 / días_mes × días trabajados` | MOVILIDAD SUPEDITADA A LA ASISTENCIA |
| ADMINISTRATIVO | RHE | `S/ 150 / días_mes × días trabajados` | MOVILIDAD SUPEDITADA A LA ASISTENCIA |
| CLARO RHE | RHE | Manual por RRHH | MOVILIDADES |
| SERVICIOS JP RHE | RHE | Manual por RRHH | MOVILIDAD SUPEDITADA A LA ASISTENCIA |

> **¿Por qué existe el ADELANTO DE MOVILIDAD en NEUSOFT ALIEXPRESS 1ST/2ND?** La movilidad de estos grupos se entrega al trabajador semana a semana durante el mes (mediante el Excel Resumen de Pagos Semanales). Al cerrar la nómina mensual, ese dinero ya fue cobrado. Por eso se registra en Total Haberes (para que conste el concepto) y se descuenta en Total Descuentos como ADELANTO DE MOVILIDAD por el mismo valor. El efecto en el neto es S/ 0.

> **Pendiente RRHH (ítem P-3):** Las movilidades de ALIEXPRESS 1ST y 2ND deben cargarse manualmente en el sistema (`fact_adicionales`) cada mes por parte de RRHH. Confirmar el flujo de carga.

---

## 6. Conceptos y afectación AFP/ESSALUD

### 6.1 Planilla General y MYPE

| Empresa | Régimen | Concepto | Total Haberes | Total Descuentos | Afecto AFP/ONP | Afecto ESSALUD | Notas |
|---|---|---|:---:|:---:|:---:|:---:|---|
| AMG - NEXO - STH | GENERAL - MYPE | Haber Básico proporcional | ✅ | ❌ | ✅ | ✅ | |
| AMG - NEXO - STH | GENERAL - MYPE | Remuneración Vacacional | ✅ | ❌ | ✅ | ✅ | |
| AMG - NEXO - STH | GENERAL - MYPE | Descanso Médico | ✅ | ❌ | ❌ | ❌ | |
| AMG - NEXO - STH | GENERAL - MYPE | Feriado | ✅ | ❌ | ✅ | ✅ | |
| AMG - NEXO - STH | GENERAL - MYPE | Asignación Familiar | ✅ | ❌ | ✅ | ✅ | |
| AMG - NEXO - STH | GENERAL - MYPE | Bonificación | ✅ | ❌ | ✅ | ✅ | |
| AMG - NEXO - STH | GENERAL - MYPE | Reintegro Afecto | ✅ | ❌ | ✅ | ✅ | |
| AMG - NEXO - STH | GENERAL - MYPE | Movilidad | ✅ | ❌ | ❌ | ❌ | Ver sección 5 — regla varía por campaña |
| AMG - NEXO - STH | GENERAL - MYPE | Reintegro Inafecto | ✅ | ❌ | ❌ | ❌ | AMG, NEXO, SERVICIOS JP, TVVENTAS |
| AMG - NEXO - STH | GENERAL - MYPE | Tardanzas | ❌ | ✅ | ✅ resta | ✅ resta | Reduce base pensiones y neto |
| AMG - NEXO - STH | GENERAL - MYPE | Otros Descuentos | ❌ | ✅ | ❌ | ❌ | |
| AMG - NEXO - STH | GENERAL - MYPE | Adelanto de Quincena | ❌ | ✅ | ❌ | ❌ | |
| AMG - NEXO - STH | GENERAL - MYPE | Retención 5ta Categoría | ❌ | ✅ | ❌ | ❌ | |
| AMG - NEXO - STH | GENERAL - MYPE | Adelanto de Gratificación | ❌ | ✅ | ❌ | ❌ | |
| AMG | GENERAL - MYPE | Comisión | ✅ | ❌ | ✅ | ✅ | Columnas separadas COMISIÓN y BONIFICACIÓN |
| AMG | GENERAL - MYPE | Adelanto de Comisión | ❌ | ✅ | ❌ | ❌ | |
| NEXO | MYPE | Comisión / Capacitación | ✅ | ❌ | ✅ | ✅ | Columna fusionada — solo porción COMISIÓN es afecta; CAPACITACIÓN es inafecta (P-2) |
| NEXO | MYPE | Adelanto de Comisión | ❌ | ✅ | ❌ | ❌ | |
| STH | GENERAL - MYPE | Bono Nocturnidad | ✅ | ❌ | ✅ | ✅ | NEUSOFT — automático + manual |
| STH | GENERAL - MYPE | Bono Rendimiento | ✅ | ❌ | ✅ | ✅ | NEUSOFT — fijo / proporcional / externo / manual |
| STH | GENERAL - MYPE | Maqueta Inafecto | ✅ | ❌ | ❌ | ❌ | NEUSOFT ALIEXPRESS |
| STH | GENERAL - MYPE | Adelanto de Movilidad | ❌ | ✅ | ❌ | ❌ | NEUSOFT ALIEXPRESS 1ST y 2ND — efecto neto S/ 0 |
| STH | MYPE | Bono Mensual | ✅ | ❌ | ✅ | ✅ | SERVICIOS JP y TVVENTAS |
| STH | MYPE | Capacitación | ✅ | ❌ | ❌ | ❌ | |

### 6.2 Planilla RHE

> **Regla general RHE:** Sin AFP/ONP. Sin ESSALUD. IR 4ta categoría (8%) si Total Haberes ≥ S/ 1,501, salvo suspensión de renta.

| Concepto | Total Haberes | Afecto AFP/ONP | Afecto ESSALUD | Aplica a |
|---|:---:|:---:|:---:|---|
| Haber Básico proporcional | ✅ | — | — | Todos |
| Feriado / Rem. Feriado | ✅ | — | — | Todos |
| Capacitación | ✅ | — | — | Todos |
| Bono / Reintegro | ✅ | — | — | Todos (P-6: sin criterio definido) |
| Movilidad | ✅ | — | — | Todos — regla varía por campaña (ver sección 5) |
| Bono Rendimiento | ✅ | — | — | CLARO RHE, RHE NEUSOFT |
| Comisiones | ✅ | — | — | CLARO RHE |
| Tardanzas / Descuentos | ❌ | — | — | CLARO RHE, RHE NEUSOFT |
| Otros descuentos | ❌ | — | — | SERVICIOS JP, TVVENTAS, ADMINISTRATIVO |
| Adelanto de Quincena | ❌ | — | — | Todos |
| IR 4ta categoría (8%) | ❌ | — | — | Todos — se resta fuera de Total Descuentos |

### 6.3 Resumen por concepto interno del sistema

| Concepto en sistema | Régimen | Total Haberes | Afecto AFP/ONP | Afecto ESSALUD |
|---|---|:---:|:---:|:---:|
| `hbr` — haber básico proporcional | General/MYPE | ✅ | ✅ | ✅ |
| `rv` — remuneración vacacional | General/MYPE | ✅ | ✅ | ✅ |
| `pdm` — descanso médico | General/MYPE | ✅ | ❌ | ❌ |
| `feriado` | General/MYPE | ✅ | ✅ | ✅ |
| `afr` — asignación familiar | General/MYPE | ✅ | ✅ | ✅ |
| `COMISION` | General/MYPE | ✅ | ✅ | ✅ |
| `BONIFICACION` | General/MYPE | ✅ | ✅ | ✅ |
| `REINTEGRO_AFECTO` | General/MYPE | ✅ | ✅ | ✅ |
| `MOVILIDAD` | General/MYPE | ✅ | ❌ | ❌ |
| `REINTEGRO_INAFECTO` | General/MYPE | ✅ | ❌ | ❌ |
| `CAPACITACION` | General/MYPE + RHE | ✅ | ❌ | ❌ |
| `BONIFICACION_NOCTURNA` | General/MYPE NEUSOFT | ✅ | ✅ | ✅ |
| `BONO_REGULAR_MAQUETA` | General/MYPE NEUSOFT | ✅ | ✅ | ✅ |
| `MAQUETA_INAFECTO` | General/MYPE NEUSOFT | ✅ | ❌ | ❌ |
| `bono_horas_nocturnas` | General/MYPE NEUSOFT | ✅ | ✅ | ✅ |
| `bono_proporcional_noches` | General/MYPE NEUSOFT | ✅ | ✅ | ✅ |
| `bono_proporcional_dias` | General/MYPE NEUSOFT | ✅ | ✅ | ✅ |
| `bono_externo` BONO_RENDIMIENTO | General/MYPE NEUSOFT | ✅ | ✅ | ✅ |
| `bono_externo` MAQUETA_INAFECTO | General/MYPE NEUSOFT | ✅ | ❌ | ❌ |
| `fijo_rendimiento` | STH MYPE NEUSOFT SISTEMAS | ✅ | ✅ | ✅ |
| `fijo_movilidad` | STH MYPE NEUSOFT SISTEMAS | ✅ | ❌ | ❌ |
| `movilidad_proporcional` | ALIEXPRESS 3RD, CAINIAO | ✅ | ❌ | ❌ |
| `movilidades` | RHE CAINIAO | ✅ | — | — |
| `descuento_tardanza` | General/MYPE | ❌ (resta base) | ✅ resta | ✅ resta |
| `ADELANTO_COMISION` | General/MYPE | ❌ | ❌ | ❌ |
| `ADELANTO_MOVILIDAD` | General/MYPE NEUSOFT | ❌ | ❌ | ❌ |
| `ADELANTO_GRATIFICACION` | General/MYPE | ❌ | ❌ | ❌ |
| `DESCUENTOS` | General/MYPE + RHE | ❌ | ❌ | ❌ |
| `hbr` | RHE | ✅ | — | — |
| `feriado` | RHE | ✅ | — | — |
| `MOVILIDAD` / `MOVILIDADES` | RHE | ✅ | — | — |
| `CAPACITACION` | RHE | ✅ | — | — |
| `COMISION` / `BONO_RENDIMIENTO` / `REINTEGRO` | RHE | ✅ | — | — |
| `ir_4ta` (8%) | RHE | ❌ (resta en neto) | — | — |

### 6.4 Adicionales registrados en el sistema (`fact_adicionales`)

Los siguientes conceptos se ingresan manualmente en `fact_adicionales` y el sistema los lee automáticamente cada periodo:

| Tipo | Afecto AFP | Provisiones | Descuento neto | Aplica a |
|---|---|---|---|---|
| COMISION | Sí | Sí | — | CLARO |
| BONIFICACION | Sí | Sí | — | CLARO, ALIEXPRESS |
| REINTEGRO_AFECTO | Sí | Sí | — | CLARO, ALIEXPRESS, TVENTAS |
| MOVILIDAD | No | No | — | CLARO, ALIEXPRESS, TVENTAS |
| REINTEGRO_INAFECTO | No | No | — | CLARO, ALIEXPRESS, TVENTAS |
| ADELANTO_COMISION | — | — | Sí | CLARO |
| DESCUENTOS | — | — | Sí | CLARO, ALIEXPRESS, TVENTAS |
| BONIFICACION_NOCTURNA | Sí | Sí | — | ALIEXPRESS |
| BONO_REGULAR_MAQUETA | Sí | Sí | — | ALIEXPRESS |
| MAQUETA_INAFECTO | No | No | — | ALIEXPRESS |
| TARDANZA | — | — | Sí (S/ 5/día) | TVENTAS; para CLARO y ALIEXPRESS siempre S/ 0 |
| CAPACITACION | No | No | — | Todos |

### 6.5 Adicionales fijos recurrentes (`dim_adicionales_fijos`)

Conceptos fijos que no se ingresan manualmente cada mes. Se configuran una sola vez y el sistema los aplica automáticamente mientras estén vigentes.

| Campo | Descripción |
|---|---|
| `numero_documento` | DNI del trabajador |
| `tipo_adicional` | Mismo valor que en `fact_adicionales` (ej. `MOVILIDAD`) |
| `monto` | Importe fijo mensual |
| `periodo_inicio` | Primer periodo de aplicación (`YYYY-MM`) |
| `periodo_final` | Último periodo (`YYYY-MM`); `NULL` = indefinido |

**Reglas:**
- Si ya existe un registro manual en `fact_adicionales` con el mismo `contrato_id` y `tipo_adicional`, el fijo no se suma — el manual prevalece sin importar el monto.
- Si el trabajador cesa, se cierra con `periodo_final`. Si regresa, nueva fila.
- El tipo hereda las mismas reglas de afectación AFP/provisiones de la tabla anterior.

---

## 7. Adelanto de Quincena

El sistema calcula automáticamente el adelanto de la primera quincena.

**Regla general (todos los FT y RHE):**
- Se evalúan los días trabajados entre el día 1 y el día 12 del mes (Q1).
- Si el trabajador tiene **ausencias en Q1 ≤ 3** y llegó al día 13, recibe el **adelanto completo** (`haber_basico / 2`).
- Si tiene más ausencias, el adelanto es proporcional a los días de Q1.
- Si no tiene ningún día de asistencia en Q1, no se genera adelanto.

**Regla especial STH — RHE — CAINIAO:**
Al adelanto calculado se agrega **S/ 100 adicionales**, siempre que el trabajador tenga al menos un día de asistencia en Q1.

---

## 8. ESSALUD (costo empleador)

Solo aplica a FT (General y MYPE). Es un costo del empleador, **no se descuenta al trabajador.**

```
ESSALUD = max(base_imponible, RMV) × 9%
```

- Tope mínimo: se usa la RMV completa (S/ 1,130) como base, incluso si el trabajador ganó menos ese mes.
- Solo se aplica al **movimiento más reciente** de cada persona en el período.

---

## 9. AFP / ONP

Solo FT. Las tasas se obtienen de la base de datos según el fondo de pensiones del trabajador:

| AFP | Aporte | Prima de seguro | Comisión |
|---|---|---|---|
| (Se leen de dim_fondo_pensiones en BD) | Variable | Variable | Variable |

**Base imponible AFP/ONP** = suma de conceptos afectos (ver tabla en sección 3) menos tardanzas.

---

## 10. IR 4ta Categoría (solo RHE)

```
IR 4ta = Total Haberes × 8%    (si Total Haberes ≥ S/ 1,501)
IR 4ta = S/ 0                  (si Total Haberes < S/ 1,501 o tiene suspensión de renta)
```

La suspensión de renta se indica en el contrato del trabajador. No entra en Total Descuentos — se resta directamente en Neto a Recibir.

---

## 11. Retención 5ta Categoría (solo FT)

Se aplica a trabajadores en planilla cuyo ingreso anual proyectado supere el tramo imponible. El sistema la calcula automáticamente en base al acumulado del año.

---

## 12. Fórmula del neto

**FT (General y MYPE):**
```
Neto = Total Haberes − Total Descuentos
     (donde Total Descuentos incluye pensiones + adelanto quincena + adelanto comisión
      + tardanzas + descuento MD + retención 5ta + otros descuentos)
```

**RHE:**
```
Neto = Total Haberes − Total Descuentos − IR 4ta categoría
```

---

## 13. Provisiones del empleador (solo FT)

El sistema calcula las provisiones como costo del empleador (no afectan el neto del trabajador):

| Provisión | Base de cálculo |
|---|---|
| Gratificación | `basico_compensatorio / 6` |
| CTS | `basico_compensatorio / 12 + asignacion_familiar / 12` |
| Vacaciones | `basico_compensatorio / 12` |
| ESSALUD | `max(base_imponible, RMV) × 9%` |

**Basico compensatorio** = Total Haberes menos conceptos inafectos (Movilidad, Reintegro Inafecto, Capacitación, Maqueta Inafecto, Movilidad Proporcional, Movilidad Fija).

Para RHE: no hay provisiones. `basico_compensatorio = Total Haberes`, `provisiones = S/ 0`.

---

## 14. Pendientes que requieren confirmación de RRHH

| # | Tema | Detalle | Decisión requerida |
|---|---|---|---|
| P-1 | Feriado SISTEMAS / ASISTENTE DE TI | El sistema calcula S/ 0 por feriado para estos cargos. | ¿Es correcto? ¿Por qué razón no aplica feriado? |
| P-2 | NEXO — COMISIÓN / CAPACITACIÓN | La planilla fusiona ambos conceptos en una sola columna. El sistema necesita el monto de cada uno por separado. | ¿RRHH los ingresa separados en la BD? ¿O cómo se distinguen? |
| P-3 | Movilidad ALIEXPRESS 1ST y 2ND | Estos montos se pagan semanalmente y deben cargarse en `fact_adicionales` al cierre de mes. | Confirmar flujo de carga y responsable. |
| P-4 | Triggers de estado (ACTIVO / CESADO / VENCIDO) | La lógica automática que cambia el estado de los contratos al cierre del período no está implementada. | Confirmar los criterios exactos y aprobar la lógica. |
| P-5 | Columnas sin fórmula en planillas | En todas las planillas existe una columna de movilidad sin fórmula que nunca entra en los cálculos (MOV. SUPEDITADA en FT, MOVILIDAD en RHE). | ¿Qué representa ese campo? ¿Es el monto contractual de referencia o un campo obsoleto? |
| P-6 | BONO / REINTEGRO en RHE | Presente como adicional en todos los RHE, pero sin criterio definido de qué tipo de pago representa. | Documentar qué es y cuándo aplica. |

---

## 15. Inconsistencias identificadas en las planillas actuales

> Estas observaciones no bloquean el sistema pero conviene resolverlas para asegurar consistencia futura.

| # | Observación | Planillas afectadas |
|---|---|---|
| I-1 | Un mismo concepto tiene tres nombres distintos: MOVILIDAD, MOVILIDADES, MOVILIDAD SUPEDITADA A LA ASISTENCIA | Todas (12 planillas) |
| I-2 | RHE NEUSOFT usa la fórmula de días trabajados de FT (resta DM y V), pero DM y V no existen en su asistencia. Si algún trabajador RHE NEUSOFT tuviera DM o V, el cálculo fallaría silenciosamente. | STH RHE NEUSOFT |
| I-3 | NEXO fusiona COMISIÓN (afecta) y CAPACITACIÓN (inafecta) en una sola columna, sin forma de distinguir el monto de cada una. | NEXO MYPE |
| I-4 | Las columnas TARDANZAS en CLARO RHE y RHE NEUSOFT son SIN FORMULA y no están referenciadas en ninguna fórmula de las planillas. Existe una columna separada TARDANZAS / DESCUENTOS que sí se usa. | STH CLARO RHE, STH RHE NEUSOFT |

---

*Documento preparado por el equipo de TI — Mayo 2026*
*Para consultas técnicas sobre el sistema, contactar al área de TI.*
