# Reglas de Cálculo — Sistema de Nómina

**Versión:** 1.2
**Fecha:** 15 de mayo de 2026
**Autor:** Herlin Yauri — Analista de Sistemas

---

## 1. Alcance

El sistema automatiza el cálculo mensual de nómina para las tres empresas del grupo con sus planillas:

| EMPRESA | REGIMEN | FAMILIA | CENTRO COSTO |
|---|---|---|---|
| AMG-STH | General | Claro | Operaciones |
| AMG | General | ADMIN | Gestión Humana |
| AMG | General | ADMIN | Administración |
| AMG-STH | General-MYPE | Claro | Delivery |
| AMG | General-MYPE | Claro | Soporte de Ventas |
| AMG | General-MYPE | Claro | Fotos Corporativo |
| STH | General-MYPE | Neusoft | Aliexpress (1ST) |
| AMG-NEXO | MYPE | ADMIN | Mantenimiento |
| STH | MYPE | Neusoft | Aliexpress (2ND) |
| STH | MYPE | Neusoft | Sistemas |
| AMG | MYPE | ADMIN | Sistemas |
| AMG | MYPE | Gerencia | Gerencia |
| NEXO | MYPE | Claro | Delivery |
| NEXO | MYPE | Claro | Soporte de Ventas |
| STH | MYPE-RHE | TVVENTAS | TVVENTAS |
| STH | MYPE-RHE | Neusoft | Aliexpress (3RD) |
| STH | MYPE-RHE | Gerencia | Gerencia |
| STH | RHE | Neusoft | CAINIAO |
| STH | RHE | Gerencia | Administración |
| STH | RHE | Gerencia | Mantenimiento |
| STH | RHE | Claro | Operaciones |
| STH | RHE | Claro | Soporte de Ventas |

## 2. Períodos y parámetros legales vigentes (desde Abril 2026)

| Parámetro | Valor |
|---|---|
| Remuneración Mínima Vital (RMV) | S/ 1,130.00 |
| Tasa ESSALUD | 9% |
| Tasa IR 4ta categoría (RHE) | 8% |
| Umbral IR 4ta | Total Haberes ≥ S/ 1,501 |

> Estos parámetros están registrados en el sistema como constantes legales con fecha de vigencia. Ante cualquier cambio (por ejemplo, una nueva RMV), basta actualizar el valor y su fecha de inicio en un único lugar — todos los cálculos que dependen de él (Asignación Familiar, tope mínimo ESSALUD) se ajustan automáticamente. Los cálculos de períodos pasados siempre se ejecutan con el valor que estaba vigente en ese momento, garantizando la trazabilidad histórica.

## 3. Regímenes

El sistema opera bajo dos regímenes con obligaciones distintas:

| | General / MYPE | RHE (Recibo por Honorarios) |
|---|---|---|
| AFP u ONP | Sí — descuento al trabajador | No aplica |
| ESSALUD | Sí — costo del empleador (9%) | No aplica |
| Retención 5ta categoría | Sí — si corresponde por renta anual | No aplica |
| IR 4ta categoría | No aplica | Sí — 8% si Total Haberes ≥ S/ 1,501 |
| Provisiones (gratificación, CTS, vacaciones) | Sí — costo del empleador | No aplica |
| Suspensión de renta | No aplica | Sí — exonera el IR 4ta si el trabajador la tramitó |

**Fórmula del neto:**

- **General / MYPE:** Neto = Total Haberes − Total Descuentos
- **RHE:** Neto = Total Haberes − Total Descuentos − IR 4ta categoría

## 4. Haberes

### 4.1 Base de cálculo: días trabajados

El sistema cuenta los días del mes calendario (28 a 31 según el mes). Cada código de asistencia tiene un efecto distinto:

| Código | Descripción | Genera haber básico |
|---|---|---|
| A, A1, A2, A3, A4 | Asistencia (turno diurno / nocturno) | Sí |
| D | Descanso semanal | Sí |
| LC, TR, C | Licencia con goce, tránsito, capacitación | Sí |
| MD | Medio día trabajado | Sí (día completo; se descuenta medio día aparte) |
| V | Vacaciones | No — genera Remuneración Vacacional |
| DM | Descanso médico | No — genera Descanso Médico |
| FI, FJ | Falta injustificada, falta justificada | No |
| LS | Licencia sin goce | No |

