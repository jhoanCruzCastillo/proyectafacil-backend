# GUÍA DE LLENADO — II. IDENTIFICACIÓN (2.3 a 2.6) — POBLACIÓN, PROBLEMA Y ALTERNATIVAS

*(FTE Saneamiento Urbano — 24 campos, 15 de ellos tablas)*

La sección más larga del módulo II. Encadena cuatro cosas en orden: **a quién afecta** (2.3), **qué
problema hay** (2.4), **qué se propone** (2.5) y **cuánto cierra de brecha** (2.6).

El 2.6 es el que alimenta el numeral 1.4 de la sección I — no al revés.

---

## 2.3 Diagnóstico de la población afectada

### `7.01.1` — año base

El año de referencia del diagnóstico, del que parte la proyección de la población.

### `7.01.2` — información general del área de influencia

Una fila por dato: **detalle, unidad de medida, valor y fuente**. Acá van la población afectada, la
**tasa de crecimiento intercensal** (provincial, distrital o de centro poblado, estimada entre los
últimos censos), la densidad por vivienda, etc.

La fuente esperada es el **Censo Nacional de Población y Vivienda del INEI** o el **padrón de
usuarios**. Cita la fuente en su columna: un valor sin fuente no sirve para sustentar la proyección.

### `7.02.1` — información del centro poblado urbano

La columna de **centro poblado es calculada** (viene del numeral 1.3). Se llenan población,
viviendas, ingreso promedio y fuente.

### `7.03.1` a `7.03.4` — consumo de los NO conectados

Este bloque solo aplica a la población **sin** conexión, y sirve para estimar después el beneficio
por ahorro de recursos. Dos modalidades:

- **Camión cisterna** (`7.03.1` a `7.03.3`): número de cilindros/tanques que llena a la semana, pago
  por cada uno y capacidad del recipiente. Con eso se calcula el costo por m³ que hoy paga esa
  población, que suele ser mucho mayor que la tarifa de red.
- **Acarreo desde la fuente** (`7.03.4`): una fila por tipo de persona que acarrea, con el tiempo
  empleado, los viajes, los baldes por viaje y la capacidad del balde.

Si toda la población está conectada, este bloque va **vacío**.

### `7.04.1` y `7.04.2` — población con y sin acceso

Una tabla para agua potable y otra para saneamiento. **Varias columnas son calculadas**: el ámbito y
el "sin acceso" los deriva el Excel. Lo que se llena es la población y las viviendas **con** acceso,
sustentadas en el **padrón de usuarios** (o documento similar) donde se vea la cantidad de personas
por vivienda.

La aritmética es la misma de siempre: `sin acceso = ámbito − con acceso`.

### `7.05.1` — diagnóstico de involucrados

Una fila por involucrado. Por cada uno: **posición** (cooperante u oponente), **interés**,
**estrategia** y **compromiso**.

Los involucrados típicos son la **Entidad Prestadora del Servicio de Saneamiento**, el **Gobierno
Local** y la **población beneficiaria**; pueden sumarse comisiones de regantes, juntas vecinales,
comunidades, etc. Los **compromisos de operación y mantenimiento** deben estar sustentados y
adjuntarse en anexos: no basta con declararlos acá.

---

## 2.4 Problema central, causas y efectos

### `7.06.1` — problema central

Redacción en positivo del déficit, con la población, los servicios y la localización. Ejemplo del
instructivo:

> "Población con limitado y deficiente acceso a los servicios de agua potable y saneamiento mediante
> alcantarillado sanitario y tratamiento de aguas residuales en el CCPP Chincha Alta, distrito de
> Chincha Alta, provincia de Chincha, departamento de Ica"

Tiene que estar sustentado en el diagnóstico de las UP: si acá se dice "deficiente", el 2.2 debe
mostrarlo.

### `7.06.2` y `7.06.3` — causas directas e indirectas

Estas tablas tienen **una columna por sistema**: agua potable, alcantarillado, tratamiento,
saneamiento básico y **gestión**. Las causas directas están **predeterminadas** y se relacionan con
las unidades productoras: una fila por causa, y en cada columna la causa de ese sistema.

Si el proyecto no interviene un sistema, esa columna va vacía — no se inventa una causa para
rellenar.

