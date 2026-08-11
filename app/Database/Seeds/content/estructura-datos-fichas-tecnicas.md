## CONVENCIONES BASE

Estas convenciones especifican como deben estructurarse cada formato por lo que todos tendrán esta estructura base:

### 0. NIVEL GENERAL

**Para qué sirve:** Es el nodo raíz del documento JSON. Contiene los metadatos que identifican a qué formato oficial corresponde el documento completo, más el array `secciones`, que contiene todo el contenido del formulario.

```json
{
  "schema_version": "1.0",
  "formato": {
    "codigo": "06-A",
    "nombre": "Ficha Técnica General Simplificada",
    "tipo_version": "estructura"
  },
  "secciones": [ ... ]
}
```

| Propiedad | Descripción |
|---|---|
| `schema_version` | Versión de la convención JSON usada en este documento. Permite que el parser sepa qué reglas de interpretación aplicar si la convención cambia en el futuro. |
| `formato.codigo` | Código oficial del formato según la normativa (en este ejemplo, `"06-A"`). Identifica qué ficha técnica representa todo el documento. |
| `formato.nombre` | Nombre legible del formato completo. Se usa para mostrarlo en la UI (títulos, breadcrumbs, listados). |
| ~~`formato.fuente_archivo`~~ | 🔴 **ELIMINADA — 2/08/2026 a las 05:24 hrs.** Era el nombre del archivo Excel del que se extrajeron las posiciones de celda, como referencia/trazabilidad. Se quedó fuera de la convención: no la leía nadie y no había dónde persistirla. Más adelante se verá qué metadatos del formato hacen falta de verdad. |
| `formato.tipo_version` | Indica si este documento JSON completo es una `"estructura"` (cascarón vacío, plantilla sin datos) o un `"ejemplo"` (misma estructura con datos reales cargados en cada campo). |
| ~~`formato.nota_secciones`~~ | 🔴 **ELIMINADA — 2/08/2026 a las 05:24 hrs.** Era texto libre para observaciones internas del equipo (en la práctica, la bitácora de avance del modelado por sección). Se quedó fuera de la convención por la misma razón que `fuente_archivo`. |
| `secciones` | Array que contiene todos los nodos de **tipo ****`"seccion"`** del formato (ver punto 1). Es el punto de entrada del árbol de contenido. |

### 1. NIVEL SECCIONES

**Para qué sirve:** Un nodo de `tipo_nodo: "seccion"` representa una división principal del formulario — tal como se ve en el menú lateral de la UI (Sección 01, Sección 02...). Toda sección está anclada a **una sola hoja del archivo Excel**: es el nivel donde se declara explícitamente en qué pestaña del libro de Excel va a escribirse todo lo que cuelgue de ella (sus grupos y campos).

```json
{
  "id": "1",
  "nombre": "Datos generales del proyecto",
  "tipo_nodo": "seccion",
  "hoja": "DATOS GENERALES",
  "campos": [ ... ]
}
```

En este ejemplo: el nodo completo es una **sección** (`tipo_nodo: "seccion"`), con `id` `"1"`, cuyo nombre visible es *"Datos generales del proyecto"*, y toda ella está anclada a la pestaña de Excel llamada `"DATOS GENERALES"` (propiedad `hoja`).

| Propiedad | Descripción |
|---|---|
| `id` | Identificador jerárquico de la sección. Es el primer nivel del árbol (`"1"`, `"2"`, `"3"`...), usado también para ordenar las secciones en el menú lateral de la UI. |
| `nombre` | Nombre visible de la sección. Se muestra en el listado lateral del menú y como encabezado principal dentro del formulario. |
| `tipo_nodo` | Valor fijo `"seccion"`. Le indica al parser que este nodo es un contenedor de nivel superior — no es un grupo ni un campo. |
| `hoja` | Nombre exacto de la pestaña del archivo Excel donde se ubican físicamente **todos** los campos que pertenecen a esta sección, incluyendo los que están dentro de grupos anidados. Esta es la **única** fuente de verdad para la hoja de destino — no se vuelve a declarar en ningún nivel inferior: ni en `grupo`, ni en `campo`, ni dentro de `captura` (tampoco en el `captura` de una tabla). 🔴 AQUÍ MODIFIQUE HOY 1/08/2026 a las 12:42 hrs |
| `campos` | Array de nodos hijos directos de esta sección. Cada elemento de este array es, o bien un nodo de `tipo_nodo: "grupo"` (ver punto 2), o bien un nodo de `tipo_nodo: "campo"` (ver punto 3). |

### 2. NIVEL SUBSECCIONES (grupo)

**Para qué sirve:** Un nodo de `tipo_nodo: "grupo"` es un nivel de agrupación visual y lógica **dentro de una sección**. No captura ningún dato por sí mismo ni tiene ubicación propia en el Excel — solo organiza campos relacionados bajo un mismo subtítulo. Un grupo puede contener otros grupos anidados, o contener directamente campos.

✏️ **\[Corregido — se eliminó un bloque JSON duplicado\]**

En este ejemplo: el nodo completo es un **grupo** (`tipo_nodo: "grupo"`), con `id` `"1.01"`, cuyo nombre visible es *"Institucionalidad"*. Este grupo cuelga de la sección `"1"` ("Datos generales del proyecto", vista en el punto 1) — por eso su `id` extiende el de la sección padre. No declara `hoja` propia: hereda `"DATOS GENERALES"` de esa sección.

```json
{
  "id": "1.01",
  "nombre": "Institucionalidad",
  "tipo_nodo": "grupo",
  "campos": [ ... ]
}
```

| Propiedad | Descripción |
|---|---|
| `id` | Identificador jerárquico que extiende el `id` de su nodo padre (sección o grupo) agregando un nuevo segmento con punto. Refleja la profundidad del grupo dentro del árbol. |
| `nombre` | Nombre visible del grupo. Se muestra como subtítulo dentro de la sección, en la UI del formulario. |
| `tipo_nodo` | Valor fijo `"grupo"`. Indica que este nodo organiza otros nodos hijos, pero no representa un dato capturable en sí mismo. |
| `campos` | Array de nodos hijos directos de este grupo. Cada elemento es, o bien otro nodo de `tipo_nodo: "grupo"` (si hay más niveles de anidamiento), o bien un nodo de `tipo_nodo: "campo"` (si ya se llegó al nivel de captura de datos). |

> **Regla de herencia de hoja:** un nodo `grupo` **nunca** declara la propiedad `hoja`. Su hoja de destino es siempre la de su ancestro más cercano de `tipo_nodo: "seccion"`. Esto aplica sin importar cuántos niveles de grupo haya entre el campo y su sección.
>
> 🔴 **AQUÍ MODIFIQUE HOY 1/08/2026 a las 12:42 hrs** — la regla se extiende a **todos** los descendientes, no solo a los grupos: ni `campo` ni `tabla` declaran `hoja`, y **tampoco dentro de su objeto ****`captura`**. Antes de esta corrección, los ejemplos JSON de los puntos 3 y 4 mostraban `"hoja"` dentro de `captura`, lo que contradecía esta misma regla. Se eliminó de todos los ejemplos (3, 4, 4.1, 4.2, 4.3, 4.4, 4.5, 4.5b y 4.6) y de la tabla de propiedades del punto 4.

### 3. NIVEL CAMPO (hoja del árbol)

**Para qué sirve:** Un nodo de `tipo_nodo: "campo"` es el único tipo de nodo que representa un dato real capturable — es la hoja final del árbol, nunca tiene hijos. Une tres cosas: cómo se llama y de qué tipo de dato es (para la UI), en qué celda exacta del Excel se ubica (heredando la hoja de su sección ancestro), y cuál es su valor actual.

✏️ **\[Corregido — se agregó ****`tipo_nodo`****, se convirtió ****`fila`**** a entero, y se agregó ****`abarca_filas`****\]**

```json
{
  "id": "1.01.01",
  "nombre": "Nivel de gobierno",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "captura": { "columna": "R", "fila": 9, "abarca_columnas": 10, "abarca_filas": 1 },
  "valor": "Regional (ejemplo)"
}
```

En este ejemplo: el nodo completo es un **campo** (`tipo_nodo: "campo"`), con `id` `"1.01.01"`, cuyo nombre visible es *"Nivel de gobierno"*. Este campo cuelga del grupo `"1.01"` ("Institucionalidad", visto en el punto 2), el cual a su vez cuelga de la sección `"1"` ("Datos generales del proyecto", vista en el punto 1). Al no declarar `hoja` propia, este campo hereda `"DATOS GENERALES"` de esa sección — y se ubica físicamente en la celda `R9` de esa pestaña, combinada a lo largo de 10 columnas.

| Propiedad | Descripción |
|---|---|
| `id` | Identificador jerárquico final, extiende el `id` de su grupo o sección padre. Debe ser único en todo el documento — otros campos (por ejemplo, campos calculados) lo van a referenciar directamente para tomar su valor. |
| `nombre` | Etiqueta del campo. Se renderiza como label en la UI del formulario, junto al control de entrada de datos. |
| `tipo_nodo` | Valor fijo `"campo"`. Marca que este nodo es una hoja del árbol: no tiene la propiedad `campos` ni ningún nodo hijo. |
| `tipo` | Tipo de dato del campo (en este ejemplo, `"texto_corto"`). Determina qué control se renderiza en la UI (input de texto, selector, número, etc.) y qué propiedades adicionales aplican al campo (se detalla en el bloque de tipos de dato, pendiente). |
| `editable` | Booleano. `true` si el usuario puede escribir el valor directamente desde la UI; `false` si el valor se genera automáticamente a partir de otros campos (fórmulas, concatenaciones) y por tanto se muestra de solo lectura. |
| `captura` | Objeto que define la ubicación física de este campo dentro de la hoja de Excel heredada de su sección ancestro. Es el puente entre el dato lógico y su posición real en el archivo Excel. **No lleva la propiedad ****`hoja`** — esa se declara únicamente en el nodo `seccion` (ver punto 1) y se hereda. 🔴 AQUÍ MODIFIQUE HOY 1/08/2026 a las 12:42 hrs |
| `captura.columna` | Letra de columna de Excel donde inicia el campo (en este ejemplo, `"R"`). |
| `captura.fila` | Número de fila de Excel donde inicia el campo, como valor entero — no como string — para permitir cálculos de offset y crecimiento en niveles posteriores (por ejemplo, tablas). En este ejemplo, `9`. |
| `captura.abarca_columnas` | Cantidad de columnas que ocupa la celda combinada del campo en el Excel, contando desde `columna`. En este ejemplo, `10` (el campo ocupa de la columna R a la columna AA). Si el campo no está combinado, el valor es `1`. |
| `captura.abarca_filas` | Cantidad de filas que ocupa la celda combinada del campo en el Excel, contando desde `fila`. En este ejemplo, `1` (no hay combinación vertical). |
| `valor` | Valor actual del campo. En un documento de `tipo_version: "estructura"` suele ir vacío o con un valor por defecto de referencia; en un documento de `tipo_version: "ejemplo"` contiene el dato real capturado, como en este ejemplo (`"Regional (ejemplo)"`). |