> **Feriados (TF):** el sistema identifica los días feriados automáticamente a partir del calendario de feriados registrado en la base de datos. No es un código que ingresa RRHH — el sistema cuenta cuántos días del período del trabajador caen en feriado y aplica el doble del valor diario por cada uno.

### 4.2 Conceptos — General / MYPE

| Concepto | Afecto AFP/ONP | Afecto ESSALUD | Aplica a |
|---|:---:|:---:|---|
| Haber Básico Proporcional | Sí | Sí | Todos |
| Remuneración Vacacional | Sí | Sí | Todos |
| Descanso Médico | No | No | Todos |
| Feriado | Sí | Sí | Todos — doble del valor diario por día feriado trabajado |
| Asignación Familiar | Sí | Sí | Solo si el trabajador tiene hijos registrados |
| Comisión | Sí | Sí | AMG |
| Bonificación | Sí | Sí | AMG, NEUSOFT ALIEXPRESS |
| Reintegro Afecto | Sí | Sí | AMG, NEXO, TVVENTAS — incluye Horas Extra y Compra de Descanso |
| Horas Extra | Sí | Sí | Cargado por RRHH desde Excel — se consolida en la columna Reintegro Afecto |
| Compra de Descanso | Sí | Sí | Cargado por RRHH desde Excel — se consolida en la columna Reintegro Afecto |
| Reintegro Inafecto | No | No | AMG, NEXO, TVVENTAS |
| Maqueta Inafecto | No | No | NEUSOFT ALIEXPRESS |
| Bono de Nocturnidad | Sí | Sí | NEUSOFT ALIEXPRESS — ver detalle más abajo |
| Bono de Rendimiento | Sí | Sí | NEUSOFT — ver detalle más abajo |
| Movilidad | No | No | Todos — ver detalle más abajo |

> **NEXO:** La columna de planilla fusiona Comisión y Capacitación en un solo valor (`COMISIÓN / CAPACITACIÓN`). El sistema lo trata actualmente como afecto en su totalidad. Pendiente separación (P-1).

> **Horas Extra y Compra de Descanso:** actualmente se agrupan en la columna Reintegro Afecto junto con el reintegro propiamente dicho. Esto impide distinguir en la salida cuánto corresponde a cada concepto, lo que dificulta la verificación y auditoría. Pendiente asignarles columnas propias en la tabla de resultados para tener trazabilidad individual por concepto.

> **Medio día (MD):** el trabajador cobra haber básico del día completo, pero se descuenta el equivalente a medio día en Total Descuentos.

### 4.3 Asignación Familiar

Solo General / MYPE. Se paga si el trabajador tiene hijos registrados en el sistema (flag habilitado en su contrato).

```
Asignación Familiar = RMV × 10% = S/ 113.00
```

**No se prorratea por días trabajados** — es un monto fijo mensual independiente de la asistencia. Si el trabajador no tiene hijos registrados, el sistema devuelve S/ 0.

### 4.4 Conceptos — RHE

| Concepto | Aplica a |
|---|---|
| Haber Básico Proporcional | Todos |
| Feriado | Todos — doble del valor diario por día feriado trabajado |
| Capacitación | Todos |
| Bono / Reintegro | Todos — sin criterio definido aún (P-2) |
| Comisiones | CLARO RHE |
| Bono Rendimiento | CLARO RHE, RHE NEUSOFT |
| Movilidad | Todos — ver detalle más abajo |

> En RHE no aplica AFP/ONP ni ESSALUD para ningún concepto.

### 4.5 Movilidad

**En todos los casos es inafecta a AFP/ONP y ESSALUD.**

#### 4.5.1 General / MYPE

