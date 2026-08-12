# SECCIÓN N°02: DIAGNÓSTICO DEL TERRITORIO

## Descripción de la sección

- **Qué representa:** el diagnóstico del territorio donde se ubican la población afectada y la unidad productora (CIAI).
- **Objetivo (según instructivo):** analizar la información sobre las características y las variables referidas al ámbito geográfico en el que se ubica la población afectada y la unidad productora.
- **Qué información contiene:** localización del área de estudio y del área de influencia; macro y micro localización; características del distrito; identificación de peligros.
- **Para qué sirve dentro de la ficha:** contextualiza geográficamente el problema y aporta evidencia territorial para el resto del diagnóstico.

**Hoja Excel:** `Territorio`

**Subsecciones:** `2.01`, `2.02`, `2.03`, `2.04`, `2.05`

### Contenido del Diagnóstico del territorio (instructivo — Gráfico 4)

1. **2.01** Localización del área de estudio del proyecto
2. **2.02** Localización del área de influencia del proyecto
3. **2.03** Macro y micro localización del área de estudio
4. **2.04** Análisis de las características del distrito donde se ubica o ubicará el CIAI
5. **2.05** Identificar los peligros que pueden ocurrir en el área de estudio

**Regla de ejemplos de este documento:** todo bloque de ejemplo es el **objeto `campo` completo** tal como aparece en `JSON EJEMPLO.json` (incluye `id`, `nombre`, `tipo`, `editable`, `captura`, configuración de tabla si aplica, y `valor`). No son valores sueltos inventados.

---

# 2.01 Localización del área de estudio del proyecto

Según el instructivo, el **área de estudio** es la localización de la población afectada (niños y niñas de 6 a 36 meses que requieren el Servicio de Cuidado Diurno) y la localización del CIAI a intervenir.

Para la FTE se debe especificar: localidad/centro poblado, distrito(s), UBIGEO, provincia y departamento.

**Notas del JSON:**
- Nota: Considerar el espacio geográfico donde se localiza la población afectada y el área donde se ubica el CIAI

---

## Campo 2.01.01 — Localización del área de estudio del proyecto

**Tipo:** tabla

**Editable:** Sí

**Qué representa:** tabla de localización geográfica del área de estudio.

**Configuración:** `filas` planas, `columnas` fijas, `agrupador` false, `filas_base` 5.

### Estructura de columnas

| Columna (id) | Nombre | Tipo |
|---|---|---|
| `n` | N° | numero |
| `ubigeo` | Ubigeo | texto_corto |
| `depto` | Departamento | texto_corto |
| `prov` | Provincia | texto_corto |
| `dist` | Distrito | texto_corto |
| `localidad` | Localidad/Centro poblado | texto_corto |

**Regla de llenado:** completar las columnas geográficas de cada fila usada. No confundir con el área de influencia (`2.02.01`).

### Observación ESTRUCTURA vs EJEMPLO