---

Como ya se explicó en detalle la estructura base del nodo `campo` en el punto 3 (id, tipo_nodo, editable, captura, valor), aquí solo marco **qué cambia o qué propiedad extra necesita cada tipo** — no repito lo que ya es común a todos.

### 3.1 Tipos de dato simples ✏️ *(renumerado para consistencia)*

| Tipo | Valor de `tipo` | ¿Propiedad extra? |
|---|---|---|
| Texto corto | `"texto_corto"` | Ninguna (ya documentado en punto 3) |
| Texto largo | `"texto_largo"` | `max_caracteres` (opcional) |
| Número | `"numero"` | ninguna extra — siempre entero |
| Decimal | `"decimal"` | `decimales` (cantidad de dígitos después de la coma) |
| Fecha | `"fecha"` | `formato_fecha` |
| Booleano | `"booleano"` | ninguna extra — el `valor` guarda la palabra que usa el Excel ("Sí"/"No") |
| Coordenadas | `"coordenadas"` | ninguna extra — `valor` es un objeto `{ lat, lng }` |

#### Ejemplos

**Texto largo**

```json
{
  "id": "2.03.01",
  "nombre": "Descripción del problema",
  "tipo_nodo": "campo",
  "tipo": "texto_largo",
  "editable": true,
  "max_caracteres": 2000,
  "captura": { "columna": "C", "fila": 45, "abarca_columnas": 8, "abarca_filas": 5 },
  "valor": ""
}
```

**Número**

```json
{
  "id": "3.02.04",
  "nombre": "Población beneficiaria",
  "tipo_nodo": "campo",
  "tipo": "numero",
  "editable": true,
  "captura": { "columna": "F", "fila": 60, "abarca_columnas": 3, "abarca_filas": 1 },
  "valor": 1250
}
```

**Decimal**

```json
{
  "id": "8.01.02",
  "nombre": "Costo total de inversión (S/)",
  "tipo_nodo": "campo",
  "tipo": "decimal",
  "editable": true,
  "decimales": 2,
  "captura": { "columna": "H", "fila": 12, "abarca_columnas": 4, "abarca_filas": 1 },
  "valor": 458300.75
}
```

**Fecha**

```json
{
  "id": "5.01.01",
  "nombre": "Fecha de inicio de horizonte de evaluación",
  "tipo_nodo": "campo",
  "tipo": "fecha",
  "editable": true,
  "formato_fecha": "DD/MM/YYYY",
  "captura": { "columna": "D", "fila": 30, "abarca_columnas": 3, "abarca_filas": 1 },
  "valor": "15/03/2026"
}
```

**Booleano**

```json
{
  "id": "12.01.03",
  "nombre": "¿Requiere certificación ambiental?",
  "tipo_nodo": "campo",
  "tipo": "booleano",
  "editable": true,
  "captura": { "columna": "K", "fila": 22, "abarca_columnas": 2, "abarca_filas": 1 },
  "valor": "Sí"
}
```

**Coordenadas**

```json
{
  "id": "2.01.01",
  "nombre": "Ubicación del proyecto",
  "tipo_nodo": "campo",
  "tipo": "coordenadas",
  "editable": true,
  "captura": { "columna": "R", "fila": 18, "abarca_columnas": 6, "abarca_filas": 1 },
  "valor": { "lat": -13.531950, "lng": -71.967463 }
}
```

### 3.2 Tipo calculado ✏️ *(AQUÍ MODIFIQUE HOY 2026-08-06 a las 08:47 hrs)*

**Si la celda del Excel ya trae su propia fórmula, no hace falta declarar nada.** El valor se obtiene evaluando esa fórmula con los datos que la estructura ya tiene, y se muestra en vivo mientras se llena la ficha. `formula` y `fuentes` quedan solo para los cálculos **propios** de la app, los que no existen en el Excel.

**Una celda con fórmula NUNCA se escribe.** Al insertar los datos se respeta lo que el Excel calcula: nada de pisar una fórmula con un número. Esto vale para toda celda del archivo que lleve fórmula, esté o no declarada como calculada en la estructura.

```json
{
  "id": "8.02.01",
  "nombre": "Costo unitario promedio (S/)",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "formula": "=(SUMA({8.01.02},{8.01.03}))/{3.02.04}",
  "fuentes": ["8.01.02", "8.01.03", "3.02.04"],
  "captura": { "columna": "H", "fila": 25, "abarca_columnas": 4, "abarca_filas": 1 },
  "valor": 409.92
}
```

| Propiedad | Descripción |
|---|---|
| `formula` | Fórmula en sintaxis de Excel, usando `{id}` como marcador de cada campo referenciado en vez de una celda directa (ej. `{8.01.02}` en vez de `H20`). El generador reemplaza cada `{id}` por la celda real de ese campo (resuelta vía su propio `captura`) al momento de escribir el archivo Excel. |
| `fuentes` | Array explícito de todos los `id` que aparecen dentro de `formula`. Aunque técnicamente se podrían extraer parseando el string de `formula`, mantenerlo como array separado sirve para: validar rápido que todas las referencias existen en el documento, y para que cualquier lógica de dependencias (por ejemplo, saber qué campos recalcular si cambia uno) no tenga que parsear texto. |

🔴 **AQUÍ MODIFIQUÉ HOY 2/08/2026 a las 12:01 hrs.** Cómo se comporta este tipo en el viaje de ida y vuelta al Excel — qué se protege en cada dirección y por qué — está en la sección **5. PROTECCIÓN DE CELDAS CALCULADAS**. Resumen: al **volcar** (Excel → JSON) un campo `calculado` se salta, conserva su `formula` y no el número cacheado; al **insertar** (JSON → Excel) su fórmula solo se escribe si la celda destino no trae ya una propia.

### 4. NIVEL CAMPO — Tipo Tabla

**Para qué sirve:** Es el cuarto tipo de nodo (`tipo: "tabla"`), usado cuando un conjunto de datos se repite en filas — a diferencia del campo simple, que captura un solo valor. Toda tabla comparte un esqueleto común de 6 propiedades, y luego se especializa según cómo crecen sus filas (`planas` o `jerarquicas`), si sus columnas son fijas o se generan dinámicamente, y si sus filas se organizan bajo agrupadores.

```json
{
  "id": "...",
  "nombre": "...",
  "tipo": "tabla",
  "editable": true,
  "config": { "filas": "planas|jerarquicas", "columnas": "fijas|dinamicas", "agrupador": true|false },
  "captura": { "fila_inicial": 0, "filas_base": 0, "columnas": [ ... ] },
  "cabecera": [ ... ],
  "columnas": [ ... ],
  "valor": [ ... ]
}
```

| Propiedad | Descripción |
|---|---|
| `config.filas` | Cómo se organizan las filas: `"planas"` (lista simple de registros) o `"jerarquicas"` (registros anidados en niveles padre-hijo). |
| `config.columnas` | Si las columnas son `"fijas"` (número conocido, declaradas una por una) o `"dinamicas"` (una columna se repite un número variable de veces, según datos externos como años o periodos). |
| `config.agrupador` | Booleano. Indica si las filas se organizan bajo encabezados de grupo intermedios (ej. "Durante la Ejecución"). |
| `config.agrupador_nivel` | 🔴 **AQUÍ MODIFIQUE HOY 1/08/2026 a las 22:57 hrs.** Solo en tablas **jerárquicas** con `agrupador: true`. Índice (base 0) del nivel del árbol que se dibuja como fila de título de grupo, y columna donde arranca ese título. Ver **4.5c**. En tablas planas no aplica: ahí el título siempre arranca en la primera columna. |
| `captura.fila_inicial` | Primera fila de Excel donde empieza el primer registro de datos (no la cabecera). |
| `captura.filas_base` | Cantidad de filas que ocupa la tabla en su estado base/ejemplo, antes de que el usuario agregue o quite registros. También es la referencia para calcular su crecimiento real (ver 4.7). |
| `captura.columnas` | Array que define la posición física de cada columna (`id`, `columna`, `abarca_columnas`). Es la única fuente de verdad para la ubicación — no se repite dentro de `columnas`. |
| `cabecera` | Array opcional que agrupa columnas bajo un título común de encabezado. Una columna que no aparece en ningún `hijos` de `cabecera` no tiene título padre, y ocupa verticalmente la misma altura que sus columnas vecinas. |
| `columnas` | Definición lógica de cada columna: `id`, `nombre`, `tipo`, y propiedades específicas del tipo. En tablas jerárquicas, este array se llama `niveles`. |
| `valor` | Los datos reales de la tabla. Su forma exacta depende de la combinación de `config`. |

### 4.1 Filas planas, columnas fijas, sin agrupador

**Para qué sirve:** La variante más simple — una lista de registros donde cada fila tiene el mismo conjunto de columnas, sin agrupaciones intermedias. `valor` es un array plano de objetos, uno por fila, con claves iguales al `id` de cada columna.

