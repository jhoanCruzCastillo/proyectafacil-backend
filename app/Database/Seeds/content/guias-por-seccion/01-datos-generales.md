# SECCIÓN N°01: DATOS GENERALES DEL PROYECTO

## Descripción de la sección

- **Qué representa:** la identificación institucional, funcional y de alineamiento del proyecto de inversión (formato FTE-CUIDADO-DIURNO; hoja Excel `Datos Generales`).
- **Objetivo:** registrar quién formula y quién ejecuta la inversión, la clasificación funcional/tipológica del proyecto, los componentes del nombre del proyecto y su contribución al cierre de una brecha prioritaria.
- **Qué información contiene:** datos de la Unidad Formuladora (UF) y de la Unidad Ejecutora de Inversiones (UEI); función / división / grupo funcional; sector y tipología; naturaleza, objeto y localización del nombre; indicador de brecha y valores de contribución.
- **Para qué sirve dentro de la ficha:** es la portada identificatoria del proyecto. Los campos calculados de esta sección aparecen rellenados en el JSON EJEMPLO aunque en la ESTRUCTURA figuren como no editables; no deben tratarse como captura manual.

**Hoja Excel:** `Datos Generales`

**Subsecciones:** `1.01`, `1.02`, `1.03`, `1.04`

---

# 1.01 Institucionalidad

Identifica a las dos unidades institucionales del proyecto. El JSON incluye notas explícitas que separan el bloque:

- `UNIDAD FORMULADORA (UF)` — campos `1.01.01` a `1.01.04`
- `UNIDAD EJECUTORA DE INVERSIONES (UEI)` — campos `1.01.05` a `1.01.08`

### Diferencia importante

Varios campos repiten el mismo **nombre** en UF y UEI (`Nivel de gobierno :`, `Entidad :`). Son nodos distintos (`1.01.01`≠`1.01.05`, `1.01.02`≠`1.01.06`). No deben copiarse entre sí salvo que el ejemplo/proyecto lo justifique; el JSON EJEMPLO muestra valores iguales en nivel de gobierno y entidad, pero responsables y nombres de unidad distintos.

---

## Campo 1.01.01 — Nivel de gobierno :

**Tipo:** texto_corto

**Editable:** Sí

**Qué representa:** nivel de gobierno de la Unidad Formuladora (UF).

**Qué debe contener:** una de las opciones del catálogo `etiquetas` del JSON ESTRUCTURA.

**Valores permitidos (etiquetas):**

- Gobierno Nacional
- Gobierno Regional
- Gobierno Local

**Regla de llenado:** elegir exactamente una etiqueta de la lista. No inventar niveles fuera del catálogo.

**Ejemplo:**

```json
"Gobierno Nacional"
```

---

## Campo 1.01.02 — Entidad :

**Tipo:** texto_corto

**Editable:** Sí

**Qué representa:** entidad a la que pertenece la Unidad Formuladora.

**Qué debe contener:** nombre de la entidad institucional de la UF.

**Regla de llenado:** texto libre corto. Información no determinada por los archivos proporcionados respecto a un catálogo cerrado de entidades.

**Ejemplo:**

```json
"Programa Nacional Cuna Más"
```

---

## Campo 1.01.03 — Nombre de la UF :

**Tipo:** texto_corto

**Editable:** Sí

**Qué representa:** nombre de la Unidad Formuladora.

**Qué debe contener:** denominación completa de la UF.

**Regla de llenado:** texto libre corto. El ejemplo sugiere que suele incluir la expresión "Unidad Formuladora" más el nombre de la entidad.

**Ejemplo:**

```json
"Unidad Formuladora Programa Nacional Cuna Más"
```

---

## Campo 1.01.04 — Responsable de la UF :

**Tipo:** texto_corto

**Editable:** Sí

**Qué representa:** persona responsable de la Unidad Formuladora.

**Qué debe contener:** nombre del responsable de la UF.

**Regla de llenado:** texto libre corto (nombre de persona).

**Ejemplo:**

```json
"Juan Perez"
```

---

## Campo 1.01.05 — Nivel de gobierno :

**Tipo:** texto_corto

**Editable:** Sí

**Qué representa:** nivel de gobierno de la Unidad Ejecutora de Inversiones (UEI).

**Qué debe contener:** una de las opciones del catálogo `etiquetas`.

**Valores permitidos (etiquetas):**