En el JSON EJEMPLO, la fila 1 tiene `ubigeo` y `localidad` llenos; `depto`/`prov`/`dist` están vacíos. El instructivo muestra la fila completa.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "2.01.01",
  "nombre": "Localización del área de estudio del proyecto",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": false,
    "abarca_filas": 1
  },
  "captura": {
    "fila_inicial": 7,
    "filas_base": 5,
    "columnas": [
      {
        "id": "n",
        "columna": "B",
        "abarca_columnas": 1
      },
      {
        "id": "ubigeo",
        "columna": "C",
        "abarca_columnas": 4
      },
      {
        "id": "depto",
        "columna": "G",
        "abarca_columnas": 4
      },
      {
        "id": "prov",
        "columna": "K",
        "abarca_columnas": 4
      },
      {
        "id": "dist",
        "columna": "O",
        "abarca_columnas": 4
      },
      {
        "id": "localidad",
        "columna": "S",
        "abarca_columnas": 4
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    {
      "id": "n",
      "nombre": "N°",
      "tipo": "numero"
    },
    {
      "id": "ubigeo",
      "nombre": "Ubigeo",
      "tipo": "texto_corto"
    },
    {
      "id": "depto",
      "nombre": "Departamento",
      "tipo": "texto_corto"
    },
    {
      "id": "prov",
      "nombre": "Provincia",
      "tipo": "texto_corto"
    },
    {
      "id": "dist",
      "nombre": "Distrito",
      "tipo": "texto_corto"
    },
    {
      "id": "localidad",
      "nombre": "Localidad/Centro poblado",
      "tipo": "texto_corto"
    }
  ],
  "valor": [
    {
      "n": 1,
      "ubigeo": "080105",
      "depto": "",
      "prov": "",
      "dist": "",
      "localidad": "San Antonio"
    },
    {
      "n": 2,
      "ubigeo": "",
      "depto": "",
      "prov": "",
      "dist": "",
      "localidad": ""
    },
    {
      "n": 3,
      "ubigeo": "",
      "depto": "",
      "prov": "",
      "dist": "",
      "localidad": ""
    },
    {
      "n": 4,
      "ubigeo": "",
      "depto": "",
      "prov": "",
      "dist": "",
      "localidad": ""
    },
    {
      "n": 5,
      "ubigeo": "",
      "depto": "",
      "prov": "",
      "dist": "",
      "localidad": ""
    }
  ]
}
```

---

# 2.02 Localización del área de influencia del proyecto

Según el instructivo, delimitar el **área de influencia** en base a la ubicación del CIAI (existente o proyectada).

### Diferencia importante

- **Área de estudio (`2.01`)** = población afectada + ubicación del CIAI a intervenir.
- **Área de influencia (`2.02`)** = ámbito de influencia del proyecto a partir del CIAI.

Misma estructura de columnas que `2.01.01`, distinto significado.

---

## Campo 2.02.01 — Localización del área de influencia del proyecto

**Tipo:** tabla

**Editable:** Sí

**Configuración:** igual a `2.01.01` (`filas_base` 5).

### Estructura de columnas

| Columna (id) | Nombre | Tipo |
|---|---|---|
| `n` | N° | numero |
| `ubigeo` | Ubigeo | texto_corto |
| `depto` | Departamento | texto_corto |
| `prov` | Provincia | texto_corto |
| `dist` | Distrito | texto_corto |
| `localidad` | Localidad/Centro poblado | texto_corto |

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "2.02.01",
  "nombre": "Localización del área de influencia del proyecto",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": false,
    "abarca_filas": 1
  },
  "captura": {
    "fila_inicial": 18,
    "filas_base": 5,
    "columnas": [
      {
        "id": "n",
        "columna": "B",
        "abarca_columnas": 1
      },
      {
        "id": "ubigeo",
        "columna": "C",
        "abarca_columnas": 4
      },
      {
        "id": "depto",
        "columna": "G",
        "abarca_columnas": 4
      },
      {
        "id": "prov",
        "columna": "K",
        "abarca_columnas": 4
      },
      {
        "id": "dist",
        "columna": "O",
        "abarca_columnas": 4
      },
      {
        "id": "localidad",
        "columna": "S",
        "abarca_columnas": 4
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    {
      "id": "n",
      "nombre": "N°",
      "tipo": "numero"
    },
    {
      "id": "ubigeo",
      "nombre": "Ubigeo",
      "tipo": "texto_corto"
    },
    {
      "id": "depto",
      "nombre": "Departamento",
      "tipo": "texto_corto"
    },
    {
      "id": "prov",
      "nombre": "Provincia",
      "tipo": "texto_corto"
    },
    {
      "id": "dist",
      "nombre": "Distrito",
      "tipo": "texto_corto"
    },
    {
      "id": "localidad",
      "nombre": "Localidad/Centro poblado",
      "tipo": "texto_corto"
    }
  ],
  "valor": [
    {
      "n": 1,
      "ubigeo": "080105",
      "depto": "",
      "prov": "",
      "dist": "",
      "localidad": "San Antonio"
    },
    {
      "n": 2,
      "ubigeo": "",
      "depto": "",
      "prov": "",
      "dist": "",
      "localidad": ""
    },
    {
      "n": 3,
      "ubigeo": "",
      "depto": "",
      "prov": "",
      "dist": "",
      "localidad": ""
    },
    {
      "n": 4,
      "ubigeo": "",
      "depto": "",
      "prov": "",
      "dist": "",
      "localidad": ""
    },
    {
      "n": 5,
      "ubigeo": "",
      "depto": "",
      "prov": "",
      "dist": "",
      "localidad": ""
    }
  ]
}
```

---

# 2.03 Macro y micro localización del área de estudio

Presenta visualmente la ubicación del CIAI:

- **Macrolocalización:** departamento, provincia y distrito.
- **Microlocalización:** entorno inmediato / detalle del sitio.

Las leyendas (`2.03.02`, `2.03.04`) son **calculadas** → no llenar manualmente.

**Notas del JSON:**
- Nota: Para la macrolocalización, puede utilizar la fuente: https://estadist.inei.gob.pe/map
https://www.cunamas.gob.pe/inicio/cobertura-de-servicios/
- Nota: Para la microlocalización, puede utilizar las fuentes: https://earth.google.com/web/
https://www.cunamas.gob.pe/inicio/cobertura-de-servicios/

---

## Campo 2.03.01 — Mapa de macrolocalización

**Tipo:** imagen

**Editable:** Sí

**Qué representa:** imagen del mapa de macrolocalización.

**Regla de llenado:** campo imagen, no texto. Fuentes sugeridas en notas del JSON (INEI map / cobertura Cuna Más).

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "2.03.01",
  "nombre": "Mapa de macrolocalización",
  "tipo_nodo": "campo",
  "tipo": "imagen",
  "editable": true,
  "captura": {
    "columna": "B",
    "fila": 28,
    "abarca_columnas": 16,
    "abarca_filas": 1
  },
  "valor": ""
}
```

---

## Campo 2.03.02 — Leyenda del mapa de macrolocalización

**Tipo:** calculado

**Editable:** No

**Regla:** no tratar como captura manual.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "2.03.02",
  "nombre": "Leyenda del mapa de macrolocalización",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "R",
    "fila": 28,
    "abarca_columnas": 5,
    "abarca_filas": 1
  },
  "valor": "Leyenda\n\nMapa de macrolocalización del CIAI de la localidad/centro poblado de San Antonio, del distrito de San Sebastian, provincia de Cusco, departamento de Cusco"
}
```