```json
{
  "id": "2.02",
  "nombre": "Localización del área de influencia del proyecto",
  "tipo": "tabla",
  "editable": true,
  "config": { "filas": "planas", "columnas": "fijas", "agrupador": false },
  "captura": {
    "fila_inicial": 18,
    "filas_base": 3,
    "columnas": [
      { "id": "n",       "columna": "B", "abarca_columnas": 1 },
      { "id": "depto",   "columna": "C", "abarca_columnas": 4 },
      { "id": "prov",    "columna": "G", "abarca_columnas": 4 },
      { "id": "dist",    "columna": "K", "abarca_columnas": 4 },
      { "id": "loc",     "columna": "O", "abarca_columnas": 4 },
      { "id": "ubigeo",  "columna": "S", "abarca_columnas": 4 }
    ]
  },
  "cabecera": [],
  "columnas": [
    { "id": "n",      "nombre": "N°",                      "tipo": "numero" },
    { "id": "depto",  "nombre": "Departamento",             "tipo": "texto_corto" },
    { "id": "prov",   "nombre": "Provincia",                "tipo": "texto_corto" },
    { "id": "dist",   "nombre": "Distrito",                 "tipo": "texto_corto" },
    { "id": "loc",    "nombre": "Localidad/Centro poblado",  "tipo": "texto_corto" },
    { "id": "ubigeo", "nombre": "Ubigeo",                   "tipo": "texto_corto" }
  ],
  "valor": [
    { "n": 1, "depto": "Cusco", "prov": "Cusco", "dist": "Wanchaq",  "loc": "Wanchaq",  "ubigeo": "3435" },
    { "n": 2, "depto": "Cusco", "prov": "Cusco", "dist": "Santiago", "loc": "Santiago", "ubigeo": "5235" },
    { "n": 3, "depto": "Puno",  "prov": "Puno",  "dist": "Juliaca",  "loc": "Juliaca",  "ubigeo": "6343" }
  ]
}
```

### 4.2 Filas planas, columnas fijas, con agrupador

**Para qué sirve:** Igual que la anterior, pero las filas se organizan bajo encabezados de grupo. `valor` pasa de ser un array plano a un array de bloques `{ agrupador, valores }`, donde cada bloque representa un grupo con sus filas dentro.

```json
{
  "id": "12.01",
  "nombre": "Matriz de impacto ambiental",
  "tipo": "tabla",
  "editable": true,
  "config": { "filas": "planas", "columnas": "fijas", "agrupador": true, "agrupador_abarca_columnas": 4 },
  "captura": {
    "fila_inicial": 8,
    "filas_base": 6,
    "columnas": [
      { "id": "imp_negativo",  "columna": "B", "abarca_columnas": 4 },
      { "id": "medidas_mitig", "columna": "F", "abarca_columnas": 3 },
      { "id": "costo",         "columna": "I", "abarca_columnas": 1 }
    ]
  },
  "cabecera": [],
  "columnas": [
    { "id": "imp_negativo",  "nombre": "IMPACTOS NEGATIVOS",     "tipo": "texto_corto" },
    { "id": "medidas_mitig", "nombre": "MEDIDAS DE MITIGACIÓN",  "tipo": "texto_corto" },
    { "id": "costo",         "nombre": "COSTO (S/)",             "tipo": "decimal" }
  ],
  "valor": [
    {
      "agrupador": { "inicia": "imp_negativo", "abarca_columnas": 4, "nombre": "Durante la Ejecución", "valores": {} },
      "valores": [
        { "imp_negativo": "Impacto 1", "medidas_mitig": "Medida A1", "costo": 232424 },
        { "imp_negativo": "Impacto 2", "medidas_mitig": "Medida A2", "costo": 532424 }
      ]
    },
    {
      "agrupador": { "inicia": "imp_negativo", "abarca_columnas": 4, "nombre": "Durante el Funcionamiento", "valores": {} },
      "valores": [
        { "imp_negativo": "Impacto 1", "medidas_mitig": "Medida B1", "costo": 232424 },
        { "imp_negativo": "Impacto 2", "medidas_mitig": "Medida B2", "costo": 532424 }
      ]
    }
  ]
}
```

| Propiedad | Descripción |
|---|---|
| `agrupador.inicia` | `id` de la columna donde empieza visualmente la fila de agrupación en el Excel. |
| `agrupador.abarca_columnas` | 🔴 **CAMBIÉ HOY 8/08/2026:** cantidad de **columnas reales de Excel** que ocupa el título, contando desde `inicia`. Antes contaba cabeceras. Es un dato **informativo**: quién manda de verdad es `config.agrupador_abarca_columnas` — al importar, de `agrupador` solo se leen `nombre` y `valores`. |
| `agrupador.nombre` | Texto del encabezado de grupo (ej. "Durante la Ejecución"). |
| `agrupador.valores` | Objeto vacío `{}` por defecto. Si el agrupador tuviera datos propios en otras columnas, se completa con `id_columna: valor`. 🔴 **AQUÍ MODIFIQUÉ HOY 2/08/2026 a las 10:39 hrs:** estaba documentado pero el **escritor de Excel no lo escribía** — el lector sí lo leía y el editor sí lo mostraba, así que el viaje de vuelta a Excel lo perdía en silencio. Ya es funcional. Caso real: la fila "Nivel de cobertura de la población demandante efectiva" de 7.03, que lleva un porcentaje por año en las columnas a la derecha de su título. |
| Bloque **sin** `agrupador.nombre` **ni** `agrupador.valores` | 🔴 **AQUÍ MODIFIQUÉ HOY 2/08/2026 a las 10:39 hrs — regla nueva.** No es "un grupo con el nombre en blanco": son **filas sueltas antes del primer grupo real**, y **no ocupan ninguna fila** del Excel. Reservarles una desalineaba la tabla entera y rompía la simetría con el lector, que nunca produce una fila de título vacía (reconoce los títulos por su fusión horizontal). El editor aplica el mismo criterio: a un bloque así no le dibuja fila de título. Ojo: un objeto `valores` con **todas** las celdas vacías cuenta como "sin valores propios" — es el molde de la estructura, no un dato. |

🔴 **AQUÍ MODIFIQUÉ HOY 8/08/2026 — un grupo puede tener ****`filas: []`****.** Es el caso complementario del anterior y cierra el par: un bloque **con** `agrupador.nombre` y **sin ninguna fila hija** es válido y ocupa **exactamente una fila** del Excel, la de su propio título, con sus datos en `agrupador.valores`. Antes se daba por hecho que todo grupo arrastraba al menos una fila, y eso obligaba a inventar una fila de cortesía que consumía una fila de más y empujaba hacia abajo todo lo que venía después en la hoja.

Resumen de las tres formas que puede tomar un bloque:

| Bloque | Filas de Excel que ocupa | Ejemplo |
|---|---|---|
| Sin `nombre` ni `valores`, con filas | Solo sus filas — **ninguna** de título | Filas sueltas antes del primer grupo real |
| Con `nombre`, con filas | 1 (título) + sus filas | Grupo normal |
| Con `nombre`, `filas: []` | **1** — solo la de su título | `4.01.02`, fila "Tasa de crecimiento de la población del área de influencia": título fusionado a la izquierda y sus valores a la derecha, sin nada colgando |

La aritmética de filas es **una sola** para las tres formas, y la comparten el editor y el escritor: si cada uno la calculara por su cuenta, la celda que se le enseña al usuario y la celda donde acaba el dato podrían desalinearse sin avisar.

➕ **\[Agregado — aclaración sobre ****`agrupador.abarca_columnas`**** vs. columnas físicas\]**

🔴 **REESCRITO HOY 8/08/2026 — esto cambió de significado.** Antes se contaban cabeceras; ahora se cuentan **columnas reales de Excel**.

El parámetro que manda es **`config.agrupador_abarca_columnas`**, y son columnas de Excel. El `abarca_columnas` que aparece dentro de `valor.agrupador` es solo informativo: el importador no lo lee.

**La fila de título siempre cubre el ancho completo de la tabla.** Ese número solo decide **dónde se corta el título**. Con `detalle` = B:E (4 columnas), `cantidad` = F, `costo` = G, `total` = H:

| Valor | Cómo queda la fila del agrupador |
|---|---|
| `4` | `[B:E` título`]` `[F]` `[G]` `[H]` |
| `2` | `[B:C` título`]` **`[D:E`**** resto****`]`** `[F]` `[G]` `[H]` |
| `1` | `[B` título`]` **`[C:E`**** resto****`]`** `[F]` `[G]` `[H]` |

Si el corte cae **dentro** de una cabecera, el sobrante NO queda suelto: se fusiona como una celda propia. Las cabeceras posteriores aportan **una celda completa cada una**, y son las que pueden llevar `agrupador.valores`.

Sin el parámetro declarado, el título abarca la tabla entera.

⚠️ **Al migrar estructuras antiguas** hay que recalcular: un `1` que significaba "1 cabecera" pasa a ser la **suma de los anchos físicos** de esa cabecera. En `3.07.01`, `1` se convirtió en `4`, porque `detalle` abarca B:E.

> En la UI se sigue eligiendo **por cabecera**, que es como se piensa la tabla, pero debajo se muestra siempre la traducción exacta — *"1 cabecera → 4 columnas de Excel — B:E"*.

### Cómo se VUELCA una tabla agrupada

🔴 **AQUÍ MODIFIQUÉ HOY 8/08/2026 — regla reescrita.** Al leer del Excel, **la forma de la tabla la declara la estructura; el archivo solo aporta valores.** Los grupos y sus filas salen del `valor` que ya tiene el campo, y la fila de cada parte se calcula con la misma aritmética que usan el escritor y el editor.

Antes se hacía al revés: la forma se deducía de las **fusiones**, dando por hecho que la fila de título fusionaba varias columnas y las de datos no. En el formato oficial pasa **justo lo contrario**:

| Celda (9.03, hoja CostosAlt1) | En el Excel |
|---|---|
| `J103`, `J115` — títulos de grupo | **sin fusionar** |
| `J104`, `J116` — filas de datos | fusionadas 4×1 |

El resultado era que tomaba las filas de datos por títulos y los títulos por datos, y la tabla entera salía desordenada. Además, con un agrupador de **una sola columna** esa detección no puede funcionar nunca: una celda de ancho 1 jamás está fusionada.