- Gobierno Nacional
- Gobierno Regional
- Gobierno Local

**Regla de llenado:** elegir exactamente una etiqueta. Es el homólogo UEI de `1.01.01`, no el mismo campo.

**Ejemplo:**

```json
"Gobierno Nacional"
```

---

## Campo 1.01.06 — Entidad :

**Tipo:** texto_corto

**Editable:** Sí

**Qué representa:** entidad a la que pertenece la UEI.

**Qué debe contener:** nombre de la entidad institucional de la UEI.

**Regla de llenado:** texto libre corto. Homólogo UEI de `1.01.02`.

**Ejemplo:**

```json
"Programa Nacional Cuna Más"
```

---

## Campo 1.01.07 — Nombre de la UEI :

**Tipo:** texto_corto

**Editable:** Sí

**Qué representa:** nombre de la Unidad Ejecutora de Inversiones.

**Qué debe contener:** denominación de la UEI.

**Regla de llenado:** texto libre corto. El ejemplo sugiere una forma abreviada tipo "UEI …".

**Ejemplo:**

```json
"UEI Cuna Más"
```

---

## Campo 1.01.08 — Responsable de la UEI :

**Tipo:** texto_corto

**Editable:** Sí

**Qué representa:** persona responsable de la UEI.

**Qué debe contener:** nombre del responsable de la UEI.

**Regla de llenado:** texto libre corto. Debe distinguirse de `1.01.04` (responsable de la UF).

**Ejemplo:**

```json
"Carlos Perez"
```

---

# 1.02 Responsabilidad funcional y tipología del proyecto de inversión

Clasifica el proyecto según la cadena funcional (función → división → grupo) y declara sector responsable y tipología.

### Nota sobre JSON ESTRUCTURA vs JSON EJEMPLO

En el JSON ESTRUCTURA, los campos `1.02.01` a `1.02.05` ya traen valores no vacíos (coinciden con el EJEMPLO). El resto de campos editables de la sección 01 en ESTRUCTURA sí están vacíos. Priorizar el schema de ESTRUCTURA; los valores de ESTRUCTURA aquí parecen plantilla precargada de tipología, no un vacío genérico.

---

## Campo 1.02.01 — Función

**Tipo:** texto_corto

**Editable:** Sí

**Qué representa:** función presupuestal/clasificadora del proyecto.

**Qué debe contener:** código y nombre de la función, en el formato que muestra el ejemplo.

**Regla de llenado:** el ejemplo sugiere el patrón `"<código> <NOMBRE EN MAYÚSCULAS>"`. No hay catálogo `etiquetas` en el JSON para este campo.

**Ejemplo:**

```json
"23 PROTECCIÓN SOCIAL"
```

---

## Campo 1.02.02 — División funcional

**Tipo:** texto_corto

**Editable:** Sí

**Qué representa:** división funcional dentro de la función.

**Qué debe contener:** código y nombre de la división funcional.

**Regla de llenado:** mismo patrón de código + nombre que `1.02.01`. Debe ser coherente con la Función, según lo que sugiera el ejemplo; la dependencia obligatoria no está declarada en el JSON.

**Ejemplo:**

```json
"051 ASISTENCIA SOCIAL"
```

---

## Campo 1.02.03 — Grupo funcional

**Tipo:** texto_corto

**Editable:** Sí

**Qué representa:** grupo funcional dentro de la división.

**Qué debe contener:** código y nombre del grupo funcional.

**Regla de llenado:** mismo patrón código + nombre. El ejemplo sugiere coherencia con Función y División.

**Ejemplo:**

```json
"0115 PROTECCIÓN DE POBLACIONES EN RIESGO"
```

---

## Campo 1.02.04 — Sector responsable

**Tipo:** texto_corto

**Editable:** Sí

**Qué representa:** sector responsable del proyecto.

**Qué debe contener:** nombre del sector (en el ejemplo, en mayúsculas).

**Regla de llenado:** texto corto. Información no determinada por los archivos proporcionados respecto a un catálogo cerrado de sectores.

**Ejemplo:**

```json
"DESARROLLO E INCLUSION SOCIAL"
```

---

## Campo 1.02.05 — Tipología de proyecto

**Tipo:** texto_corto

**Editable:** Sí

**Qué representa:** tipología del proyecto de inversión.

**Qué debe contener:** nombre de la tipología.