| Campaña | Cargo | Cómo se calcula | Monto base |
|---|---|---|---|
| AMG, NEXO, SERVICIOS JP, TVVENTAS | Todos | Manual por RRHH | Variable |
| NEUSOFT ALIEXPRESS 1ST (General) | SUPERVISOR, CALIDAD | Pagada semana a semana (Excel Resumen Pagos Semanales). Al cierre se registra en Total Haberes y se descuenta como Adelanto de Movilidad — **efecto neto S/ 0** | Variable |
| NEUSOFT ALIEXPRESS 1ST (General) | FORMACION, JEFE DE OPERACIONES | No aplica | — |
| NEUSOFT ALIEXPRESS 1ST (MYPE) | ATENCION AL CLIENTE | Pagada semana a semana (Excel Resumen Pagos Semanales). Al cierre se registra en Total Haberes y se descuenta como Adelanto de Movilidad — **efecto neto S/ 0** | Variable |
| NEUSOFT ALIEXPRESS 2ND (MYPE) | ATENCION AL CLIENTE | Pagada semana a semana (Excel Resumen Pagos Semanales). Al cierre se registra en Total Haberes y se descuenta como Adelanto de Movilidad — **efecto neto S/ 0** | Variable |
| NEUSOFT ALIEXPRESS 3RD (MYPE) | ATENCION AL CLIENTE, ASESOR DE VENTAS | `S/ 250 / días_mes × días con turno nocturno (considera descanso)` | S/ 250 |
| NEUSOFT SISTEMAS | ASISTENTE DE TI | Monto fijo mensual — **no se prorratea por días trabajados** | S/ 500 |

#### 4.5.2 RHE

| Campaña | Cargo | Cómo se calcula | Monto base |
|---|---|---|---|
| RHE NEUSOFT ALIEXPRESS 3RD | ATENCION AL CLIENTE | `S/ 250 / días_mes × días con turno nocturno (considera descanso)` | S/ 250 |
| RHE NEUSOFT CAINIAO | SUPERVISOR, ATENCION AL CLIENTE | `S/ 250 / días_mes × días con turno nocturno (considera descanso)` | S/ 250 |
| TVVENTAS RHE | Todos | `S/ 150 / días_mes × días trabajados` | S/ 150 |
| ADMINISTRATIVO RHE | Todos | Manual por RRHH | Variable |
| SERVICIOS JP RHE | Todos | Manual por RRHH | Variable |
| CLARO RHE | Todos | Manual por RRHH | Variable |

> Ninguna movilidad RHE tiene Adelanto de Movilidad — todas se pagan al cierre del mes.

### 4.6 Bono TVVENTAS

**Inafecto AFP/ONP y ESSALUD.**

El sistema calcula automáticamente un bono mensual para los trabajadores del centro de costo TVVENTAS:

```
Bono TVVENTAS = S/ 300 / días_mes × días con asistencia
```

Donde días con asistencia cuenta los códigos: A, A1, A2, A3, A4, MD, D (excluye faltas y ausencias).

> **Campaña descontinuada.** La fórmula queda documentada para referencia. Consultar en reunión final si el cálculo debe mantenerse, archivarse o eliminarse del sistema.

### 4.7 Bono de Nocturnidad

**Afecto AFP/ONP y ESSALUD. Aplica únicamente a General / MYPE.**

| Campaña | Cargo | Cómo se calcula | Monto base |
|---|---|---|---|
| NEUSOFT ALIEXPRESS 1ST (General) | SUPERVISOR | `S/ 400 / días_mes × días con turno nocturno (considera descanso)` | S/ 400 |
| NEUSOFT ALIEXPRESS 1ST (General) | CALIDAD | `S/ 300 / días_mes × días con turno nocturno (considera descanso)` | S/ 300 |
| NEUSOFT ALIEXPRESS 1ST (MYPE) | ATENCION AL CLIENTE | `S/ 250 / días_mes × días con turno nocturno (considera descanso)` | S/ 250 |
| NEUSOFT ALIEXPRESS 2ND (MYPE) | ATENCION AL CLIENTE | `S/ 1.75 × total de horas nocturnas del mes` | S/ 1.75 por hora |

> **ALIEXPRESS 1ST:** proporcional a días con marcación nocturna. Los días de descanso (D) heredan la clasificación nocturna si la secuencia previa fue predominantemente nocturna.

> **ALIEXPRESS 2ND:** se paga por hora, no por día. El sistema detecta las horas nocturnas según el código de asistencia (A1, A2, etc.). Los días de descanso (D) heredan las horas nocturnas del turno más frecuente en la secuencia que los rodea — primero se busca hacia atrás, y si no hay referencia, hacia adelante.

### 4.8 Bono de Rendimiento