⚠️ **Consecuencia práctica:** el volcado de una tabla agrupada necesita que la estructura **ya declare sus grupos**. Si el campo no trae `valor` con la forma, no hay dónde depositar lo leído y la tabla se omite. En la práctica siempre lo trae, porque el molde de la plantilla los lleva.

Es el mismo criterio que ya seguían las **jerarquicas** (`rellenarArbol`): las agrupadas se habían quedado con el método viejo.

### 4.3 Filas planas, columnas dinámicas, sin agrupador

**Para qué sirve:** Se usa cuando una columna debe repetirse un número variable de veces (periodos, años), cuya cantidad no se conoce de antemano en la estructura. Esa columna declara `columnas_base` (la lista de encabezados a generar), y en `valor` cada fila guarda un array plano relacionado **por posición** con `columnas_base` — la única excepción a la regla de referenciar todo por id.

```json
{
  "id": "6.02",
  "nombre": "Análisis de la demanda del servicio",
  "tipo": "tabla",
  "editable": true,
  "config": { "filas": "planas", "columnas": "dinamicas", "agrupador": false },
  "captura": {
    "fila_inicial": 15,
    "filas_base": 4,
    "columnas": [
      { "id": "servicio",           "columna": "B", "abarca_columnas": 1 },
      { "id": "descripcion",        "columna": "C", "abarca_columnas": 2 },
      { "id": "u_medida",           "columna": "E", "abarca_columnas": 1 },
      { "id": "columnas_dinamicas", "columna": "F", "abarca_columnas": 1, "columnas_base": ["Año 1", "Año 2", "Año 3", "....", "....", "....", "....", "Año n"] }
    ]
  },
  "cabecera": [],
  "columnas": [
    { "id": "servicio",           "nombre": "Servicio",         "tipo": "texto_corto" },
    { "id": "descripcion",        "nombre": "Descripción",      "tipo": "texto_corto" },
    { "id": "u_medida",           "nombre": "Unidad de Medida", "tipo": "texto_corto" },
    { "id": "columnas_dinamicas", "nombre": "irrelevante",      "tipo": "numero" }
  ],
  "valor": [
    { "servicio": "Servicio 1", "descripcion": "Descripción 01", "u_medida": "metros", "columnas_dinamicas": [4, 5, 6, 7, 8, 5, 4, 0] },
    { "servicio": "Servicio 2", "descripcion": "Descripción 02", "u_medida": "unidad", "columnas_dinamicas": [4, 5, 6, 7, 8, 5, 4, 0] },
    { "servicio": "Servicio 3", "descripcion": "Descripción 03", "u_medida": "unidad", "columnas_dinamicas": [4, 5, 6, 7, 8, 5, 4, 0] }
  ]
}
```

| Propiedad | Descripción |
|---|---|
| `columnas_base` | Array de encabezados generados dinámicamente para esta columna, en orden. Cada elemento se convierte en una columna real del Excel al generarlo. |
| `nombre` (columna dinámica) | No se usa — cada columna generada trae su propio nombre desde `columnas_base`. Se declara solo por consistencia de esquema. |
| `valor.[fila].columnas_dinamicas` | Array plano de valores, relacionado por **posición** con `columnas_base`: el índice 0 corresponde al primer elemento, etc. |

### 4.4 Filas planas, columnas dinámicas, con agrupador

**Para qué sirve:** Combina las dos variantes anteriores — filas agrupadas bajo encabezados de grupo, con una columna que se repite dinámicamente dentro de cada grupo.

```json
{
  "id": "8.04.04",
  "nombre": "Cronograma de inversión de metas financieras",
  "tipo": "tabla",
  "editable": true,
  "config": { "filas": "planas", "columnas": "dinamicas", "agrupador": true },
  "captura": {
    "fila_inicial": 85,
    "filas_base": 13,
    "columnas": [
      { "id": "accion",                   "columna": "B", "abarca_columnas": 3 },
      { "id": "activos",                  "columna": "E", "abarca_columnas": 2 },
      { "id": "tipo_factor_productivo",   "columna": "G", "abarca_columnas": 2 },
      { "id": "columnas_dinamicas",       "columna": "I", "abarca_columnas": 1, "columnas_base": ["1", "2", "3", "....", "....", "....", "....", "n"] },
      { "id": "costo_estimado_inversion", "columna": "Q", "abarca_columnas": 2 }
    ]
  },
  "cabecera": [
    { "titulo": "Acción sobre los activos", "hijos": ["accion", "activos"] },
    { "titulo": "Cronograma de Inversión",  "hijos": ["columnas_dinamicas"] }
  ],
  "columnas": [
    { "id": "accion",                   "nombre": "Componente /acción",        "tipo": "texto_corto" },
    { "id": "activos",                  "nombre": "Activos",                   "tipo": "texto_corto" },
    { "id": "tipo_factor_productivo",   "nombre": "Tipo de factor productivo", "tipo": "texto_corto" },
    { "id": "columnas_dinamicas",       "nombre": "irrelevante",               "tipo": "decimal" },
    { "id": "costo_estimado_inversion", "nombre": "Costo estimado de inversión a precios de mercado (Soles)", "tipo": "decimal" }
  ],
  "valor": [
    {
      "agrupador": { "inicia": "accion", "abarca_columnas": 3, "nombre": "Componente 1", "valores": {} },
      "valores": [
        { "accion": "Acción 1", "activos": "Activo 1", "tipo_factor_productivo": "Obra", "columnas_dinamicas": [200000, 250000, 198000], "costo_estimado_inversion": 648000 },
        { "accion": "Acción 2", "activos": "Activo 2", "tipo_factor_productivo": "Obra", "columnas_dinamicas": [40000, 32000, 0], "costo_estimado_inversion": 72000 }
      ]
    },
    {
      "agrupador": { "inicia": "accion", "abarca_columnas": 3, "nombre": "Componente 2", "valores": {} },
      "valores": [
        { "accion": "Acción 1", "activos": "Activo 3", "tipo_factor_productivo": "Equipo", "columnas_dinamicas": [0, 45000, 0], "costo_estimado_inversion": 45000 }
      ]
    }
  ]
}
```

### 4.5 Filas jerárquicas, columnas fijas, sin agrupador

**Para qué sirve:** Se usa cuando los datos forman un árbol real de profundidad fija (causa directa → sustento → causa indirecta). `columnas` se reemplaza por `niveles`, y `valor` es una estructura recursiva anidada vía `hijos`.

```json
{
  "id": "4.01.02",
  "nombre": "",
  "tipo": "tabla",
  "editable": true,
  "config": { "filas": "jerarquicas", "columnas": "fijas", "agrupador": false },
  "captura": {
    "fila_inicial": 11,
    "filas_base": 9,
    "columnas": [
      { "id": "causa_directa",   "columna": "B", "abarca_columnas": 4 },
      { "id": "sustento",        "columna": "F", "abarca_columnas": 4 },
      { "id": "causa_indirecta", "columna": "J", "abarca_columnas": 4 }
    ]
  },
  "cabecera": [],
  "niveles": [
    { "id": "causa_directa",   "nombre": "Causa Directa (CD)",    "tipo": "texto_largo", "combina_vertical": true },
    { "id": "sustento",        "nombre": "Sustento (evidencias)", "tipo": "texto_largo", "combina_vertical": true },
    { "id": "causa_indirecta", "nombre": "Causa Indirecta (CI)",  "tipo": "texto_largo" }
  ],
  "valor": [
    {
      "causa_directa": "Inadecuadas condiciones de la infraestructura educativa",
      "hijos": [
        {
          "sustento": "El 60% de aulas presentan rajaduras (informe de defensa civil 2024)",
          "hijos": [
            { "causa_indirecta": "Aulas construidas con material precario" },
            { "causa_indirecta": "Ausencia de mantenimiento preventivo" }
          ]
        }
      ]
    },
    {
      "causa_directa": "Limitada disponibilidad de mobiliario y equipos",
      "hijos": [
        {
          "sustento": "Ratio de 2 alumnos por pupitre (censo escolar 2024)",
          "hijos": [
            { "causa_indirecta": "Mobiliario deteriorado y obsoleto" }
          ]
        }
      ]
    }
  ]
}
```

| Propiedad | Descripción |
|---|---|
| `niveles` | Reemplaza a `columnas` en tablas jerárquicas. Cada elemento define un nivel de profundidad del árbol, en el mismo orden en que aparecen las columnas físicas en el Excel. |
| `combina_vertical` | Booleano. Si es `true`, la celda de ese nivel se fusiona verticalmente cuando su valor se repite para varios hijos — es un efecto visual, no reduce la cantidad de filas físicas reales (ver 4.7). |
| `valor.[nodo].hijos` | Array recursivo: cada hijo es un objeto con la clave del siguiente nivel y, opcionalmente, su propio `hijos`. El último nivel del árbol no tiene `hijos`. |

🔴 **AQUÍ MODIFIQUE HOY 1/08/2026 a las 22:57 hrs** — esta nota decía que `filas jerárquicas + agrupador: true` no tenía ejemplo real y que el mecanismo esperado sería el mismo `agrupador`/`valores` de las variantes planas. **Resultó no ser así**: el agrupador jerárquico no es un bloque envolvente, sino un nivel del propio árbol. Ver **4.5c**.

### 4.5b Filas jerárquicas, columnas dinámicas

**Para qué sirve:** Combina el árbol de 4.5 con la columna que se repite dinámicamente de 4.3 — se usa cuando los datos forman una jerarquía de dos o más niveles, y uno de esos niveles necesita un valor por período en vez de un solo valor de texto. Reutiliza el mismo sentinel `"columnas_dinamicas"` de 4.3/4.4: **cualquier entrada de ****`niveles`**** puede ser la dinámica** con solo declarar `id: "columnas_dinamicas"` en vez de un id propio — no es una propiedad extra pegada a otro nivel, es un nivel del árbol como cualquier otro, salvo que su valor es un array en vez de texto. Por eso, cuando el dato real necesita tanto una etiqueta de texto ("OPERACIÓN") como sus valores por año, se modelan como **dos niveles separados y consecutivos**: uno de texto normal, seguido del nivel dinámico (que en ese caso siempre será hoja del árbol, sin `hijos` propios).

