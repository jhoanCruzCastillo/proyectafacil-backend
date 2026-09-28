# GUÍA DE LLENADO — FORMATO N.° 07-C: REGISTRO DE IOARR

*(hoja de Excel "Formato 07-C IOARR" — una sola sección con 128 campos)*

---

## Lo primero: esto se llena por bloques excluyentes

El formato **no se llena de arriba a abajo completo**. Tiene dos decisiones que abren o cierran
bloques enteros, y llenar un bloque que no corresponde es un error de registro, no un extra.

### Decisión 1 — el TIPO de IOARR (campo `3.02.01`)

Según el tipo elegido se llena **un solo** bloque de la sección E:

| Bloque | Se llena cuando el tipo es |
|---|---|
| `5.01` | **Optimización** |
| `5.02` | **Ampliación Marginal del Servicio** |
| `5.03` | **Ampliación Marginal de la Edificación u Obra Civil** |
| `5.04` | **Ampliación Marginal para Adquisición Anticipada de Terrenos** |
| `5.05` | **Ampliación Marginal para Liberación de Interferencias** |
| `5.06` | **Reposición** |
| `5.07` | **Rehabilitación de Infraestructura** |
| `5.08` | **Rehabilitación de Equipos Mayores** |
| `5.09` | parámetros comunes de **Rehabilitación/Reposición** (antigüedad, estado, costos de mantenimiento) |
| `5.10` | **Inversión Masiva de Activos** (varias UP — única excepción a "una sola UP por registro") |

Es posible considerar **más de un tipo de inversión por Unidad Productora**; en ese caso se llena
cada bloque que corresponda.

### Decisión 2 — el monto: 75 UIT

| Monto del activo | Qué se llena |
|---|---|
| **Mayor a 75 UIT** | Registro **completo**: bloques `6.01` a `6.06` (metas, costos, cronogramas, modalidad, financiamiento) |
| **Menor o igual a 75 UIT** | Registro **simplificado**: bloques `7.01` a `7.05` |

Los dos caminos **no se llenan a la vez**.

### Esto vale también cuando te toca UNA SOLA tabla

Las tablas del formato se llenan de a una, en llamadas separadas, y cada llamada ve solo su tabla.
Eso NO te exime de las dos decisiones de arriba: **antes de escribir nada en una tabla de los bloques
`5.x` o `7.x`, averigua en la fuente de la verdad qué tipo de IOARR se registra y si los activos
superan las 75 UIT**, exactamente como lo harías para el formato entero.

- Si la tabla pertenece a un bloque de la sección E que **no** corresponde al tipo de IOARR del
  expediente, devuélvela **con todas sus celdas vacías**. No la rellenes "por analogía" con datos de
  otro bloque: una tabla de Reposición o de Inversión Masiva llenada en una Optimización es un error
  de registro, aunque los datos vengan de la fuente correcta.
- Lo mismo con el bloque `7.x`: si los activos superan las 75 UIT, el registro simplificado va
  **entero vacío**, aunque tengas a mano los costos y el cronograma de mantenimiento.
- Caso real de esta guía: en una IOARR de **Optimización** con activos mayores a 75 UIT, las únicas
  tablas de esos dos tramos que llevan contenido son las de `5.01`. Las de `5.02` a `5.10` y las de
  `7.01` a `7.05` van vacías.

---

## Bloques que siempre se llenan

### `0.01` Encabezado
El **nombre de la inversión** se construye con una fórmula fija:

> **acciones + activos + nombre de la Unidad Productora + localización geográfica de la UP**

Ejemplo real: *"ADQUISICIÓN DE EQUIPO Y MOBILIARIO DE AULAS DE INNOVACIÓN PEDAGÓGICA Y EQUIPO Y
MOBILIARIO DE LABORATORIO Y TALLERES PARA LA IE EDELMIRA DEL PANDO, DISTRITO DE ATE, PROVINCIA LIMA,
REGIÓN DE LIMA"*.

El **código único** solo se consigna si el Banco de Inversiones ya lo asignó; si la inversión es
nueva, va vacío — nunca se inventa.

### `1.01` Alineamiento a una brecha prioritaria
Función, división funcional, grupo funcional y sector responsable. El indicador de brecha admite
**más de un servicio y más de un indicador**, cada uno con su tipo (Calidad / Cobertura), unidad de
medida y contribución del proyecto al valor del PMI.

### `2.01` Institucionalidad
Cuatro bloques: OPMI, UF, UEI (cada uno con nivel de gobierno, entidad, nombre y responsable) y el
nombre de la UEP.

### `3.01` / `3.02` Datos generales
Código de identificación de la UP (código modular, de establecimiento, de rutas, de inventario de
recursos turísticos, según lo haya definido el Sector), nombre de la UP y su localización
(departamento, provincia, distrito, centro poblado, coordenadas UTM). Para **UP lineales** se adjunta
un archivo **KML/Excel** con las coordenadas UTM y el número de orden secuencial.

En `3.02.01` va la tabla que decide todo: por cada activo, si la inversión es mayor a 75 UIT, el tipo
de IOARR, la acción sobre el activo, el activo y el tipo de factor productivo.

### `4.01` / `4.02` Estado situacional del activo y responsable del mantenimiento
Inscripción registral (con partida y oficina registral), registro en el inventario de la entidad, y
la Unidad Ejecutora Presupuestal que asumirá el financiamiento del mantenimiento.

---

## Reglas que invalidan el registro

Están detalladas en los Lineamientos IOARR; las que más pesan al llenar:

- La capacidad final **no puede superar en más del 20 %** la capacidad de diseño original. Los campos
  de capacidad (`5.01.08` a `5.01.11`, `5.02.08` a `5.02.11`) son justamente los que lo demuestran:
  si el incremento pasa del 20 %, corresponde un **Proyecto de Inversión**, no una IOARR.
- Una UP **inoperativa más de un año** exige un PI de Recuperación.
- **No se puede registrar** una IOARR sobre activos de una UP que ya tuvo una IOARR en los últimos
  **3 años**, ni sobre una UP que está siendo intervenida por un PI.
- El **mantenimiento permanente no es IOARR**. Remodelar o reparar instalaciones sanitarias o
  eléctricas, por sí solo, tampoco.
- **No se coloca el nombre del activo** cuando la IOARR es de **Liberación de Interferencias**.

---

## Dónde está la evidencia

- **Brecha e indicadores**: diagnóstico de brechas del Sector y PMI de la entidad.
- **Código y datos de la UP**: registro sectorial correspondiente (padrón, RENIPRESS, código modular,
  inventario de recursos turísticos, etc.).
- **Inscripción registral**: partida registral y oficina registral (SUNARP).
- **Inventario**: código del inventario de activos de la entidad.
- **Estado situacional, restricciones y problema operativo**: informe de diagnóstico de la UP.
- **Antigüedad, estado y vida útil de equipos**: inventario y reportes técnicos del área usuaria.
- **Costos**: cotizaciones, valores referenciales del sector o adquisiciones similares.

---

## Errores que hay que evitar

- Llenar varios bloques de la sección E "por si acaso". Solo va el que corresponde al tipo.
- Llenar a la vez el registro completo (`6.x`) y el simplificado (`7.x`).
- Inventar el código único de la inversión cuando todavía no fue asignado.
- Trasplantar contenido de ficha técnica (diagnóstico extenso, problema/objetivo, evaluación social):
  este formato no los tiene.
- Redactar el nombre de la inversión sin la fórmula acciones + activos + UP + localización.
- Declarar un incremento de capacidad mayor al 20 % y seguir registrándolo como IOARR.