**Regla de llenado:** texto corto. En este formato, el ejemplo usa la tipología de Centro Infantil de Atención Integral.

**Ejemplo:**

```json
"CENTRO INFANTIL DE ATENCIÓN INTEGRAL"
```

---

# 1.03 Nombre del proyecto de inversión

Arma (o refleja) el nombre del proyecto a partir de tres componentes en la misma fila del Excel: naturaleza (editable), objeto (calculado) y localización (calculado).

### Diferencia importante

Solo `1.03.01` es de captura manual. `1.03.02` y `1.03.03` son `tipo: calculado` / `editable: false` en el JSON ESTRUCTURA: **no deben llenarse** en el autollenado.

El ejemplo sugiere que el nombre completo del proyecto se lee como concatenación de los tres fragmentos (naturaleza + objeto + localización), pero la fórmula exacta de composición **no está determinada por los archivos proporcionados**.

---

## Campo 1.03.01 — Naturaleza de intervención

**Tipo:** texto_corto

**Editable:** Sí

**Qué representa:** tipo de intervención que tipifica el proyecto en su nombre.

**Qué debe contener:** una de las opciones del catálogo `etiquetas`.

**Valores permitidos (etiquetas):**

- Mejoramiento
- Ampliación
- Mejoramiento y ampliación
- Recuperación

**Regla de llenado:** elegir exactamente una etiqueta.

**Ejemplo:**

```json
"Mejoramiento"
```

---

## Campo 1.03.02 — Objeto de intervención

**Tipo:** calculado

**Editable:** No

**Qué representa:** fragmento del nombre del proyecto que describe el objeto de la intervención.

**Qué debe contener:** no es campo de captura manual.

**Regla:** este campo no debe tratarse como un campo de captura manual. En el JSON EJEMPLO aparece un texto descriptivo del servicio/objeto; de dónde se calcula exactamente **no está determinado por los archivos proporcionados** (solo se observa el resultado).

**Ejemplo (solo referencia del valor calculado observado):**

```json
"del Servicio de Cuidado Infantil en el Centro Infantil de Atención Integral (CIAI)"
```

---

## Campo 1.03.03 — Localización

**Tipo:** calculado

**Editable:** No

**Qué representa:** fragmento del nombre del proyecto con la localización geográfica.

**Qué debe contener:** no es campo de captura manual.

**Regla:** no llenar manualmente. El ejemplo sugiere una frase que encadena localidad, distrito, provincia y departamento; la fuente exacta de esos datos dentro de la ficha **no está determinada por los archivos proporcionados** en esta sección.

**Ejemplo (solo referencia del valor calculado observado):**

```json
"en la localidad de San Antonio, distrito de San Sebastian, provincia de Cusco, departamento de Cusco"
```

---

# 1.04 Alineamiento y contribución al cierre de una brecha prioritaria

Declara el servicio con brecha priorizada, el indicador de brecha, su unidad de medida, el espacio geográfico y el año de referencia, más dos valores numéricos editables: el valor del indicador y la contribución del proyecto al cierre de brecha.

### Diferencia importante

- `1.04.01` … `1.04.05` son **calculados / no editables** en ESTRUCTURA → no llenar.
- `1.04.06` (Valor) y `1.04.07` (Contribución del Cierre de Brecha) son **decimal / editables** → sí son captura.

---

## Campo 1.04.01 — Servicios públicos con brecha identificada y priorizada

**Tipo:** calculado

**Editable:** No

**Qué representa:** servicio público respecto del cual existe una brecha identificada y priorizada.

**Regla:** no llenar manualmente.

**Ejemplo (valor calculado observado):**

```json
"Servicio de cuidado diurno"
```

---

## Campo 1.04.02 — Nombre del Indicador de brecha de acceso a servicios

**Tipo:** calculado

**Editable:** No

**Qué representa:** nombre del indicador de brecha de acceso a servicios.

**Regla:** no llenar manualmente.

**Ejemplo (valor calculado observado):**

```json
"Porcentaje de centros infantiles de atención integral que brindan el servicio de cuidado integral en condición inadecuada. (PCIAICI)"
```

---

## Campo 1.04.03 — Unidad de medida

**Tipo:** calculado

**Editable:** No

**Qué representa:** unidad de medida del indicador de brecha.

**Regla:** no llenar manualmente.

**Ejemplo (valor calculado observado):**

```json
"Centro Infantil de Atención Integral (CIAI)"
```

---