Ejemplo real: Formato 6-A, hoja COSTO TOTAL, sección 8.03 "Costos de operación y mantenimiento con y sin proyecto" — tres niveles (`escenario`: SIN PROYECTO / CON PROYECTO / INCREMENTAL, con celda combinada verticalmente sobre sus 2 hijos; `item`: OPERACIÓN / MANTENIMIENTO; y el nivel dinámico hoja, con un valor por año en columnas F a N del Excel).

```json
{
  "id": "8.03.03",
  "nombre": "Costos de operación y mantenimiento con y sin proyecto",
  "tipo": "tabla",
  "editable": true,
  "config": { "filas": "jerarquicas", "columnas": "dinamicas", "agrupador": false },
  "captura": {
    "fila_inicial": 66,
    "filas_base": 6,
    "columnas": [
      { "id": "escenario",          "columna": "B", "abarca_columnas": 2 },
      { "id": "item",               "columna": "D", "abarca_columnas": 2 },
      { "id": "columnas_dinamicas", "columna": "F", "abarca_columnas": 1, "columnas_base": ["1", "2", "3", "4", "5", "....", "....", "....", "n"] }
    ]
  },
  "cabecera": [],
  "niveles": [
    { "id": "escenario",          "nombre": "Escenario", "tipo": "texto_corto", "combina_vertical": true },
    { "id": "item",               "nombre": "Ítem",      "tipo": "texto_corto" },
    { "id": "columnas_dinamicas", "nombre": "irrelevante", "tipo": "decimal" }
  ],
  "valor": [
    {
      "escenario": "SIN PROYECTO",
      "hijos": [
        { "item": "OPERACIÓN",     "hijos": [ { "columnas_dinamicas": [12000, 12500, 13000, 13500, 14000, 14200, 14400, 14600, 15000] } ] },
        { "item": "MANTENIMIENTO", "hijos": [ { "columnas_dinamicas": [3000, 3100, 3200, 3300, 3400, 3450, 3500, 3550, 3600] } ] }
      ]
    },
    {
      "escenario": "CON PROYECTO",
      "hijos": [
        { "item": "OPERACIÓN",     "hijos": [ { "columnas_dinamicas": [18000, 18500, 19000, 19500, 20000, 20200, 20400, 20600, 21000] } ] },
        { "item": "MANTENIMIENTO", "hijos": [ { "columnas_dinamicas": [4500, 4600, 4700, 4800, 4900, 4950, 5000, 5050, 5100] } ] }
      ]
    },
    {
      "escenario": "INCREMENTAL",
      "hijos": [
        { "item": "OPERACIÓN",     "hijos": [ { "columnas_dinamicas": [6000, 6000, 6000, 6000, 6000, 6000, 6000, 6000, 6000] } ] },
        { "item": "MANTENIMIENTO", "hijos": [ { "columnas_dinamicas": [1500, 1500, 1500, 1500, 1500, 1500, 1500, 1500, 1500] } ] }
      ]
    }
  ]
}
```

| Propiedad | Descripción |
|---|---|
| `niveles[i].id = "columnas_dinamicas"` | Marca ese nivel del árbol como el dinámico — igual sentinel que 4.3/4.4. Puede ser cualquier profundidad del árbol, no solo la más externa ni obligatoriamente la hoja (aunque en la práctica, si necesita coexistir con una etiqueta de texto en el mismo "renglón" visual, ese texto vive en el nivel padre inmediato y el dinámico queda como su único hijo). |
| `captura.columnas[].columnas_base` (en la entrada dinámica) | Igual que en 4.3 — lista de encabezados generados dinámicamente para esa columna, en el mismo orden en que deben leerse los valores del array. |
| `valor.[nodo].columnas_dinamicas` | En los nodos de ese nivel, reemplaza por completo el valor de texto normal por un array plano relacionado por posición con `columnas_base` — misma convención que en las variantes planas (4.3/4.4), nunca coexiste con una etiqueta de texto en el mismo nodo. |

✅ *Implementado y verificado en el código (editor + escritor de Excel) el 2026-07-13, probado en 8.03 de la plantilla "faro".*

### 4.5c Filas jerárquicas, con agrupador

🔴 **AQUÍ MODIFIQUE HOY 1/08/2026 a las 22:57 hrs — sección nueva.**

**Para qué sirve:** Un árbol (4.5) en el que además hay **filas de título que agrupan a los hermanos** de un nivel. En el Excel se ven como una fila propia, sin celda a la izquierda, que abarca varias columnas — igual que el agrupador de las variantes planas, pero **dentro** de la jerarquía.

Caso real: Formato CIAI, hoja `Unidad Productora`, tabla 3.07.01 "a. Personal". La columna B lleva a la vez los títulos ("Actores comunales:", "Equipo técnico profesional del PNCM:") y los ítems que cuelgan de cada uno ("Madre cuidadora", "Madre guía"…), en filas consecutivas.

**Regla — el agrupador NO es un bloque envolvente, es un nivel del árbol.** A diferencia de 4.2 y 4.4, aquí `valor` **no** se parte en bloques `{ agrupador, valores }`. El árbol sigue siendo el mismo array recursivo de 4.5; lo único que se declara es **cuál de sus niveles se dibuja como fila de título** en vez de como celda fusionada a la izquierda. Eso se hace con `config.agrupador_nivel`.

**Regla — el nivel del agrupador NO consume una columna.** Un nodo de ese nivel ocupa su propia fila y sus hijos **siguen en la misma columna**, debajo. Por eso los `niveles` se declaran como en cualquier jerárquica: el agrupador no necesita un nivel propio en la lista. Declararle uno (una columna extra apuntando a la misma letra de Excel) es un error — duplica el dato y al volcar desde Excel el texto aparece repetido en dos niveles.

| Propiedad | Descripción |
|---|---|
| `config.agrupador` | `true` para activar la variante. |
| `config.agrupador_nivel` | Índice (base 0) del nivel del árbol que se dibuja como fila de título. `0` = los nodos raíz son los títulos; `1` = lo son los del segundo nivel, y así. Es también el índice de la **columna** donde arranca el título. |
| `config.agrupador_abarca_columnas` | Cantidad de **cabeceras/columnas** que fusiona la fila de título, contando desde la columna del agrupador (no desde la primera de la tabla, a diferencia de 4.2). Opcional: por defecto abarca hasta la última columna. Vale la misma aclaración de 4.2 — son cabeceras lógicas, no columnas físicas de Excel. Las columnas que quedan **libres a la derecha** del título admiten valores propios del grupo (ver más abajo). |

**Cómo se ve la profundidad del árbol.** Como el nivel del agrupador no gasta columna, el árbol tiene **un nivel más que columnas**. La correspondencia profundidad → columna es:

| Profundidad del nodo | Columna que le toca |
|---|---|
| menor o igual que `agrupador_nivel` | la misma (`profundidad`) |
| mayor que `agrupador_nivel` | `profundidad - 1` |

Por eso, en el ejemplo de abajo, la clave `detalle` aparece en **dos profundidades seguidas**: una es el título del grupo y la otra el ítem — ambas viven en la columna B del Excel, que es exactamente lo que hace el archivo real.

```json
{
  "id": "3.07.01",
  "nombre": "a. Personal",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "jerarquicas",
    "columnas": "fijas",
    "agrupador": true,
    "agrupador_nivel": 1
  },
  "captura": {
    "fila_inicial": 94,
    "filas_base": 11,
    "columnas": [
      { "id": "descripcion", "columna": "I", "abarca_columnas": 9 },
      { "id": "detalle",     "columna": "B", "abarca_columnas": 4 },
      { "id": "cantidad",    "columna": "F", "abarca_columnas": 1 },
      { "id": "costo",       "columna": "G", "abarca_columnas": 1 },
      { "id": "total",       "columna": "H", "abarca_columnas": 1 }
    ]
  },
  "cabecera": [
    { "titulo": "Costos anual", "hijos": ["cantidad", "costo", "total"] }
  ],
  "niveles": [
    { "id": "descripcion", "nombre": "Descripción de las prácticas de operación", "tipo": "texto_largo", "combina_vertical": true },
    { "id": "detalle",     "nombre": "Detalle",   "tipo": "texto_corto" },
    { "id": "cantidad",    "nombre": "Cantidad",  "tipo": "decimal" },
    { "id": "costo",       "nombre": "Costo",     "tipo": "decimal" },
    { "id": "total",       "nombre": "Total",     "tipo": "decimal" }
  ],
  "valor": [
    {
      "descripcion": "La gestión del CIAI está a cargo del comité de gestión…",
      "hijos": [
        {
          "detalle": "Actores comunales:",
          "hijos": [
            { "detalle": "Madre cuidadora", "hijos": [ { "cantidad": 2, "hijos": [ { "costo": 7800, "hijos": [ { "total": 15600 } ] } ] } ] },
            { "detalle": "Madre guía",      "hijos": [ { "cantidad": 6, "hijos": [ { "costo": 7800, "hijos": [ { "total": 46800 } ] } ] } ] }
          ]
        },
        {
          "detalle": "Equipo técnico profesional del PNCM:",
          "hijos": [
            { "detalle": "Acompañantes técnicos (AT)", "hijos": [ { "cantidad": 1, "hijos": [ { "costo": 54000, "hijos": [ { "total": 54000 } ] } ] } ] }
          ]
        }
      ]
    }
  ]
}
```

⚠️ **Limitación conocida al volcar desde Excel.** En el archivo oficial, la diferencia entre un título de grupo y un ítem no está codificada de forma legible por máquina: ambos comparten la columna B y el mismo tipo de fusión (`B94` y `B95` son las dos `1x4`). Lo único que los distingue es la **indentación / viñeta** del texto (`      Actores comunales:` vs `o   Madre cuidadora`). Por eso el volcado Excel → JSON de esta variante **no puede reconstruir el nivel de agrupación por sí solo** y devuelve los nodos aplanados en un mismo nivel. La escritura JSON → Excel sí es exacta.

