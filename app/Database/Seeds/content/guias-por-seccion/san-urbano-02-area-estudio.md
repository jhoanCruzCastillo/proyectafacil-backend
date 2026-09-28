# GUÍA DE LLENADO — II. IDENTIFICACIÓN (2.1) — ÁREA DE ESTUDIO E INFLUENCIA

*(FTE Saneamiento Urbano — numerales 2.1.1 a 2.1.5, 9 campos)*

Esta sección describe **dónde** ocurre el problema, con qué condiciones físicas, con qué agua
disponible y expuesto a qué peligros. Todo lo que se declare acá condiciona después el diseño, la
demanda y los costos.

---

## La distinción que más se confunde: estudio vs. influencia

| | Qué es | Tamaño |
|---|---|---|
| **Área de estudio** (`2.01.x`) | El espacio de referencia donde se localiza el problema: incluye la población afectada, la UP a intervenir (o dónde podría construirse una nueva) **y** otras UP a las que esa población podría acceder | El más grande |
| **Área de influencia** (`2.02.x`) | Solo el ámbito donde está la población afectada | Está **dentro** del anterior |

Casi siempre el área de influencia es igual o menor. Si en la evidencia salen idénticas, está bien
repetirlas; si el área de influencia sale más grande que la de estudio, hay un error.

### `2.01.1` y `2.02.1` — las tablas

Una fila por centro poblado urbano (AA.HH. / Urbanización / Sector / Localidad), con departamento,
provincia, distrito y **Ubigeo**. El Ubigeo se decide **fila por fila**: el del centro poblado si lo
tiene en el INEI, el del distrito solo si no lo tiene.

Ojo: en estas dos tablas **varias columnas son calculadas** — el Excel las arrastra de la tabla de
localización del numeral 1.3. No las escribas; en la práctica solo se completa lo que quede editable.

### `2.01.2` y `2.02.2` — zona geográfica

**Costa**, **Sierra** o **Selva**. Sale de la altitud y ubicación del centro poblado.

### `2.02.3`

Solo si corresponde (es la nota al pie de la tabla de área de influencia). Si no hay nada que
precisar, va vacío.

---

## `2.03.1` — características físicas

Una fila por centro poblado: **tipo de suelo, altitud (m.s.n.m.), temperatura (°C), precipitación
(mm), humedad (%) y la fuente**. La fuente típica es **SENAMHI**; el tipo de suelo sale del estudio
de suelos o de la memoria geotécnica.

Estos datos no son decorativos: condicionan el diseño, la demanda y los costos. Si falta uno, déjalo
vacío en vez de estimarlo.

---

## `2.04.1` — disponibilidad del recurso hídrico

**Una fila por fuente de agua.** Se pueden (y se deben) incluir fuentes que la UP hoy **no** usa: el
instructivo pide evaluar todas las fuentes factibles del área de estudio, porque de ahí salen las
alternativas de solución del numeral 2.5.3.

Por cada fuente:

- nombre, **fecha de aforo**, **Q aforado (l/s)** y **Q mínimo estimado (l/s)** — el mínimo se obtiene
  por aforo en época de estiaje o por referencia de la población;
- **cota referencial (msnm)** y coordenadas **WGS84** (Este / Norte);
- **uso actual** (consumo humano, riego, sin uso…);
- si **cuenta con disponibilidad hídrica** otorgada por la **ANA/ALA** y el **número del documento**
  (la resolución);
- **calidad del agua**: acá se señalan expresamente los parámetros que **superan los Límites Máximos
  Permisibles (LMP)**. Si ninguno los supera, se dice así, citando a DIGESA o el laboratorio.

### `2.04.2`

Si del análisis de las fuentes resulta que **hace falta tratamiento**, se declara acá. Es
consecuencia directa de la columna de calidad de la tabla anterior: si alguna fuente supera los LMP,
la respuesta no puede ser que no se necesita tratamiento.

---

## `2.05.1` — peligros del área de estudio

Una fila por peligro (sismos, deslizamientos, inundaciones, huaicos, heladas…). Por cada uno:

- si **existen antecedentes de ocurrencia** en el área (Sí/No) y, si sí, su **frecuencia**,
  **intensidad** y **grado de peligro** (bajo / medio / alto);
- si **existe información que indique futuros cambios** en las características del peligro o nuevos
  peligros (Sí/No) y, si sí, cuáles.

Fuente habitual: el **mapa de peligros de CENEPRED**. Esta tabla alimenta el numeral 2.2.6
(exposición de la UP), así que los peligros que se listen acá deben ser los mismos que aparezcan allá.

---

## Dónde está la evidencia

- **Ubicación y Ubigeo**: INEI (códigos de centro poblado), padrón del prestador.
- **Clima, temperatura, precipitación, humedad**: SENAMHI.
- **Tipo de suelo**: estudio de suelos / memoria geotécnica del expediente.
- **Aforos, caudales, cotas y coordenadas**: estudio hidrológico o hidrogeológico.
- **Disponibilidad hídrica**: resolución de la **ANA / ALA**.
- **Calidad del agua y LMP**: DIGESA o informe de laboratorio acreditado.
- **Peligros**: CENEPRED, SIGRID, estudios de riesgo.

---

## Errores que hay que evitar

- Confundir área de estudio con área de influencia, o declarar la de influencia más grande.
- Poner el mismo Ubigeo en todas las filas sin revisar cuál tiene código propio.
- Listar solo la fuente que la UP ya usa y omitir las demás fuentes factibles del área.
- Decir que no se necesita tratamiento cuando alguna fuente supera los LMP.
- Escribir en columnas calculadas de `2.01.1` / `2.02.1` (las arrastra el Excel desde el numeral 1.3).
- Listar peligros acá que después no aparecen en la tabla de exposición del 2.2.6.