**Afecto AFP/ONP y ESSALUD en régimen General/MYPE. En RHE no aplica AFP/ONP ni ESSALUD.**

| Campaña | Régimen | Cargo | Cómo se calcula | Monto base |
|---|---|---|---|---|
| NEUSOFT ALIEXPRESS 1ST (General) | General | FORMACION, JEFE DE OPERACIONES, SUPERVISOR, CALIDAD | Enviado desde Excel externo cada mes | Variable |
| STH MYPE NEUSOFT SISTEMAS | MYPE | ASISTENTE DE TI | Monto fijo mensual — **no se prorratea por días trabajados** | S/ 360 |
| NEUSOFT ALIEXPRESS 3RD | RHE | ATENCION AL CLIENTE | Enviado desde Excel externo cada mes | Variable |
| NEUSOFT CAINIAO | RHE | SUPERVISOR | `S/ 300 / días_mes × días trabajados` | S/ 300 |
| NEUSOFT CAINIAO | RHE | ATENCION AL CLIENTE | `S/ 450 / días_mes × días trabajados` | S/ 450 |

> **Excel externo:** RRHH carga el monto desde el Excel Resumen de Pagos cada cierre de mes. El sistema lo recibe como valor ya calculado — no aplica fórmula propia.

> **CAINIAO:** proporcional a días trabajados, no a turno nocturno. El monto varía según el cargo.

## 5. Descuentos

### 5.1 General / MYPE

| Concepto | Aplica a | Notas |
|---|---|---|
| AFP — Fondo de pensiones | Afiliados AFP | Tasa según fondo contratado |
| AFP — Prima de seguro | Afiliados AFP | Tasa según fondo contratado |
| AFP — Comisión variable | Afiliados AFP | Tasa según fondo contratado |
| ONP | Afiliados ONP | 13% sobre base imponible |
| Retención 5ta categoría | Todos — si corresponde | Se calcula sobre el acumulado anual proyectado |
| Adelanto de quincena | Todos | Ver sección 9 |
| Adelanto de comisión | AMG, NEXO, SERVICIOS JP, TVVENTAS | Manual por RRHH |
| Adelanto de movilidad | NEUSOFT ALIEXPRESS 1ST y 2ND | Igual al monto de movilidad — efecto neto S/ 0 |
| Adelanto de gratificación | Todos | Aplica en los meses de pago de gratificación (julio y diciembre). Los detalles técnicos del cálculo están pendientes de documentar — el sistema aún no lo contempla. |
| Tardanzas | Todos | Suma a Total Descuentos y reduce base AFP/ONP. **TVVENTAS:** S/ 5 por día, calculado automáticamente desde la asistencia (días con tardanza registrada). **CLARO y ALIEXPRESS:** siempre S/ 0. |
| Descuento por medio día (MD) | Todos | `haber_basico / días_mes / 2 × cantidad de MD` |
| Otros descuentos | Todos | Manual por RRHH |

**Base imponible AFP/ONP** = suma de conceptos afectos − tardanzas.

### 5.2 RHE

| Concepto | Aplica a | Notas |
|---|---|---|
| Adelanto de quincena | Todos | Ver sección 9 |
| Tardanzas / Descuentos | CLARO RHE, RHE NEUSOFT | Manual por RRHH |
| Otros descuentos | SERVICIOS JP, TVVENTAS, ADMINISTRATIVO | Manual por RRHH |
| IR 4ta categoría (8%) | Todos — si corresponde | Se resta **fuera** de Total Descuentos, directo en Neto a Recibir. No aplica si Total Haberes < S/ 1,501 o el trabajador tiene suspensión de renta |

## 6. ESSALUD

Solo aplica a régimen General y MYPE. Es un costo del empleador — **no se descuenta al trabajador.**

```
ESSALUD = max(base_imponible, RMV) × 9%
```

- **Tope mínimo:** si el trabajador ganó menos que la RMV (S/ 1,130) ese mes, ESSALUD se calcula igualmente sobre la RMV completa.
- Si un trabajador tuvo más de un contrato activo en el mismo período dentro de la misma empresa, ESSALUD se aplica solo al movimiento más reciente. Cada empresa del grupo calcula ESSALUD de forma independiente.

## 7. AFP y ONP