**Valores propios de la fila de título.** 🔴 **AQUÍ MODIFIQUE HOY 1/08/2026 a las 23:12 hrs — antes figuraba como pendiente; ya está resuelto.** Cuando el título no abarca toda la tabla, las columnas que quedan **libres a su derecha** son editables y guardan los datos del grupo — es la fila-resumen. En el archivo real, la fila de "Actores comunales:" tiene `H94 = 262200` (el total del grupo).

A diferencia de las variantes planas, que los meten en `agrupador.valores` (4.2), aquí se emiten como **claves hermanas del título dentro del mismo nodo**, exactamente igual que las de una fila normal. No hace falta envoltorio:

```json
{
  "detalle": "Actores comunales:",
  "cantidad": 12,
  "total": 262200,
  "hijos": [
    { "detalle": "Madre cuidadora", "hijos": [ /* … */ ] }
  ]
}
```

Regla de lectura: dentro de un nodo, la clave que coincide con la columna de su propio nivel es **el título**; cualquier otra clave que coincida con una columna de la tabla son **valores propios de esa fila**. Solo tienen sentido en columnas libres — si `agrupador_abarca_columnas` cubre toda la tabla, no queda ninguna.

### 4.6 Caso especial — Valores seleccionables ✏️ *(AQUÍ MODIFIQUE HOY 2026-08-06 a las 08:47 hrs)*

**Para qué sirve:** documentar cómo se representa un valor que en el Excel se elige de una lista desplegable.

**La lista de opciones YA NO se declara en el JSON.** Se lee de la validación de datos del propio Excel, celda por celda. El Excel oficial es la única fuente de verdad: si el MEF publica una versión con otras opciones, se actualizan solas y no hay que migrar ninguna estructura.

Esto incluye las **listas dependientes**: cuando la validación es un `INDIRECT` que apunta a una celda calculada, se evalúa esa celda con los valores que la estructura ya tiene y de ahí sale el rango con las opciones. Así, elegir un valor en un campo cambia las opciones que ofrece otro, igual que en Excel.

**Lo único que va en el JSON es el dato.** Se guarda el texto exacto de la opción, tal como aparece en el Excel — incluidos tildes y guiones largos, que es justo lo que se pierde al teclearlo a mano.

```json
{
  "id": "11.02",
  "nombre": "Modalidad de ejecución de proyecto",
  "tipo": "tabla",
  "editable": true,
  "config": { "filas": "planas", "columnas": "fijas", "agrupador": false },
  "captura": {
    "fila_inicial": 41,
    "filas_base": 5,
    "columnas": [
      { "id": "tipo",   "columna": "B", "abarca_columnas": 5 },
      { "id": "marcar", "columna": "G", "abarca_columnas": 1 }
    ]
  },
  "cabecera": [],
  "columnas": [
    { "id": "tipo",   "nombre": "Tipo de ejecución", "tipo": "texto_corto" },
    { "id": "marcar", "nombre": "Marcar", "tipo": "booleano" }
  ],
  "valor": [
    { "tipo": "Administración directa", "marcar": "No" },
    { "tipo": "Administración indirecta – por contrata", "marcar": "Sí" },
    { "tipo": "Administración indirecta – Asociación Público Privado (APP)", "marcar": "No" },
    { "tipo": "Administración indirecta – Núcleo Ejecutor", "marcar": "No" },
    { "tipo": "Administración indirecta – Ley 29230 (Obras por Impuestos)", "marcar": "No" }
  ]
}
```

| Propiedad | Descripción |
|---|---|
| `etiquetas` / `opciones` | **Ya no se usan.** La lista sale de la validación de datos del Excel. Si aparecen en una estructura antigua, se ignoran. |
| `valor` de una columna con lista | El texto exacto de la opción elegida, tal cual figura en el Excel (ej. `"Sí"`, `"Administración indirecta – por contrata"`). Nunca `true`/`false` ni un índice. |

### 4.7 Crecimiento y desplazamiento de filas

**Para qué sirve:** Cuando el usuario agrega más registros a una tabla de los que tenía en su estado base (`filas_base`), todo lo que esté ubicado *después* de esa tabla en la misma hoja debe desplazarse hacia abajo. Esta sección define cómo se calcula ese crecimiento y cómo se propaga.

**Cálculo del crecimiento de una tabla:**

```plain text
crecimiento(tabla) = max(0, filas_reales - filas_base)
```

El desplazamiento nunca es negativo: si la tabla terminó con menos filas que su base, no se resta nada a lo que viene después.

`filas_reales` se calcula distinto según la variante:

| Variante | Cómo se cuenta `filas_reales` |
|---|---|
| Planas, sin agrupador | `valor.length` |
| Planas, con agrupador | Suma de: 1 fila por cada bloque de agrupador + `valores.length` de ese bloque, para todos los bloques |
| Jerárquicas | Cantidad total de nodos hoja (el último nivel del árbol, sin `hijos`) en todo `valor` — los niveles padre no suman filas propias, solo se fusionan sobre las filas que generan sus hijos finales |
| Columnas dinámicas | No afecta el conteo — el crecimiento dinámico es horizontal, se calcula igual que si no tuviera columnas dinámicas |

**Propagación del desplazamiento:**

```plain text
fila_efectiva(nodo) = fila_base(nodo) + Σ crecimiento(tabla)                       para toda tabla que esté antes que "nodo" en la misma hoja
```

**Regla de orden:** el orden de "antes/después" lo determina el orden en que los nodos aparecen dentro de `campos`, a través de todas las secciones que comparten `hoja`. Por eso ese orden debe reflejar fielmente la disposición física real del Excel, de arriba hacia abajo — si el JSON no respeta ese orden, el cálculo de desplazamiento se rompe silenciosamente.

### 4.8 Caso especial — Celdas partidas (`subcolumnas`)

🔴 **AQUÍ MODIFIQUE HOY 1/08/2026 a las 18:49 hrs — sección nueva.**

**Para qué sirve:** Hay columnas cuyo ancho fusionado guarda **dos datos distintos en celdas separadas** en unas filas, y **un solo dato fusionado** en otras. No es una tabla dentro de otra: es una misma columna lógica que, fila por fila, se parte o no se parte. Declararla como una sola celda (`abarca_columnas: 2`) hace que al leer el Excel se capture solo la primera parte y **se pierda silenciosamente la segunda**.

Caso real que motivó la convención: Formato CIAI, hoja `Involucrados`, tabla 4.01 "Descripción de la población afectada", columna `%` (Excel J:K):

| Fila Excel | Cómo está | Contenido |
|---|---|---|
| 8 — Población Total | J8:K8 **fusionada** | Un solo texto: "Población del distrito de San Sebastián, al año 2023. Fuente: INEI" |
| 9 a 12 — resto de poblaciones | J y K **separadas** | J = porcentaje (`0.0514` en la celda, se ve `5.1%`), K = texto ("de la población total") |
| 13 — Tasa de crecimiento | J13:K13 **fusionada** | (vacía) |

**Regla:** la partición se declara **a nivel de columna** (en `captura.columnas` y en `columnas`), nunca dentro de `valor`. `valor` sigue conteniendo únicamente datos, igual que en todas las demás variantes — así la posición se declara una sola vez y el crecimiento/desplazamiento de filas (4.7) sigue funcionando sin cambios.

⚠️ **Sobre el ****`0.0514`**** de la columna J.** Es lo que guarda la **celda de Excel**, no lo que acaba en el JSON. En la plantilla oficial `J9:J12` son **fórmulas** (`+IF(I8>0,I9/I8,"")`), así que por la regla del volcado nunca se vuelcan: el JSON deja esa parte vacía y el valor se ve calculado en vivo. Si esa misma celda fuera de teclear, el JSON guardaría `"5.1%"`, no la fracción — ver *Valores con formato de porcentaje*.

**Qué decide si una fila va partida o fusionada:** la **forma del valor** en esa fila.

| Forma de `valor[fila][id_columna]` | Resultado en Excel |
|---|---|
| Objeto (`{ "pct": ..., "texto": ... }`) | Fila **partida** — cada subcolumna se escribe/lee en su propia celda |
| String o número plano | Fila **fusionada** — un solo valor a lo ancho de `abarca_columnas` de la columna padre |

```json
{
  "id": "4.01.01",
  "nombre": "Descripción de la población afectada",
  "tipo": "tabla",
  "editable": true,
  "config": { "filas": "planas", "columnas": "fijas", "agrupador": false },
  "captura": {
    "fila_inicial": 8,
    "filas_base": 6,
    "columnas": [
      { "id": "tipo",        "columna": "B", "abarca_columnas": 2 },
      { "id": "descripcion", "columna": "D", "abarca_columnas": 4 },
      { "id": "unidad",      "columna": "H", "abarca_columnas": 1 },
      { "id": "cantidad",    "columna": "I", "abarca_columnas": 1 },
      { "id": "porcentaje",  "columna": "J", "abarca_columnas": 2,
        "subcolumnas": [
          { "id": "pct",   "columna": "J", "abarca_columnas": 1 },
          { "id": "texto", "columna": "K", "abarca_columnas": 1 }
        ]
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    { "id": "tipo",        "nombre": "Tipo de población", "tipo": "texto_corto" },
    { "id": "descripcion", "nombre": "Descripción",       "tipo": "texto_largo" },
    { "id": "unidad",      "nombre": "Unidad de medida",  "tipo": "texto_corto" },
    { "id": "cantidad",    "nombre": "Cantidad",          "tipo": "numero" },
    { "id": "porcentaje",  "nombre": "%",                 "tipo": "texto_largo",
      "subcolumnas": [
        { "id": "pct",   "nombre": "%",           "tipo": "decimal" },
        { "id": "texto", "nombre": "Referencia",  "tipo": "texto_corto" }
      ]
    }
  ],
  "valor": [
    { "tipo": "Población Total", "descripcion": "Es la población total del área de influencia...", "unidad": "Persona", "cantidad": 112536,
      "porcentaje": "Población del distrito de San Sebastián, al año 2023. Fuente: INEI" },
    { "tipo": "Población de Referencia", "descripcion": "Es la proporción de la población total...", "unidad": "Niños y niñas", "cantidad": 5788,
      "porcentaje": { "pct": 0.0514, "texto": "de la población total" } },
    { "tipo": "Población Demandante Potencial", "descripcion": "Es el segmento de la población de referencia...", "unidad": "Niños y niñas", "cantidad": 850,
      "porcentaje": { "pct": 0.1469, "texto": "de la población de referencia" } },
    { "tipo": "Población Demandante Efectiva", "descripcion": "Es el segmento de la población demandante potencial...", "unidad": "Niños y niñas", "cantidad": 87,
      "porcentaje": { "pct": 0.1024, "texto": "de la población demandante potencial" } },
    { "tipo": "Población Objetivo", "descripcion": "Es aquella parte de la población demandante efectiva...", "unidad": "Niños y niñas", "cantidad": 60,
      "porcentaje": { "pct": 0.6897, "texto": "de la población demandante efectiva" } },
    { "tipo": "Tasa de crecimiento de la población del área de influencia", "descripcion": "", "unidad": "Tasa", "cantidad": 0.011,
      "porcentaje": "" }
  ]
}
```