---

## Campo 2.03.03 — Mapa de microlocalización

**Tipo:** imagen

**Editable:** Sí

**Regla de llenado:** campo imagen. Fuentes sugeridas: Google Earth / cobertura Cuna Más.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "2.03.03",
  "nombre": "Mapa de microlocalización",
  "tipo_nodo": "campo",
  "tipo": "imagen",
  "editable": true,
  "captura": {
    "columna": "B",
    "fila": 32,
    "abarca_columnas": 16,
    "abarca_filas": 1
  },
  "valor": "https://res.cloudinary.com/dbtb20o97/image/upload/v1786452248/proyecta-facil/imagenes/2.03.03_evwyhg.png"
}
```

---

## Campo 2.03.04 — Leyenda del mapa de microlocalización

**Tipo:** calculado

**Editable:** No

**Regla:** no llenar manualmente.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "2.03.04",
  "nombre": "Leyenda del mapa de microlocalización",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "R",
    "fila": 32,
    "abarca_columnas": 5,
    "abarca_filas": 1
  },
  "valor": "Leyenda\n\nMapa de microlocalización del CIAI de la localidad/centro poblado de San Antonio, del distrito de San Sebastian, provincia de Cusco, departamento de Cusco"
}
```

---

## Campo 2.03.05 — Fuente de información

**Tipo:** texto_corto

**Editable:** Sí

**Qué debe contener:** referencia a la fuente usada para macro/micro localización.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "2.03.05",
  "nombre": "Fuente de información",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "captura": {
    "columna": "G",
    "fila": 33,
    "abarca_columnas": 11,
    "abarca_filas": 1
  },
  "valor": ""
}
```

---

# 2.04 Análisis de las características del distrito donde se ubica o ubicará el CIAI

Bloques (notas del JSON / instructivo):

- **A.** Datos generales del distrito (`2.04.1` … `2.04.05`) — fuente referida: SENAMHI
- **B.** Accesibilidad, pobreza y características sociales/económicas (`2.04.06` … `2.04.08`) — planes de desarrollo locales/regionales
- **C.** Acceso a servicios públicos (`2.04.09`) — fuente referida: INEI

**Notas del JSON:**
- A. Datos generales del distrito
- B. Características de: i) accesibilidad al distrito; ii) condiciones de pobreza; y, iii) principales características sociales y económicas
- Fuente: https://censos2017.inei.gob.pe/redatam/

---

## Campo 2.04.1 — Altitud

**Tipo:** texto_corto

**Editable:** Sí

**Nota de ID:** el identificador es `2.04.1` (no `2.04.01`).

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "2.04.1",
  "nombre": "Altitud",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "captura": {
    "columna": "B",
    "fila": 42,
    "abarca_columnas": 3,
    "abarca_filas": 1
  },
  "valor": "3295"
}
```