Solo régimen General y MYPE. Se descuentan al trabajador.

**Base imponible** = suma de conceptos afectos − tardanzas.

**AFP:** Las tasas (aporte obligatorio, prima de seguro y comisión variable) se leen directamente de la tabla `dim_fondo_pensiones` en la base de datos, mantenida con información oficial de la SBS.

**ONP:** Tasa fija de 13% sobre la base imponible.

## 8. Retención 5ta Categoría

Solo régimen General y MYPE. Se aplica cuando el ingreso anual acumulado del trabajador supera el tramo imponible según el impuesto a la renta.

Actualmente RRHH la calcula en Excel en base al historial de remuneraciones del trabajador y la carga como adicional (`RETENCION_5TA`). El sistema la descuenta directamente en Total Descuentos. La implementación del cálculo automático dentro del sistema está pendiente — requiere integrar el historial de pagos del año y la lógica de proyección de renta anual.

## 9. Adelanto de Quincena

El sistema calcula automáticamente el adelanto de la primera quincena y lo suma a Total Descuentos.

### 9.1 Regla general — todos los regímenes

El mes se divide en dos quincenas fijas: **Q1 = días 1 al 12**, **Q2 = día 13 al fin de mes**.

**Días que cuentan en Q1** para el cálculo del adelanto (días con pago completo):

| Cuenta | No cuenta |
|---|---|
| A, A1, A2, A3, A4, A7 (asistencia) | FI, FJ (faltas) |
| D (descanso semanal) | LS (licencia sin goce) |
| DM (descanso médico) | C (capacitación) |
| MD (medio día), LC (licencia con goce) | TR (tránsito) |
| V (vacaciones) | |

**Ausencias en Q1** = días de incorporación tardía al período + FI + FJ + LS en Q1.

| Condición | Adelanto |
|---|---|
| Llegó al día 13 y ausencias en Q1 ≤ 3 | Adelanto completo: `haber_basico × 40%` |
| Llegó al día 13 y ausencias en Q1 > 3 | Proporcional: `haber_basico / días_mes × días con pago completo en Q1` |
| Sin ningún día que cuente en Q1 | S/ 0 — no se genera adelanto |

### 9.2 Regla especial — STH RHE CAINIAO

Al adelanto calculado se suman **S/ 100 adicionales**, siempre que el trabajador tenga al menos un día de asistencia en Q1. Esta regla solo aplica a trabajadores que no tienen adelanto fijo configurado (ver 9.3).

### 9.3 Adelanto fijo por trabajador

Algunos trabajadores tienen un monto de adelanto fijo configurado en el sistema, independiente del cálculo Q1. Cuando existe esta configuración, el sistema usa ese monto directamente — no aplica la fórmula general ni la regla especial CAINIAO.

El monto fijo puede ser S/ 0, lo que equivale a registrar explícitamente que ese trabajador no recibe adelanto de quincena bajo ninguna circunstancia.

Esta configuración se gestiona en la tabla de conceptos recurrentes del sistema (ver sección 10).

## 10. Carga de adicionales por RRHH

Los conceptos variables del mes (comisiones, bonificaciones, movilidades, reintegros, descuentos, etc.) no los calcula el sistema — los registra RRHH al cierre de cada período.

**Proceso:**
1. RRHH recolecta los resúmenes de cada centro de costo (Excel Resumen de Pagos Semanales, liquidaciones de campaña, etc.).
2. Unifica todos los montos en un único archivo Excel con el formato requerido por la plataforma.
3. Carga el archivo de forma masiva a través de la aplicación web, que los registra en la tabla `fact_adicionales`.
4. El sistema de nómina los lee automáticamente al procesar el período.

Los conceptos fijos recurrentes se configuran una sola vez en el sistema indicando el DNI del trabajador, el tipo de concepto, el monto y el período de inicio. Se aplican automáticamente cada mes sin necesidad de cargarlos de nuevo, hasta que se registre un período de fin o se cese al trabajador.

El sistema distingue dos categorías de conceptos recurrentes:

| Categoría | Efecto | Ejemplos |
|---|---|---|
| `adicional_fijo` | Suma a Total Haberes | Movilidad fija, bono fijo |
| `adelanto_fijo` | Suma a Total Descuentos | Adelanto de quincena fijo (`ADELANTO_QUINCENA`) |

