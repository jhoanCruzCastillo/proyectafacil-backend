# GUÍA DE LLENADO — II. IDENTIFICACIÓN (2.2.8) — GESTIÓN OPERATIVA DEL SERVICIO

*(FTE Saneamiento Urbano — 6 campos, los seis tablas)*

Acá se diagnostica **quién presta el servicio y cómo lo administra**, no la infraestructura. Es la
sección que sustenta después la sostenibilidad (numeral 4.6).

---

## `6.01.1` — características de la gestión actual

Una fila por servicio (o por centro poblado urbano, según corresponda). Se declara:

- **operador del servicio**: EPS, ATM (Área Técnica Municipal), JASS, la propia municipalidad, u otro;
- si cuenta con **plan operativo** y con **recursos humanos y logísticos** para la operación y
  mantenimiento;
- **porcentaje de cobertura** del servicio;
- **costo de operación y mantenimiento**, en **S/ por MES** — no anual. Si la evidencia lo trae al
  año, divídelo entre 12 antes de escribirlo;
- **pago por el servicio** (la tarifa o cuota familiar) y **subsidio**, de haberlo;
- **número de conexiones existentes por tipo de usuario**: doméstico, comercial, estatal, social e
  industrial. La columna de **total es calculada** — la suma la hace el Excel, no la escribas;
- **principales restricciones o limitaciones** para la O&M: morosidad, falta de personal, falta de
  equipos, ausencia de micromedición, tarifa por debajo del costo, etc.

---

## `6.02.1` — continuidad y calidad del agua potable

La columna de **nombre de la UP es calculada** (viene del 2.2.1). Solo se llenan dos números, y los
dos son los que alimentan después los indicadores de brecha de **calidad** del numeral 2.6.2:

- **población con continuidad** del servicio de agua potable por red pública **las 24 horas y los 7
  días de la semana** (Personas). Es un criterio estricto: si el servicio es de 6 horas al día, la
  población con continuidad es **0**, no la población atendida;
- **número de viviendas con presencia de cloro residual mayor o igual a 0.5 mg/l** (Viviendas). Es
  el límite permisible; por debajo de ese valor la vivienda cuenta como sin cloro residual adecuado.

Estos dos números tienen que ser coherentes con lo que después se declare en las tablas de brecha de
calidad: allá la población/viviendas **sin** la condición sale de restar estas de las totales.

---

## `6.03.1` a `6.03.4` — mantenimiento, un sistema por tabla

Cuatro tablas con la misma forma, una por sistema:

| Campo | Sistema |
|---|---|
| `6.03.1` | Agua potable |
| `6.03.2` | Alcantarillado sanitario |
| `6.03.3` | Tratamiento de aguas residuales |
| `6.03.4` | Disposición sanitaria de excretas |

Una fila por activo que **efectivamente recibe mantenimiento**. Por cada uno:

- si el mantenimiento es **preventivo** o **correctivo** (se marca la columna que corresponda; pueden
  ser ambas si el operador hace las dos cosas);
- **frecuencia**: mensual, trimestral, semestral, anual, eventual…;
- **fecha del último mantenimiento realizado**;
- **acciones de mantenimiento realizado**, concretas ("limpieza de instalaciones", "limpieza de
  accesorios", "cambio de empaquetaduras"), no genéricas.

Si un sistema **no existe** en la UP (por ejemplo no hay UBS), esa tabla va **vacía**. Si el sistema
existe pero **no recibe mantenimiento**, también va vacía y eso se explica en las restricciones de
`6.01.1` — no se inventan filas con "ninguno".

---

## Dónde está la evidencia

- **Operador, cobertura, conexiones y tarifa**: padrón de usuarios y reportes del prestador; **SUNASS**
  para EPS reguladas.
- **Costo de O&M**: presupuesto operativo del prestador, estados financieros.
- **Continuidad**: reportes de SUNASS o del prestador.
- **Cloro residual**: monitoreos de DIGESA o del prestador.
- **Mantenimiento**: bitácoras, órdenes de trabajo, actas de mantenimiento.

---

## Errores que hay que evitar

- Poner el costo de O&M anual en una columna que pide S/ mes.
- Escribir el total de conexiones (es calculado).
- Declarar población con continuidad igual a la población atendida cuando el servicio es por horas.
- Confundir el número de viviendas **con** cloro residual adecuado y las que están **sin** él.
- Rellenar las cuatro tablas de mantenimiento aunque el sistema no exista.
- Escribir "mantenimiento general" en vez de la acción concreta realizada.