---

## Campo 2.04.02 — Temperatura media anual

**Tipo:** texto_corto

**Editable:** Sí

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "2.04.02",
  "nombre": "Temperatura media anual",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "captura": {
    "columna": "E",
    "fila": 42,
    "abarca_columnas": 4,
    "abarca_filas": 1
  },
  "valor": "12° C"
}
```

---

## Campo 2.04.03 — Humedad

**Tipo:** texto_corto

**Editable:** Sí

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "2.04.03",
  "nombre": "Humedad",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "captura": {
    "columna": "I",
    "fila": 42,
    "abarca_columnas": 3,
    "abarca_filas": 1
  },
  "valor": "0%"
}
```

---

## Campo 2.04.04 — Precipitación media anual

**Tipo:** texto_corto

**Editable:** Sí

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "2.04.04",
  "nombre": "Precipitación media anual",
  "tipo_nodo": "campo",
  "tipo": "texto_corto",
  "editable": true,
  "captura": {
    "columna": "L",
    "fila": 42,
    "abarca_columnas": 3,
    "abarca_filas": 1
  },
  "valor": "3 mm"
}
```

---

## Campo 2.04.05 — Coordenadas geográficas en decimales Latitud y Longitud

**Tipo:** coordenadas

**Editable:** Sí

**Qué debe contener:** objeto con `lat` y `lng` (números). No convertir a texto concatenado.

- `lat`: latitud en grados decimales
- `lng`: longitud en grados decimales

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "2.04.05",
  "nombre": "Coordenadas geográficas en decimales Latitud y Longitud",
  "tipo_nodo": "campo",
  "tipo": "coordenadas",
  "editable": true,
  "captura": {
    "columna": "O",
    "fila": 42,
    "abarca_columnas": 8,
    "abarca_filas": 1
  },
  "valor": {
    "lat": -13.5407619,
    "lng": -71.923069
  }
}
```

---

## Campo 2.04.06 — Accesibilidad

**Tipo:** texto_largo

**Editable:** Sí

**Qué representa:** descripción de la accesibilidad al distrito / área.

### Diferencia importante

No mezclar con `2.04.07` (pobreza) ni `2.04.08` (características sociales y económicas).

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "2.04.06",
  "nombre": "Accesibilidad",
  "tipo_nodo": "campo",
  "tipo": "texto_largo",
  "editable": true,
  "captura": {
    "columna": "B",
    "fila": 47,
    "abarca_columnas": 21,
    "abarca_filas": 1
  },
  "valor": "\nEl territorio de este distrito se extiende en 89,44 kilómetros cuadrados y se encuentra dentro del conurbano de la ciudad de Cuzco. Se encuentra localizado a 13º 31’ 49” Latitud Sur y 71º 56’ 14” Longitud Oeste. Se encuentra a 15 mitutos del centro de la ciudad de Cusco. Las vías de acceso son asfaltadas, se encuentran en buen estado.\"\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t"
}
```

---

## Campo 2.04.07 — Condiciones de pobreza

**Tipo:** texto_largo

**Editable:** Sí

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "2.04.07",
  "nombre": "Condiciones de pobreza",
  "tipo_nodo": "campo",
  "tipo": "texto_largo",
  "editable": true,
  "captura": {
    "columna": "B",
    "fila": 49,
    "abarca_columnas": 21,
    "abarca_filas": 1
  },
  "valor": "\nEn el distrito aún existe una exclusión social que genera pobreza monetaria en la población principalmente del área rural y zonas periurbanas de expansión, donde la población tiene carencias, ello está condicionado a las limitadas oportunidades de desarrollo que brindan las autoridades, como la calidad educativa que se brinda, el nivel educativo que tiene la población, limitadas oportunidades laborales, servicios de salud integrales que garanticen la esperanza de vida. El distrito de San Sebastián alcanza al año 2019 un Índice de Desarrollo Humano IDH de 0,6806, lo que significa que el nivel de desarrollo humano en el distrito se encuentra en nivel medio.\nFuente: Plan de Desarrollo Concertado MD San Sebastián al 2033"
}
```