**Regla de prioridad:** si en un período dado existe tanto un fijo configurado como un ingreso manual del mismo tipo para el mismo trabajador, el sistema usa el manual y descarta el fijo. Esto permite hacer ajustes puntuales sin modificar la configuración base.

Tanto los adicionales manuales como los fijos están asociados a un período (`YYYY-MM`). Esto garantiza que cualquier cálculo ejecutado sobre un período — ya sea en el cierre del mes o en una revisión posterior — siempre producirá los mismos resultados, con exactamente los conceptos que correspondían a ese momento.

## 11. Provisiones del Empleador

Solo aplica a régimen General y MYPE. Son costos del empleador — **no se descuentan al trabajador.**

Las provisiones se calculan mensualmente para determinar el costo real de cada trabajador. No afectan el neto del trabajador salvo en los meses de pago efectivo (julio y diciembre para gratificación).

### 11.1 Base de cálculo: Básico Compensatorio

```
Básico Compensatorio = Total Haberes − Movilidad
```

Donde Movilidad comprende todos los conceptos de movilidad del trabajador: adicional MOVILIDAD, movilidad fija, movilidad proporcional y movilidades (RHE CAINIAO). Al ser inafectos, no forman parte de la base de provisiones.

> **RHE:** no aplica — Básico Compensatorio = 0 y las provisiones son S/ 0.

### 11.2 Gratificación

Se pagan dos gratificaciones al año: en julio (por el período enero–junio) y en diciembre (por el período julio–diciembre).

| Régimen | Fórmula mensual | Equivale a |
|---|---|---|
| General | Básico Comp. / 6 | 2 gratificaciones × 1 sueldo / año |
| MYPE | Básico Comp. / 12 | 2 gratificaciones × ½ sueldo / año |

**En los meses de pago (julio y diciembre):**

La gratificación y el bono extraordinario aparecen en el Total Haberes de la planilla y se descuentan íntegramente como Adelanto de Gratificación en el Total Descuentos — el efecto sobre el neto del trabajador es S/ 0. El pago real se realiza por depósito separado.

> **Pendiente de implementación:** el cálculo del monto real de gratificación en julio y diciembre (con prorrateo por meses completos trabajados en el semestre) aún no está implementado en el sistema.

### 11.3 Bonificación Extraordinaria (9%)

Costo adicional del empleador calculado sobre la provisión de gratificación. Por ley, la gratificación no está afecta a ESSALUD pero genera esta bonificación equivalente.

```
Bonificación Extraordinaria = Provisión Gratificación × 9%
```

### 11.4 CTS (Compensación por Tiempo de Servicios)

Beneficio social que se deposita dos veces al año: en mayo (por el período noviembre–abril) y en noviembre (por el período mayo–octubre).

| Régimen | Fórmula mensual | Equivale a |
|---|---|---|
| General | Básico Comp. / 12 | 1 sueldo / año |
| MYPE | Básico Comp. / 24 | ½ sueldo / año |

> **Pendiente de implementación:** el cálculo del depósito semestral real aún no está implementado en el sistema.

### 11.5 Vacaciones

| Régimen | Fórmula mensual | Equivale a |
|---|---|---|
| General | Básico Comp. / 12 | 30 días / año |
| MYPE | Básico Comp. / 24 | 15 días / año |

### 11.6 Costo Total del Empleador

**General / MYPE:**
```
Costo Total = Básico Compensatorio + Movilidad + ESSALUD
            + Provisión Gratificación + Bonificación Extraordinaria 9%
            + Provisión CTS + Provisión Vacaciones
```

**RHE:**
```
Costo Total = Adelanto de Quincena + Neto a Recibir
           (Neto a Recibir = Total Haberes − Total Descuentos − IR 4ta)
```

### 11.7 Resumen por concepto

| Concepto | General | MYPE | Período de pago | Estado |
|---|---|---|---|---|
| Gratificación | Básico / 6 | Básico / 12 | Julio y diciembre | Provisión implementada — pago real pendiente |
| Bonificación Extraordinaria | Grat × 9% | Grat × 9% | Julio y diciembre | Implementada |
| CTS | Básico / 12 | Básico / 24 | Mayo y noviembre | Provisión implementada — depósito real pendiente |
| Vacaciones | Básico / 12 | Básico / 24 | Al momento del descanso | Implementada |