| Propiedad | Descripción |
|---|---|
| `captura.columnas[].subcolumnas` | Array con la posición física de cada parte: `id`, `columna`, `abarca_columnas`. La suma de sus anchos debe coincidir con el `abarca_columnas` de la columna padre — es el mismo espacio, solo que dividido. Presente solo en columnas que se parten. |
| `columnas[].subcolumnas` | Definición lógica de cada parte: `id` (debe coincidir con el de `captura`), `nombre` y `tipo` propios. Cada parte puede tener un tipo distinto (ej. `decimal` • `texto_corto`), que es justamente el motivo de partir la celda. |
| `columnas[].tipo` (columna padre) | Sigue declarándose — es el tipo que aplica cuando la fila va **fusionada** (valor plano). Los tipos de las partes solo aplican cuando la fila va partida. |
| `valor[fila][id_columna]` | Objeto con las claves de las subcolumnas ⇒ fila partida. String/número plano ⇒ fila fusionada. Es la única señal: no hay una bandera aparte por fila. |

**Qué NO se hizo, y por qué:** una alternativa considerada era que `valor` guardara un array de objetos, cada uno con su propio `tipo`, `valor`, `fila` y `columna` absolutas. Se descartó porque (a) mete la posición dentro de los datos, repitiéndola en cada fila cuando en el resto de la convención se declara una sola vez; (b) rompe el crecimiento de filas de 4.7 — una fila agregada por el usuario no tendría posición declarada, y un desplazamiento por una tabla que creció más arriba dejaría todas esas posiciones absolutas desfasadas; y (c) no expresa el caso fusionado (fila 8) sin una regla adicional.

> **Nota de implementación:** 🔴 **AQUÍ MODIFIQUÉ HOY 2/08/2026 a las 10:39 hrs — esta nota decía que la convención "todavía no está implementada". Ya no es cierto: está implementada de punta a punta** — editor (interruptor "Tiene subcolumnas" en el engranaje de la columna, y engranaje por celda para partir/fusionar), lector de Excel y escritor. Al escribir, una celda partida rompe la fusión que la plantilla trae por defecto; una fusionada vacía las columnas de las otras partes para no dejar restos ocultos bajo la fusión. Ver la página hija **INSTRUCCIONES DEL UI** para el detalle del comportamiento en el editor.

### 4.9 Caso especial — Fila de cabecera del Excel usada como fila de datos

🔴 **AQUÍ MODIFIQUÉ HOY 2/08/2026 a las 10:39 hrs — sección nueva.**

**Para qué sirve:** Hay tablas cuyo **bloque de cabecera contiene celdas que el usuario debe llenar**. El caso típico: las columnas se llaman "Año 0 … Año 10" y, justo debajo de esos rótulos, la plantilla deja una fila de casillas para escribir los **años concretos** (2022, 2023 …). Esa fila es visualmente parte de la cabecera, pero funcionalmente son datos.

No hace falta una variante de tabla nueva: basta con **hacer que la tabla empiece en esa fila**, de modo que sea la primera fila de `valor` como cualquier otra.

| Regla | Detalle |
|---|---|
| `fila_inicial` apunta a la fila de cabecera | No a la primera fila de datos "real". Las filas de cabecera que quedan por encima (los rótulos "Año 0…") no forman parte de la tabla. |
| Las columnas de etiqueta van **vacías** en esa fila | Ej. `detalle` y `unidad`: la plantilla oficial ya los trae impresos y con formato, así que volcarlos sería reescribir lo mismo. Y por la regla de "celda vacía no toca nada", quedan intactos. |
| Si la celda cae dentro de una **fusión** de la cabecera, dejarla vacía es **obligatorio** | En 7.02 la cabecera "Tipo de población" ocupa `B11:C13`, o sea que `B13` — la celda de esa columna en la fila de años — está **dentro** del rango fusionado. Escribir ahí corrompería la cabecera. |
| Los huecos en blanco también cuentan como filas | Si entre la cabecera y los datos la plantilla deja una fila vacía de separación, va en `valor` como una fila con todas sus celdas en `""`. Si no, todo lo de abajo queda corrido. |
| `filas_base` cuenta el bloque completo | Fila de cabecera + huecos + filas de datos. |

**Funciona en los dos sentidos.** Al escribir, los años caen en sus casillas y la cabecera queda intacta. Al leer, la celda de la columna de etiqueta devuelve vacío aunque esté dentro de una fusión, porque en OOXML una celda fusionada guarda su valor **solo en la esquina superior izquierda** del rango.

**Casos reales (hoja ****`Brecha`**** del formato CIAI):**

| Tabla | `fila_inicial` | `filas_base` | Composición |
|---|---|---|---|
| 7.02.01 | 13 | 7 | años (13) + hueco (14) + 5 tipos de población (15–19) |
| 7.03.01 | 27 | 5 | años (27) + hueco (28) + 2 datos (29–30) + grupo "Nivel de cobertura…" (31) |
| 7.05.01 | 47 | 3 | años (47) + hueco (48) + 1 dato (49) |
| 7.06.01 | 54 | 5 | años (54) + hueco (55) + 3 datos (56–58) |

```json
{
  "id": "7.06.01",
  "nombre": "Brecha del Servicio de Cuidado Diurno",
  "tipo": "tabla",
  "editable": true,
  "config": { "filas": "planas", "columnas": "fijas", "agrupador": false },
  "captura": {
    "fila_inicial": 54,
    "filas_base": 5,
    "columnas": [
      { "id": "detalle", "columna": "B", "abarca_columnas": 1 },
      { "id": "unidad",  "columna": "C", "abarca_columnas": 1 },
      { "id": "ano0",    "columna": "D", "abarca_columnas": 1 }
    ]
  },
  "cabecera": [],
  "columnas": [
    { "id": "detalle", "nombre": "Detalle",          "tipo": "texto_corto" },
    { "id": "unidad",  "nombre": "Unidad de Medida", "tipo": "texto_corto" },
    { "id": "ano0",    "nombre": "Año 0",            "tipo": "decimal" }
  ],
  "valor": [
    { "detalle": "", "unidad": "", "ano0": 2022 },
    { "detalle": "", "unidad": "", "ano0": "" },
    { "detalle": "Población Demandante Efectiva", "unidad": "Niños y niñas", "ano0": 0 },
    { "detalle": "Oferta", "unidad": "Niños y niñas", "ano0": 0 },
    { "detalle": "Brecha", "unidad": "Niños y niñas", "ano0": 0 }
  ]
}
```

⚠️ **Cuidado con ****`cabecera`****.** Es solo decoración para agrupar columnas bajo un título común en la UI; no tiene efecto en el Excel. Declararla cuando el archivo real **no** tiene ese título (7.03, 7.05 y 7.06 no tienen nada sobre las columnas de años) solo confunde la lectura de la estructura.

### 4.10 Filas que abarca una celda base — `config.abarca_filas`

🔴 **AQUÍ MODIFIQUÉ HOY 10/08/2026 — sección nueva.**

**Para qué sirve:** hasta ahora toda fila base de una tabla — una fila de `valor` en las variantes planas, o un nodo hoja (los hijos que NO son padres) en las jerárquicas — se asumía siempre de una sola fila física de Excel. Algunos formatos oficiales traen celdas base ya fusionadas verticalmente (una fila lógica que en el Excel real ocupa 2 o 3 filas para que el texto tenga espacio). `config.abarca_filas` declara explícitamente cuántas filas de Excel ocupa cada fila base de esa tabla.

| Regla | Detalle |
|---|---|
| Valor por defecto: `1` | Una fila base = una fila física de Excel — el comportamiento de siempre. Si el documento no trae `abarca_filas`, se asume `1`; no hace falta migrar los documentos ya guardados, cualquier lector debe caer a ese default. |
| Es un valor por TABLA, no por fila individual | Aplica de manera uniforme a todas las filas base de esa tabla. Si una misma tabla mezcla alturas distintas entre sus propias filas base, esto no las representa — usar el valor predominante y ajustar a mano el caso puntual, o avisar si esto se vuelve frecuente para evaluar una variante nueva. |
| No reemplaza a `combina_vertical` (4.5) | `combina_vertical` sigue siendo para los niveles PADRE de una tabla jerárquica (fusión visual de un valor que se repite entre hermanos, sin reducir filas físicas). `config.abarca_filas` es para las filas/celdas BASE — los hijos que no son padres en una jerárquica, o cualquier fila en una tabla plana. |
| Afecta el crecimiento de filas (4.7) | Cada fila base ahora "pesa" `abarca_filas` filas físicas en vez de 1 — el desplazamiento de las tablas siguientes en la misma hoja debe multiplicar por este factor. |

**En el editor:** aparece como un parámetro numérico junto a "Fila inicial (Excel)" y "Filas base" en el panel de propiedades del campo tabla (ver INSTRUCCIONES DEL UI).

Ejemplo — tabla plana cuyas filas base ocupan 2 filas de Excel cada una:

```json
{
  "id": "4.03.01",
  "nombre": "Descripción de Alternativas de Solución",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": { "filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 2 },
  "captura": {
    "fila_inicial": 74,
    "filas_base": 3,
    "columnas": [
      { "id": "descripcion", "columna": "B", "abarca_columnas": 6 }
    ]
  },
  "cabecera": [],
  "columnas": [
    { "id": "descripcion", "nombre": "Descripción de Alternativas de Solución", "tipo": "texto_largo" }
  ],
  "valor": [
    { "descripcion": "Alternativa 1: ..." },
    { "descripcion": "Alternativa 2: ..." },
    { "descripcion": "Alternativa 3: ..." }
  ]
}
```