Las causas indirectas son el detalle: *"insuficiente capacidad de captación"*, *"redes de
distribución deterioradas"*, *"insuficientes conexiones domiciliarias"*.

### `7.06.4` — efectos directos

Los efectos están acotados por el instructivo a dos, principalmente:

1. Incremento de la incidencia de **enfermedades gastrointestinales y dérmicas**.
2. Incremento del **gasto en salud** de las familias por enfermedades relacionadas al consumo de agua
   de mala calidad.

---

## 2.5 Planteamiento del proyecto

### `7.07.1` — objetivo central

Es la **situación opuesta al problema central** y debe estar relacionado con la naturaleza de
intervención. Si el problema dice "población con limitado y deficiente acceso...", el objetivo dice
"población con suficiente y adecuado acceso...", misma localización y mismos servicios.

### `7.08.1` y `7.08.2` — medios de primer nivel y medios fundamentales

Misma estructura de columnas por sistema. Los **medios fundamentales se relacionan de manera inversa
a las causas indirectas** del 2.4: si una causa indirecta fue "redes de distribución deterioradas",
el medio fundamental es "adecuada infraestructura de redes de distribución".

Revisa la correspondencia una por una: un medio sin causa que le corresponda, o una causa sin medio,
es un error de construcción del árbol.

### `7.08.3` — fin específico

Espejo de los efectos: disminución de la incidencia de enfermedades gastrointestinales y dérmicas, y
disminución del gasto en salud de las familias.

### `7.09.1` — alternativas de solución

Una fila por **alternativa + servicio + UP**, con la descripción de las **acciones** que la componen.

Lo que distingue una alternativa de otra suele ser la **fuente de agua**: por ejemplo Alternativa 1
con captación **subterránea** (pozos, manantes, galerías filtrantes) y Alternativa 2 con captación
**superficial** (río, lago, laguna); también cabe una fuente **mixta**. Cada alternativa debe dar
**solución integral** a la demanda de todos los servicios, no de uno solo.

Las acciones deben corresponder a los **activos estratégicos aprobados por el Sector** (listado del
MVCS). En el **Anexo 13** se adjunta el desarrollo técnico de la segunda alternativa.

Recuerda: **alcantarillado y UBS son UP distintas**, así que no se plantean como dos alternativas
comparables; una misma alternativa puede contemplar ambos sistemas.

### `7.09.2` — nota del 2.5.4

Solo se llena **si se propone una única alternativa**: hay que sustentar técnicamente por qué no es
posible plantear otras. Si hay dos o más alternativas, va vacío.

---

## 2.6 Aporte al cierre de brecha

Cuatro tablas con la misma aritmética, y en todas **`a`, `c` y la columna de brecha son calculadas**:

```
(a) universo del ámbito de influencia
(b) los que SÍ tienen el servicio / la condición adecuada
(c) = (a) − (b)
(d) ≤ (c)    contribución al cierre de brechas     <-- esto es lo que se llena
```

| Campo | Indicador |
|---|---|
| `7.10.1` | Cobertura — población sin acceso a agua potable y a saneamiento |
| `7.10.2` | Cobertura — volumen de aguas residuales no tratadas (m³/año) |
| `7.11.1` | Calidad — viviendas con cloro residual menor a 0.5 mg/l |
| `7.11.2` | Calidad — población sin continuidad del servicio |

**La contribución (d) nunca puede superar el déficit (c).** Si la evidencia da un número mayor, es un
error del expediente: se usa el déficit.

Creación y ampliación aportan a **cobertura**; mejoramiento y recuperación, a **calidad**. Los
valores de `7.11.1` y `7.11.2` tienen que ser coherentes con lo declarado en `6.02.1`.

---

## Errores que hay que evitar

- Escribir en las columnas calculadas de `7.02.1`, `7.04.x`, `7.10.x` y `7.11.x`.
- Declarar una contribución mayor que el déficit.
- Un medio fundamental que no corresponde a ninguna causa indirecta (o al revés).
- Redactar el objetivo central sin que sea el espejo del problema central.
- Llenar el bloque de no conectados (`7.03.x`) cuando toda la población está conectada.
- Plantear una alternativa que resuelve solo un servicio y deja los otros sin solución.
- Llenar `7.09.2` habiendo dos alternativas, o dejarlo vacío habiendo una sola.
- Inventar causas para columnas de sistemas que el proyecto no interviene.