## Campo 1.04.04 — Espacio geográfico

**Tipo:** calculado

**Editable:** No

**Qué representa:** ámbito geográfico al que se refiere el indicador de brecha.

**Regla:** no llenar manualmente. El ejemplo sugiere un distrito; la regla de derivación exacta no está determinada en esta sección.

**Ejemplo (valor calculado observado):**

```json
"Distrito de San Sebastian"
```

---

## Campo 1.04.05 — Año

**Tipo:** calculado

**Editable:** No

**Qué representa:** año de referencia del valor del indicador.

**Regla:** no llenar manualmente. En el ejemplo el valor aparece como texto `"2026"` aunque el nombre del campo es "Año".

**Ejemplo (valor calculado observado):**

```json
"2026"
```

---

## Campo 1.04.06 — Valor

**Tipo:** decimal (`decimales: 2` en ESTRUCTURA)

**Editable:** Sí

**Qué representa:** valor del indicador de brecha para el espacio geográfico y año de referencia.

**Qué debe contener:** un valor numérico decimal (hasta 2 decimales según schema).

**Regla de llenado:** campo editable de captura. **Diferencia ESTRUCTURA vs EJEMPLO:** el schema declara `tipo: decimal`, pero el JSON EJEMPLO guarda el string `"60%"` (incluye símbolo de porcentaje). Al autollenar, preferir el tipo del schema (`decimal`) si la fuente de verdad aporta un número; si se reproduce el estilo del ejemplo, el valor observado incluye `%` como texto. No está determinado por los archivos si el Excel espera fracción (`0.60`), porcentaje numérico (`60`) o texto con `%`.

**Ejemplo (tal como aparece en el JSON EJEMPLO):**

```json
"60%"
```

---

## Campo 1.04.07 — Contribución del Cierre de Brecha (Valor)

**Tipo:** decimal (`decimales: 2` en ESTRUCTURA)

**Editable:** Sí

**Qué representa:** magnitud de la contribución del proyecto al cierre de la brecha.

**Qué debe contener:** valor decimal (hasta 2 decimales).

**Regla de llenado:** captura manual editable. En el JSON EJEMPLO el valor es el número `1` (tipo JSON number), no un string. Qué unidad expresa exactamente ese `1` **no está determinado por los archivos proporcionados** (¿un CIAI? ¿un punto porcentual?). El ejemplo solo muestra el número.

**Ejemplo:**

```json
1
```

---

## Resumen de campos editables vs no editables (Sección 01)

| ID | Nombre | Editable | Acción en autollenado |
|---|---|---|---|
| 1.01.01 | Nivel de gobierno : (UF) | Sí | Llenar (catálogo) |
| 1.01.02 | Entidad : (UF) | Sí | Llenar |
| 1.01.03 | Nombre de la UF : | Sí | Llenar |
| 1.01.04 | Responsable de la UF : | Sí | Llenar |
| 1.01.05 | Nivel de gobierno : (UEI) | Sí | Llenar (catálogo) |
| 1.01.06 | Entidad : (UEI) | Sí | Llenar |
| 1.01.07 | Nombre de la UEI : | Sí | Llenar |
| 1.01.08 | Responsable de la UEI : | Sí | Llenar |
| 1.02.01 | Función | Sí | Llenar |
| 1.02.02 | División funcional | Sí | Llenar |
| 1.02.03 | Grupo funcional | Sí | Llenar |
| 1.02.04 | Sector responsable | Sí | Llenar |
| 1.02.05 | Tipología de proyecto | Sí | Llenar |
| 1.03.01 | Naturaleza de intervención | Sí | Llenar (catálogo) |
| 1.03.02 | Objeto de intervención | No | NO LLENAR (calculado) |
| 1.03.03 | Localización | No | NO LLENAR (calculado) |
| 1.04.01 | Servicios públicos con brecha… | No | NO LLENAR (calculado) |
| 1.04.02 | Nombre del Indicador de brecha… | No | NO LLENAR (calculado) |
| 1.04.03 | Unidad de medida | No | NO LLENAR (calculado) |
| 1.04.04 | Espacio geográfico | No | NO LLENAR (calculado) |
| 1.04.05 | Año | No | NO LLENAR (calculado) |
| 1.04.06 | Valor | Sí | Llenar (decimal) |
| 1.04.07 | Contribución del Cierre de Brecha (Valor) | Sí | Llenar (decimal) |