Con `abarca_filas: 2`, la fila base 1 vive en B74:B75 (fusionada), la fila base 2 en B76:B77, la fila base 3 en B78:B79 — `filas_base: 3` sigue contando filas BASE, no filas físicas; la cantidad de filas físicas reales que ocupa la tabla es `filas_base × abarca_filas`.

## 5. PROTECCIÓN DE CELDAS CALCULADAS

🔴 **AQUÍ MODIFIQUÉ HOY 2/08/2026 a las 12:01 hrs — sección nueva.**

**Para qué sirve:** Las secciones 0–4 describen la **forma** del JSON. Esta describe una regla de **comportamiento** que vale para todo el documento, en las dos direcciones del viaje entre JSON y Excel.

### Regla — JSON → Excel

**Toda celda que ya traiga una fórmula en el libro es intocable.** No se le escribe nada, sin importar lo que diga el JSON y sin importar cómo esté declarado el campo en la estructura.

El motivo no es solo conservar el cálculo de esa celda: una fórmula suele alimentar a otras, muchas veces en **otras hojas**. Sobrescribirla corta la cadena completa aguas abajo.

No depende de que la estructura declare bien el campo — la comprobación se hace contra el archivo real, en el único punto por el que pasan **todas** las escrituras (campos simples, tablas, celdas partidas y filas de grupo). Cubre los dos casos de OOXML: la fórmula normal (`<f>texto</f>`) y las **compartidas**, donde solo la celda maestra lleva el texto y el resto del grupo trae `<f t="shared" si="N"/>` vacío; mirar solo el texto partiría esos grupos por la mitad.

No es silencioso: al insertar se registra cuántas celdas se omitieron y cuáles.

### Regla — Excel → JSON (volcado)

Un campo declarado `tipo: "calculado"` o `editable: false` **no se vuelca**: conserva su `formula`, no el número que Excel dejó cacheado en la celda. Sustituir la fórmula por su resultado la convertiría en un dato muerto.

🔴 **AQUÍ MODIFIQUÉ HOY 8/08/2026 — quién decide qué se vuelca.** La decisión ya no la toma el tipo declarado en el JSON, ni el archivo del que se leen los datos: la toma **la celda en el Excel asignado a la ficha**.

| En el Excel **asignado** | En el archivo que se vuelca | Qué pasa |
|---|---|---|
| La celda **tiene fórmula** | lo que sea | **No se vuelca.** Al insertar nunca escribimos ahí —la hoja lo recalcularía—, así que guardarlo dejaría en el JSON un número muerto que además puede acabar contradiciendo lo que el Excel calcula. |
| La celda es **de teclear** | valor escrito a mano | Se vuelca el valor. |
| La celda es **de teclear** | **fórmula** | Se vuelca su **RESULTADO**, nunca la fórmula. |

Con esto la lectura y la escritura miran por fin el mismo archivo: **el volcado trae exactamente el conjunto de celdas que la inserción es capaz de escribir.** Antes no coincidían — se protegían las fórmulas del asignado al escribir, pero al leer se miraban las del archivo de origen —, y por eso las diez fechas de `6.01.02` a `6.01.10` se descartaban en silencio: en la plantilla son casillas vacías, pero el anexo las traía resueltas con fórmulas encadenadas (`+K8+5`, `+I10+60`, `+K10+1`…).

Excepción, la de siempre: la **aritmética entre números literales** de la plantilla (`=1872+1927+1989`) sí se vuelca — ahí el resultado *es* el dato.

Si la celda del archivo de origen trae fórmula pero **sin resultado calculado** (archivos generados por herramientas que no evalúan), no hay nada que copiar: se ignora y se informa en el resumen del volcado.

### Valores con formato de porcentaje

🔴 **AQUÍ MODIFIQUÉ HOY 8/08/2026 — regla nueva.** Excel guarda un porcentaje como **fracción** (`0.011`) y el `%` lo pone el formato de la celda (`0.00%`, numFmtId 9 y 10, o cualquier código con `%`). En el JSON se guarda **lo que muestra la hoja**: `"1.10%"`.

Es el mismo criterio que con las fechas: en el JSON va la forma legible, y al insertar se reconvierte a lo que el Excel espera (`"1.10%"` → `0.011`), para que las fórmulas que leen esa celda sigan funcionando. Un `%` escrito a mano en cualquier celda se interpreta igual, que es exactamente lo que hace Excel cuando alguien teclea `50%`.

Caso real: `Involucrados!I13` (tasa de crecimiento, dentro de `4.01.02`). Antes el volcado guardaba `1.0999999999999999E-2` —la fracción cruda con el ruido de coma flotante—; ahora guarda `"1.10%"`.

No cuenta como porcentaje un `%` que sea **literal del formato** (`0.00" %"`), porque ahí Excel no multiplica por 100.

### Números hacia celdas con formato numérico

🔴 **AGREGADO HOY 8/08/2026.** Si la celda destino tiene un formato **explícitamente numérico** (declara dígitos con `0`/`#`: moneda, decimales, miles, porcentaje) y el valor del JSON es un número limpio, se escribe como **número**, no como texto.

Excel no aplica un formato numérico a una cadena: un `3650` escrito como texto en una celda de moneda se ve `3650` pelado, sin su `S/`, por más que el estilo de la celda siga intacto. Lo decide **la celda**, no el tipo declarado en la estructura — una columna puesta como `texto_corto` puede apuntar igualmente a una celda de moneda.

Con dos salvaguardas deliberadas, porque convertir a número a lo bruto destruye datos:

| Valor en el JSON | Se escribe como | Por qué |
|---|---|---|
| `3650`, `100.8` | **número** | se ve `S/ 3,650` |
| `08010` | texto | un ubigeo perdería el cero delante |
| `1,250` | texto | con separador de miles no se arriesga a malinterpretarlo |

Quedan fuera `General` y `Texto`, donde no hay formato que respetar.

### Por qué importa más de lo que parece

La plantilla oficial del CIAI (`1_plantilla_electronica.xlsx`) tiene **~2320 fórmulas nativas**. La hoja `Brecha` — toda la sección 7 — está **calculada de punta a punta**:

| Celda | Fórmula original | Qué significa |
|---|---|---|
| `D13` | `+IF(Horizonte!K8>0,Horizonte!K8,"")` | El primer año sale de la hoja Horizonte |
| `E13` | `+IF(Horizonte!$K$8>0,D13+365,"")` | Cada año siguiente = el anterior + 365 |
| `D15` | `+Involucrados!I8` | La población viene de la hoja Involucrados |
| `E15` | `+D15*(1+Involucrados!$I$13)` | Proyección por tasa de crecimiento |
| `D27`, `D47`, `D54` | `+D13`, `+D27`, `+D47` | Las filas de años de 7.03, 7.05 y 7.06 solo reflejan la de 7.02 |
| `D31` | `+IF(D29>0,D30/D29,"")` | Nivel de cobertura = objetivo / demandante |
| `D58` | `+D57-D56` | Brecha = oferta − demanda |

⚠️ **Consecuencia práctica.** Una tabla puede estar perfectamente modelada (posiciones correctas, ida y vuelta simétrica) y aun así **no escribirse nunca** en el Excel, porque sus celdas se calculan solas. Es el caso de las cinco tablas de la sección 7: sirven para **leer** del Excel, no para escribir en él. Modelar una tabla y que el volcado hacia Excel no haga nada **no es un error** — conviene comprobar primero si esas celdas llevan fórmula.

⚠️ **Nuestras propias fórmulas ceden.** Un campo `calculado` cuyo `valor` sea `=6.01.12+6.01.13` solo escribe su fórmula traducida si la celda destino está **libre**. Si el Excel ya trae la suya (`+I23+I25`, la misma cuenta escrita distinto), gana la del archivo oficial.

## 6. NODO `nota`

🔴 **AQUÍ MODIFIQUÉ HOY 11/08/2026 — sección nueva.**

**Para qué sirve:** un bloque de texto libre que el admin deja intercalado entre los campos de una subsección — instrucciones, comentarios, advertencias para quien llena la ficha. No es un campo real: no representa ningún dato de la ficha, y nunca se lee ni se escribe en el Excel.

Convive dentro del mismo array `campos[]` de un `grupo`, al lado de los nodos `tipo_nodo: "campo"` — el orden del array es el orden en que se renderiza en la UI, así que una nota puede ir intercalada exactamente donde el admin la necesite, no solo al final.

Forma — deliberadamente mínima, sin `id`, sin `nombre`, sin `tipo`, sin `editable`, sin `captura`:

```json
{ "tipo_nodo": "nota", "nota": "texto de la nota…" }
```

| Regla | Detalle |
|---|---|
| Sin identificador | No tiene `id` — no participa en la numeración `X.YY.ZZ` de sus hermanos: un campo real insertado antes o después de una nota nunca "salta" un número. |
| Sin captura | Nunca tiene columna/fila de Excel — el volcado y la inserción la ignoran por completo, no hace falta excluirla a mano. |
| Visible al cliente | Se muestra igual en Estructura, Ejemplos y en la ficha del cliente — siempre en solo lectura, con el mismo estilo (negrita, igual que la etiqueta de un campo normal) que el resto del contenido. |
| Se autora una sola vez | El texto lo escribe el admin desde Estructura; no es un valor de ejemplo — el mismo texto aparece en cualquier ejemplo o ficha de cliente, no varía caso a caso. |
| No cuenta como campo | Queda fuera de `cantidadCampos` de la sección. |

Comparar con `Subseccion.ayuda`: la ayuda de subsección vive detrás de un botón "?" y explica cómo llenar TODA la subsección de un tirón; una nota se ve directo en el flujo, en cualquier posición, y suele ser tan puntual como una frase junto a un campo específico.

---

✅ **Claude tiene acceso a este documento** (confirmado vía Notion MCP).