## 12. Inconsistencias identificadas en las planillas

Las siguientes observaciones no bloquean el sistema pero requieren definición para asegurar consistencia entre las planillas Excel y el sistema.

| # | Observación | Planillas afectadas | Estado |
|---|---|---|---|
| I-1 | Las planillas muestran una columna de movilidad sin fórmula (`MOV. SUPEDITADA A LA ASISTENCIA` en FT, `MOVILIDAD` en RHE) que no entra en ningún cálculo. | Todas | **Resuelto** — campo referencial únicamente. Uso futuro por definir. |
| I-2 | NEXO fusiona COMISIÓN (afecta AFP/ESSALUD) y CAPACITACIÓN (inafecta) en una sola columna sin posibilidad de separar los montos. El sistema trata el valor completo como COMISIÓN (afecto). | NEXO MYPE | Pendiente reunión final — se decide si se separan las columnas (ver P-1) |
| I-3 | En CLARO RHE y RHE NEUSOFT existe una columna `TARDANZAS` sin fórmula y sin referencia en ningún cálculo. La columna real usada es `TARDANZAS / DESCUENTOS`. | STH CLARO RHE, STH RHE NEUSOFT | Por eliminar o aclarar |

## 13. Pendientes de confirmación

| # | Tema | Estado | Detalle |
|---|---|---|---|
| P-1 | NEXO — COMISIÓN / CAPACITACIÓN | **Pendiente reunión final** | El sistema trata el valor completo como COMISIÓN (afecto AFP/ESSALUD). Se mantendrá así hasta la reunión final con RRHH, donde se decidirá si se requiere separar las columnas para distinguir el monto afecto del inafecto. |
| P-2 | BONO / REINTEGRO en RHE | **Pendiente reunión final** | RRHH ingresa manualmente reintegros y horas extras bajo este concepto. Se mantendrá así hasta la reunión final con RRHH, donde se decidirá si se requiere mayor detalle o columnas separadas. |

## 14. Mejoras sugeridas al sistema

Estas mejoras no son requisitos para la puesta en producción pero se recomiendan para robustecer el sistema en etapas futuras.

### 14.1 Restricción de códigos de asistencia por régimen

Actualmente el sistema web permite registrar cualquier código de asistencia sin validar si corresponde al régimen del trabajador. Algunos códigos como DM (descanso médico) o V (vacaciones) no aplican al régimen RHE, pero si se registrasen por error, el motor de nómina los descartaría silenciosamente sin generar ninguna alerta.

**Mejora sugerida:** restringir en el sistema web los códigos de asistencia disponibles según el régimen del contrato activo del trabajador. Un trabajador RHE no debería poder tener registrados códigos que solo aplican a planilla fija (General / MYPE), y viceversa. Esto evita errores de ingreso que el motor de nómina no puede detectar por sí solo.

### 14.2 Módulo de registro de bonos por supervisor

Actualmente los bonos sin lógica automática definida (rendimiento variable, bonos por evaluación de desempeño, etc.) se recopilan desde Excel externo y los carga RRHH manualmente al cierre del mes. Esto implica un proceso de recolección manual entre supervisores, RRHH y el sistema.

**Mejora sugerida:** incorporar en el sistema web un módulo donde cada supervisor pueda registrar directamente los bonos asignados a sus asesores al cierre del período. El flujo sería:

1. El supervisor ingresa los bonos de su equipo en el sistema, indicando trabajador, monto y concepto.
2. Los bonos quedan en estado **pendiente** — no impactan los cálculos de nómina.
3. RRHH revisa los bonos registrados y los aprueba o rechaza desde el mismo sistema.
4. Solo al aprobar RRHH el bono cambia a estado **aprobado** e impacta en los cálculos del período.

Este flujo elimina el ciclo de Excel entre supervisores y RRHH, reduce el riesgo de errores de transcripción y permite trazabilidad completa de quién registró y quién aprobó cada bono. A nivel del motor de nómina, simplifica el código al unificar estos conceptos bajo la misma lógica de `fact_adicionales` que ya existe, sin necesidad de manejar casos especiales por campaña.