---

## Campo 2.04.08 — Principales características sociales y económicas

**Tipo:** texto_largo

**Editable:** Sí

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "2.04.08",
  "nombre": "Principales características sociales y económicas",
  "tipo_nodo": "campo",
  "tipo": "texto_largo",
  "editable": true,
  "captura": {
    "columna": "B",
    "fila": 51,
    "abarca_columnas": 21,
    "abarca_filas": 1
  },
  "valor": "\nLa vulnerabilidad de los derechos de los niños, niñas y adolescentes es un problema que podrían deberse a las limitadas acciones la disfunción familiar, entorno familiar desfavorable y el limitado sistema de protección de las niñas, niños y adolescentes por las instancias que velan por sus derechos esta es ocasionado por los propios padres por múltiples factores como discusiones, disfunciones; así como por personas extrañas que violenta, vulneran sus derechos y dañan su integridad afectando el desarrollo integral, socioemocional y su desarrollo en el futuro como ciudadano, así mismo las instancias que velan por sus derechos no protegen adecuadamente permitiendo la persistencia e incremento de la vulneración de sus derechos. para su reducción se debe promover acciones multisectoriales desde la educación, salud, seguridad y fortalecer las instancias que velan por los derechos de los niños, niñas y adolescentes.\nFuente: Plan de Desarrollo Concertado MD San Sebastián al 2033"
}
```

---

## Campo 2.04.09 — C. Acceso a servicios públicos en el distrito

**Tipo:** tabla

**Editable:** Sí

**Configuración:** `filas` planas, `columnas` fijas, `agrupador` false, `filas_base` 4.

Las 4 filas traen precargado el nombre del `servicio`.

### Estructura de columnas

| Columna (id) | Nombre | Tipo |
|---|---|---|
| `servicio` | Servicio público | texto_corto |
| `porcentaje` | Porcentaje de viviendas con acceso | decimal |
| `anio` | Año de información | numero |
| `fuente` | Fuente de información (incluir enlace) | texto_corto |

**Fuente referida (nota JSON / instructivo):** `https://censos2017.inei.gob.pe/redatam/`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "2.04.09",
  "nombre": "C. Acceso a servicios públicos en el distrito",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": false,
    "abarca_filas": 1
  },
  "captura": {
    "fila_inicial": 55,
    "filas_base": 4,
    "columnas": [
      {
        "id": "servicio",
        "columna": "B",
        "abarca_columnas": 6
      },
      {
        "id": "porcentaje",
        "columna": "H",
        "abarca_columnas": 4
      },
      {
        "id": "anio",
        "columna": "L",
        "abarca_columnas": 3
      },
      {
        "id": "fuente",
        "columna": "O",
        "abarca_columnas": 8
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    {
      "id": "servicio",
      "nombre": "Servicio público",
      "tipo": "texto_corto"
    },
    {
      "id": "porcentaje",
      "nombre": "Porcentaje de viviendas con acceso",
      "tipo": "decimal"
    },
    {
      "id": "anio",
      "nombre": "Año de información",
      "tipo": "numero"
    },
    {
      "id": "fuente",
      "nombre": "Fuente de información (incluir enlace)",
      "tipo": "texto_corto"
    }
  ],
  "valor": [
    {
      "servicio": "Abastecimiento de agua por red pública dentro de la vivienda",
      "porcentaje": 78.23,
      "anio": 2017,
      "fuente": "INEI"
    },
    {
      "servicio": "Servicio higiénico conectado a red pública de desagüe dentro de la vivienda",
      "porcentaje": 76.75,
      "anio": 2017,
      "fuente": "INEI"
    },
    {
      "servicio": "Cuenta con alumbrado eléctrico conectado a una red pública",
      "porcentaje": 96.41,
      "anio": 2017,
      "fuente": "INEI"
    },
    {
      "servicio": "Cuenta con servicio de internet",
      "porcentaje": 36.9,
      "anio": 2017,
      "fuente": "INEI"
    }
  ]
}
```

---

# 2.05 Identificar los peligros que pueden ocurrir en el área de estudio

Identifica peligros naturales y antrópicos con antecedentes de ocurrencia y posibles cambios futuros.

**Fuente referida:** `https://sigrid.cenepred.gob.pe/sigridv3/`

La tabla tiene **cabeceras agrupadas** (`cabecera`).

### Jerarquía de cabecera

```
Peligros
├── ¿Existen antecedentes de ocurrencia en el área de estudio?
│   ├── Sí / No                  (antecedentes_sino)
│   └── Características …        (antecedentes_caract)
└── ¿Existe información que indique futuros cambios…?
    ├── Sí/No                    (cambios_sino)
    └── Características de los cambios… (cambios_caract)
```

**Notas del JSON:**
- Fuente: https://sigrid.cenepred.gob.pe/sigridv3/

---

## Campo 2.05.01 — Identificar los peligros que pueden ocurrir en el área de estudio

**Tipo:** tabla

**Editable:** Sí

**Configuración:** `filas` planas, `columnas` fijas, `agrupador` false, `filas_base` 16.

### Estructura de columnas

| Columna (id) | Nombre | Tipo |
|---|---|---|
| `peligro` | Peligros | texto_corto |
| `antecedentes_sino` | Sí / No | booleano (`true`=`Sí`, `false`=`No`) |
| `antecedentes_caract` | Características (Intensidad, frecuencia, área de impacto, otros) | texto_largo |
| `cambios_sino` | Sí/No | booleano |
| `cambios_caract` | Características de los cambios o los nuevos peligros | texto_largo |

**Peligros precargados:** Inundaciones; Movimientos en masa; Lluvias intensas; Helada; Nevadas; Friaje; Sismos; Sequías; Vulcanismo; Tsunamis; Incendios forestales; Erosión; Vientos fuertes; Incendios urbanos; Radiación solar; Otros.

### Observación ESTRUCTURA vs EJEMPLO (booleanos)

- Schema: `tipo: booleano` con etiquetas Sí/No.
- JSON EJEMPLO: `antecedentes_sino` aparece como strings `"Sí"`/`"No"`; `cambios_sino` queda `""` en las filas del ejemplo.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "2.05.01",
  "nombre": "Identificar los peligros que pueden ocurrir en el área de estudio",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": false,
    "abarca_filas": 1
  },
  "captura": {
    "fila_inicial": 66,
    "filas_base": 16,
    "columnas": [
      {
        "id": "peligro",
        "columna": "B",
        "abarca_columnas": 5
      },
      {
        "id": "antecedentes_sino",
        "columna": "G",
        "abarca_columnas": 1
      },
      {
        "id": "antecedentes_caract",
        "columna": "H",
        "abarca_columnas": 7
      },
      {
        "id": "cambios_sino",
        "columna": "O",
        "abarca_columnas": 1
      },
      {
        "id": "cambios_caract",
        "columna": "P",
        "abarca_columnas": 7
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "¿Existen antecedentes de ocurrencia en el área de estudio?",
      "hijos": [
        "antecedentes_sino",
        "antecedentes_caract"
      ]
    },
    {
      "titulo": "¿Existe información que indique futuros cambios en las características del peligro o los nuevos peligros?",
      "hijos": [
        "cambios_sino",
        "cambios_caract"
      ]
    }
  ],
  "columnas": [
    {
      "id": "peligro",
      "nombre": "Peligros",
      "tipo": "texto_corto"
    },
    {
      "id": "antecedentes_sino",
      "nombre": "Sí / No",
      "tipo": "booleano",
      "etiquetas": {
        "true": "Sí",
        "false": "No"
      }
    },
    {
      "id": "antecedentes_caract",
      "nombre": "Características (Intensidad, frecuencia, área de impacto, otros)",
      "tipo": "texto_largo"
    },
    {
      "id": "cambios_sino",
      "nombre": "Sí/No",
      "tipo": "booleano",
      "etiquetas": {
        "true": "Sí",
        "false": "No"
      }
    },
    {
      "id": "cambios_caract",
      "nombre": "Características de los cambios o los nuevos peligros",
      "tipo": "texto_largo"
    }
  ],
  "valor": [
    {
      "peligro": "Inundaciones",
      "antecedentes_sino": "No",
      "antecedentes_caract": "",
      "cambios_sino": "",
      "cambios_caract": ""
    },
    {
      "peligro": "Movimientos en masa",
      "antecedentes_sino": "No",
      "antecedentes_caract": "",
      "cambios_sino": "",
      "cambios_caract": ""
    },
    {
      "peligro": "Lluvias intensas",
      "antecedentes_sino": "Sí",
      "antecedentes_caract": "Periodo de lluvias, entre diciembre y marzo.",
      "cambios_sino": "",
      "cambios_caract": ""
    },
    {
      "peligro": "Helada",
      "antecedentes_sino": "Sí",
      "antecedentes_caract": "Periodo de heladas, mayo a junio.",
      "cambios_sino": "",
      "cambios_caract": ""
    },
    {
      "peligro": "Nevadas",
      "antecedentes_sino": "No",
      "antecedentes_caract": "",
      "cambios_sino": "",
      "cambios_caract": ""
    },
    {
      "peligro": "Friaje",
      "antecedentes_sino": "No",
      "antecedentes_caract": "",
      "cambios_sino": "",
      "cambios_caract": ""
    },
    {
      "peligro": "Sismos",
      "antecedentes_sino": "Sí",
      "antecedentes_caract": "Leves o moderados",
      "cambios_sino": "",
      "cambios_caract": ""
    },
    {
      "peligro": "Sequías",
      "antecedentes_sino": "No",
      "antecedentes_caract": "",
      "cambios_sino": "",
      "cambios_caract": ""
    },
    {
      "peligro": "Vulcanismo",
      "antecedentes_sino": "No",
      "antecedentes_caract": "",
      "cambios_sino": "",
      "cambios_caract": ""
    },
    {
      "peligro": "Tsunamis",
      "antecedentes_sino": "No",
      "antecedentes_caract": "",
      "cambios_sino": "",
      "cambios_caract": ""
    },
    {
      "peligro": "Incendios forestales",
      "antecedentes_sino": "No",
      "antecedentes_caract": "",
      "cambios_sino": "",
      "cambios_caract": ""
    },
    {
      "peligro": "Erosión",
      "antecedentes_sino": "No",
      "antecedentes_caract": "",
      "cambios_sino": "",
      "cambios_caract": ""
    },
    {
      "peligro": "Vientos fuertes",
      "antecedentes_sino": "Sí",
      "antecedentes_caract": "Periodo de vientos, agosto",
      "cambios_sino": "",
      "cambios_caract": ""
    },
    {
      "peligro": "Incendios urbanos",
      "antecedentes_sino": "No",
      "antecedentes_caract": "",
      "cambios_sino": "",
      "cambios_caract": ""
    },
    {
      "peligro": "Radiación solar",
      "antecedentes_sino": "No",
      "antecedentes_caract": "",
      "cambios_sino": "",
      "cambios_caract": ""
    },
    {
      "peligro": "Otros",
      "antecedentes_sino": "No",
      "antecedentes_caract": "",
      "cambios_sino": "",
      "cambios_caract": ""
    }
  ]
}
```

---

## Resumen de acción para autollenado (Sección 02)

| ID | Nombre | Tipo | Editable | Acción sugerida |
|---|---|---|---|---|
| 2.01.01 | Localización del área de estudio… | tabla | Sí | Llenar filas geográficas |
| 2.02.01 | Localización del área de influencia… | tabla | Sí | Llenar (concepto distinto a 2.01) |
| 2.03.01 | Mapa de macrolocalización | imagen | Sí | Imagen (no texto) |
| 2.03.02 | Leyenda macrolocalización | calculado | No | NO LLENAR |
| 2.03.03 | Mapa de microlocalización | imagen | Sí | Imagen (no texto) |
| 2.03.04 | Leyenda microlocalización | calculado | No | NO LLENAR |
| 2.03.05 | Fuente de información | texto_corto | Sí | Llenar |
| 2.04.1 | Altitud | texto_corto | Sí | Llenar |
| 2.04.02 | Temperatura media anual | texto_corto | Sí | Llenar |
| 2.04.03 | Humedad | texto_corto | Sí | Llenar |
| 2.04.04 | Precipitación media anual | texto_corto | Sí | Llenar |
| 2.04.05 | Coordenadas… | coordenadas | Sí | Llenar `{lat,lng}` |
| 2.04.06 | Accesibilidad | texto_largo | Sí | Llenar |
| 2.04.07 | Condiciones de pobreza | texto_largo | Sí | Llenar |
| 2.04.08 | Características sociales y económicas | texto_largo | Sí | Llenar |
| 2.04.09 | Acceso a servicios públicos | tabla | Sí | Llenar % / año / fuente |
| 2.05.01 | Peligros del área de estudio | tabla | Sí | Llenar booleanos + características |
