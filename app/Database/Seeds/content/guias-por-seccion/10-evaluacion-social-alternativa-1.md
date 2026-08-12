# SECCIÓN N°10: EVALUACIÓN SOCIAL - ALTERNATIVA 1

## Descripción de la sección

- **Qué representa:** SECCIÓN N°10: EVALUACIÓN SOCIAL - ALTERNATIVA 1.
- **Objetivo (según instructivo):** Información no determinada por los archivos proporcionados.
- **Hoja Excel:** `Evaluación Social Alt1`
- **JSON `id` de sección:** `12`

### Contexto del instructivo (extracto)

MÓDULO 4. EVALUACIÓN El Módulo 4: Evaluación tiene como objetivo determinar si la ejecución del proyecto del CIAI es conveniente para la sociedad en su conjunto. En este módulo se desarrollan los siguientes puntos: Gráfico 15. Contenido del Módulo 4: Evaluación SECCIÓN 10 - EVALUACIÓN SOCIAL En este punto se presentan las orientaciones para identificar, medir y valorizar de los beneficios y costos del proyecto, desde el punto de vista del bienestar social de todo el país, así como estimar los indicadores de rentabilidad social y realizar el análisis de sensibilidad bidimensional. Este punto comprende el siguiente contenido: Gráfico 16. Contenido de la Evaluación social Es importante precisar que se debe desarrollar este punto para cada una de las alternativas técnicas propuestas en el punto Resumen de las alternativas técnicas . En base a los resultados obtenidos en la evaluación social, se deberá seleccionar la mejor alternativa técnica. Evaluación social Sostenibilidad Gestión del proyecto Impacto ambiental Marco Lógico Conclusiones 10.04 10.03 10.02 10.01 Objetivo: Identificar, medir y valorizar de los beneficios y costos del proyecto, desde el punto de vista del bienestar social de todo el país Evaluación social Beneficios sociales Costos sociales Flujo de costos a precios sociales (evaluación social) Indicadores de rentabilidad social 10.05 Análisis de sensibilidad 10.01. Beneficios sociales Los beneficios sociales que están relacionados a los fines del proyecto (que, a su vez, se relacionan a los efectos del problema central identificado), permiten incrementar el bienestar de los niños y niñas que reciben el Servicio de Cuidado Diurno en el C IAI, de manera adecuada, como consecuencia del acceso al servicio o de la mejor calidad de éste. Los beneficios sociales, que guardan correspondencia con los fines del proyecto, identificados en el punto Definición de los objetivos del proyecto, son los siguientes:  Mayor acceso de niñas y niños de 6 a 36 meses, a raciones alimentarias y suplemento con hierro.  Niñas y niños de 6 a 36 meses reciben suficiente seguimiento al control CRED.  Adecuado desarrollo cognitivo de niñas y niños de 6 a 36 meses.  Ingesta adecuada de nutrientes por parte de niñas y niños de 6 a 36 meses.  Mayor nivel de desarrollo infantil temprano de los niños y niñas de 6 a 36 meses que viven en situación de pobreza y pobreza extrema. Es importante precisar que estos beneficios solamente serán obtenidos por los niños y niñas que reciban un adecuado SCD en el CIAI. Ejemplo: 10.01 BENEFICIOS SOCIALES Mayor acceso de niñas y niños de 6 a 36 meses, a raciones alimentarias y suplemento con hierro Niñas y niños de 6 a 36 meses reciben suficiente seguimiento al control CRED Adecuado desarrollo cognitivo de niñas y niños de 6 a 36 meses Ingesta adecuada de nutrientes por parte de niñas y niños de 6 a 36 meses Benificios de la intervención Mayor nivel de desarrollo infantil temprano de los niños y niñas menores de 36 meses que viven en situación de pobreza y pobreza extrema 10.02. Costos sociales Los costos sociales 19 es el valor que tiene para la sociedad los factores de producción e insumos que se emplearán durante la ejecución y funcionamiento del proyecto (costo de oportunidad). Para estimar los costos sociales, se aplica los respectivos factores de corrección a los costos directos e indirectos del proyecto de inversión del CIAI y a los costos de operación y mantenimiento, que fueron calculados en el punto Costos del 

**Regla de ejemplos:** cada bloque de ejemplo es el **objeto `campo` completo** del `JSON EJEMPLO.json` correspondiente a esta sección/alternativa.

## Nota sobre alternativas (instructivo — SECCIÓN 10)

Esto **no es un error de modelado**. El instructivo indica que la **Evaluación social** debe desarrollarse **para cada una de las alternativas técnicas** propuestas. Con base en sus resultados se **selecciona la mejor alternativa**.

En el JSON existen tres hojas/nodos hermanos:

| JSON `id` | Hoja | Nombre |
|---|---|---|
| `12` | `Evaluación Social Alt1` | SECCIÓN N°10: EVALUACIÓN SOCIAL - ALTERNATIVA 1 |
| `13` | `Evaluación Social Alt2` | SECCIÓN N°10: EVALUACIÓN SOCIAL - ALTERNATIVA 2 |
| `14` | `Evaluación Social Alt3` | SECCIÓN N°10: EVALUACIÓN SOCIAL - ALTERNATIVA 3 |

Comparten la misma lógica de sección (Evaluación social), con IDs de campos propios por alternativa.

**Esta guía documenta la Alternativa 1** (JSON id `12`).

### Subsecciones / grupos

- `12.01` — 10.01 BENEFICIOS SOCIALES
- `12.02` — 10.02 COSTOS SOCIALES
- `12.03` — 10.03 FLUJO DE COSTOS A PRECIOS SOCIALES (EVALUACIÓN SOCIAL)
- `12.04` — 10.04 INDICADORES DE RENTABILIDAD SOCIAL
- `12.05` — 10.05 ANÁLISIS DE SENSIBILIDAD

---

# 10.01 BENEFICIOS SOCIALES

---

## Campo 12.01.1 — Benificios de la intervención

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `beneficio` | Benificios de la intervención | texto_largo |

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "12.01.1",
  "nombre": "Benificios de la intervención",
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
    "fila_inicial": 8,
    "filas_base": 5,
    "columnas": [
      {
        "id": "beneficio",
        "columna": "B",
        "abarca_columnas": 6
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    {
      "id": "beneficio",
      "nombre": "Benificios de la intervención",
      "tipo": "texto_largo"
    }
  ],
  "valor": [
    {
      "beneficio": "Mayor acceso de niñas y niños de 6 a 36 meses, a raciones alimentarias y suplemento con hierro"
    },
    {
      "beneficio": "Niñas y niños de 6 a 36 meses reciben suficiente seguimiento al control CRED "
    },
    {
      "beneficio": "Adecuado desarrollo cognitivo de niñas y niños de 6 a 36 meses"
    },
    {
      "beneficio": "Ingesta adecuada de nutrientes por parte de niñas y niños de 6 a 36 meses"
    },
    {
      "beneficio": "Mayor nivel de desarrollo infantil temprano de los niños y niñas menores de 36 meses que viven en situación de pobreza y pobreza extrema"
    }
  ]
}
```

---

# 10.02 COSTOS SOCIALES

---

## Campo 12.02.1 — Transformación de costos de inversión a precios sociales

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `detalle` | Detalle | texto_corto |
| `costo_mercado` | Costo a precios de mercado  | decimal |
| `factor_correccion` | Factor de corrección | decimal |
| `costo_social` | Costo a precios sociales  | decimal |

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": true, "abarca_filas": 1, "agrupador_abarca_columnas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "12.02.1",
  "nombre": "Transformación de costos de inversión a precios sociales",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": true,
    "abarca_filas": 1,
    "agrupador_abarca_columnas": 1
  },
  "captura": {
    "columna_inicial": "B",
    "fila_inicial": 21,
    "filas_base": 16,
    "columnas": [
      {
        "id": "detalle",
        "columna": "B",
        "abarca_columnas": 1
      },
      {
        "id": "costo_mercado",
        "columna": "F",
        "abarca_columnas": 1
      },
      {
        "id": "factor_correccion",
        "columna": "G",
        "abarca_columnas": 1
      },
      {
        "id": "costo_social",
        "columna": "H",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    {
      "id": "detalle",
      "nombre": "Detalle",
      "tipo": "texto_corto"
    },
    {
      "id": "costo_mercado",
      "nombre": "Costo a precios de mercado ",
      "tipo": "decimal"
    },
    {
      "id": "factor_correccion",
      "nombre": "Factor de corrección",
      "tipo": "decimal"
    },
    {
      "id": "costo_social",
      "nombre": "Costo a precios sociales ",
      "tipo": "decimal"
    }
  ],
  "valor": [
    {
      "agrupador": {
        "inicia": "detalle",
        "abarca_columnas": 1,
        "nombre": "Componente 1:",
        "valores": {}
      },
      "valores": [
        {
          "detalle": "",
          "costo_mercado": "",
          "factor_correccion": 0.8474576271186441,
          "costo_social": ""
        }
      ]
    },
    {
      "agrupador": {
        "inicia": "detalle",
        "abarca_columnas": 1,
        "nombre": "Componente 2",
        "valores": {}
      },
      "valores": [
        {
          "detalle": "",
          "costo_mercado": "",
          "factor_correccion": 0.8474576271186441,
          "costo_social": ""
        }
      ]
    },
    {
      "agrupador": {
        "inicia": "detalle",
        "abarca_columnas": 1,
        "nombre": "Componente 3",
        "valores": {}
      },
      "valores": [
        {
          "detalle": "",
          "costo_mercado": "",
          "factor_correccion": 0.8474576271186441,
          "costo_social": ""
        }
      ]
    },
    {
      "agrupador": {
        "inicia": "detalle",
        "abarca_columnas": 1,
        "nombre": "Componente 4",
        "valores": {}
      },
      "valores": [
        {
          "detalle": "",
          "costo_mercado": "",
          "factor_correccion": 0.9259259259259258,
          "costo_social": ""
        }
      ]
    },
    {
      "agrupador": {
        "inicia": "detalle",
        "abarca_columnas": 1,
        "nombre": "Medidas de reducción del riesgo de desastre y mitigación ambiental",
        "valores": {}
      },
      "valores": [
        {
          "detalle": "Implementación de medidas ",
          "costo_mercado": "",
          "factor_correccion": 0.79,
          "costo_social": ""
        },
        {
          "detalle": "Gestión del proyecto",
          "costo_mercado": "",
          "factor_correccion": "",
          "costo_social": ""
        },
        {
          "detalle": "Expediente técnico o documento equivalente",
          "costo_mercado": "",
          "factor_correccion": "",
          "costo_social": ""
        },
        {
          "detalle": "Supervisión",
          "costo_mercado": "",
          "factor_correccion": "",
          "costo_social": ""
        },
        {
          "detalle": "Liquidación",
          "costo_mercado": "",
          "factor_correccion": "",
          "costo_social": ""
        },
        {
          "detalle": "Gastos generales",
          "costo_mercado": "",
          "factor_correccion": "",
          "costo_social": ""
        }
      ]
    },
    {
      "agrupador": {
        "inicia": "detalle",
        "abarca_columnas": 1,
        "nombre": "Total",
        "valores": {
          "detalle": "Total",
          "costo_mercado": "",
          "factor_correccion": "",
          "costo_social": ""
        }
      },
      "valores": []
    }
  ]
}
```

---

## Campo 12.02.2 — Transformación de costos de operación y mantenimiento a precios sociales

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `detalle` | Detalle | texto_corto |
| `costo_mercado` | Costo a precios de mercado  | decimal |
| `factor_correccion` | Factor de corrección | decimal |
| `costo_social` | Costo a precios sociales  | decimal |

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": true, "abarca_filas": 1, "agrupador_abarca_columnas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "12.02.2",
  "nombre": "Transformación de costos de operación y mantenimiento a precios sociales",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": true,
    "abarca_filas": 1,
    "agrupador_abarca_columnas": 1
  },
  "captura": {
    "columna_inicial": "B",
    "fila_inicial": 43,
    "filas_base": 6,
    "columnas": [
      {
        "id": "detalle",
        "columna": "B",
        "abarca_columnas": 1
      },
      {
        "id": "costo_mercado",
        "columna": "F",
        "abarca_columnas": 1
      },
      {
        "id": "factor_correccion",
        "columna": "G",
        "abarca_columnas": 1
      },
      {
        "id": "costo_social",
        "columna": "H",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    {
      "id": "detalle",
      "nombre": "Detalle",
      "tipo": "texto_corto"
    },
    {
      "id": "costo_mercado",
      "nombre": "Costo a precios de mercado ",
      "tipo": "decimal"
    },
    {
      "id": "factor_correccion",
      "nombre": "Factor de corrección",
      "tipo": "decimal"
    },
    {
      "id": "costo_social",
      "nombre": "Costo a precios sociales ",
      "tipo": "decimal"
    }
  ],
  "valor": [
    {
      "agrupador": {
        "inicia": "detalle",
        "abarca_columnas": 1,
        "nombre": "Costos de operación incremental",
        "valores": {
          "detalle": "Costos de operación incremental",
          "costo_mercado": "",
          "factor_correccion": "",
          "costo_social": ""
        }
      },
      "valores": [
        {
          "detalle": "a. Personal",
          "costo_mercado": "",
          "factor_correccion": "",
          "costo_social": ""
        },
        {
          "detalle": "·       Actores comunales:",
          "costo_mercado": "",
          "factor_correccion": "",
          "costo_social": ""
        },
        {
          "detalle": "·       Equipo técnico profesional del PNCM: ",
          "costo_mercado": "",
          "factor_correccion": "",
          "costo_social": ""
        },
        {
          "detalle": "b. Servicios",
          "costo_mercado": "",
          "factor_correccion": "",
          "costo_social": ""
        }
      ]
    },
    {
      "agrupador": {
        "inicia": "detalle",
        "abarca_columnas": 1,
        "nombre": "Total",
        "valores": {
          "detalle": "Total",
          "costo_mercado": "",
          "factor_correccion": "",
          "costo_social": ""
        }
      },
      "valores": []
    }
  ]
}
```

---

# 10.03 FLUJO DE COSTOS A PRECIOS SOCIALES (EVALUACIÓN SOCIAL)

**Nota del JSON:** * Se considera el 10% como valor de recuperación de la inversión en infraestructura

---

## Campo 12.03.1 — FLUJO DE COSTOS A PRECIOS SOCIALES (EVALUACIÓN SOCIAL)

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `detalle` | Años | texto_corto |
| `ano0` | Año 0 | decimal |
| `ano1` | Año 1 | decimal |
| `ano2` | Año 2 | decimal |
| `ano3` | Año 3 | decimal |
| `ano4` | Año 4 | decimal |
| `ano5` | Año 5 | decimal |
| `ano6` | Año 6 | decimal |
| `ano7` | Año 7 | decimal |
| `ano8` | Año 8 | decimal |
| `ano9` | Año 9 | decimal |
| `ano10` | Año 10 | decimal |

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": true, "abarca_filas": 1, "agrupador_abarca_columnas": 12}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "12.03.1",
  "nombre": "FLUJO DE COSTOS A PRECIOS SOCIALES (EVALUACIÓN SOCIAL)",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": true,
    "abarca_filas": 1,
    "agrupador_abarca_columnas": 12
  },
  "captura": {
    "columna_inicial": "B",
    "fila_inicial": 53,
    "filas_base": 6,
    "columnas": [
      {
        "id": "detalle",
        "columna": "B",
        "abarca_columnas": 1
      },
      {
        "id": "ano0",
        "columna": "C",
        "abarca_columnas": 1
      },
      {
        "id": "ano1",
        "columna": "D",
        "abarca_columnas": 1
      },
      {
        "id": "ano2",
        "columna": "E",
        "abarca_columnas": 1
      },
      {
        "id": "ano3",
        "columna": "F",
        "abarca_columnas": 1
      },
      {
        "id": "ano4",
        "columna": "G",
        "abarca_columnas": 1
      },
      {
        "id": "ano5",
        "columna": "H",
        "abarca_columnas": 1
      },
      {
        "id": "ano6",
        "columna": "I",
        "abarca_columnas": 1
      },
      {
        "id": "ano7",
        "columna": "J",
        "abarca_columnas": 1
      },
      {
        "id": "ano8",
        "columna": "K",
        "abarca_columnas": 1
      },
      {
        "id": "ano9",
        "columna": "L",
        "abarca_columnas": 1
      },
      {
        "id": "ano10",
        "columna": "M",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    {
      "id": "detalle",
      "nombre": "Años",
      "tipo": "texto_corto"
    },
    {
      "id": "ano0",
      "nombre": "Año 0",
      "tipo": "decimal"
    },
    {
      "id": "ano1",
      "nombre": "Año 1",
      "tipo": "decimal"
    },
    {
      "id": "ano2",
      "nombre": "Año 2",
      "tipo": "decimal"
    },
    {
      "id": "ano3",
      "nombre": "Año 3",
      "tipo": "decimal"
    },
    {
      "id": "ano4",
      "nombre": "Año 4",
      "tipo": "decimal"
    },
    {
      "id": "ano5",
      "nombre": "Año 5",
      "tipo": "decimal"
    },
    {
      "id": "ano6",
      "nombre": "Año 6",
      "tipo": "decimal"
    },
    {
      "id": "ano7",
      "nombre": "Año 7",
      "tipo": "decimal"
    },
    {
      "id": "ano8",
      "nombre": "Año 8",
      "tipo": "decimal"
    },
    {
      "id": "ano9",
      "nombre": "Año 9",
      "tipo": "decimal"
    },
    {
      "id": "ano10",
      "nombre": "Año 10",
      "tipo": "decimal"
    }
  ],
  "valor": [
    {
      "agrupador": {
        "inicia": "detalle",
        "abarca_columnas": 1,
        "nombre": "",
        "valores": {}
      },
      "valores": [
        {
          "detalle": "Años",
          "ano0": "",
          "ano1": "",
          "ano2": "",
          "ano3": "",
          "ano4": "",
          "ano5": "",
          "ano6": "",
          "ano7": "",
          "ano8": "",
          "ano9": "",
          "ano10": ""
        }
      ]
    },
    {
      "agrupador": {
        "inicia": "detalle",
        "abarca_columnas": 1,
        "nombre": "Costos de inversión, operación y mantenimiento a precios sociales",
        "valores": {
          "detalle": "Costos de inversión, operación y mantenimiento a precios sociales",
          "ano0": "",
          "ano1": "",
          "ano2": "",
          "ano3": "",
          "ano4": "",
          "ano5": "",
          "ano6": "",
          "ano7": "",
          "ano8": "",
          "ano9": "",
          "ano10": ""
        }
      },
      "valores": [
        {
          "detalle": "1. Costos de inversión",
          "ano0": "",
          "ano1": "",
          "ano2": "",
          "ano3": "",
          "ano4": "",
          "ano5": "",
          "ano6": "",
          "ano7": "",
          "ano8": "",
          "ano9": "",
          "ano10": ""
        },
        {
          "detalle": "2. Costos de reinversión",
          "ano0": "",
          "ano1": "",
          "ano2": "",
          "ano3": "",
          "ano4": "",
          "ano5": "",
          "ano6": "",
          "ano7": "",
          "ano8": "",
          "ano9": "",
          "ano10": ""
        },
        {
          "detalle": "3. Costos de operación y mantenimiento incremental",
          "ano0": "",
          "ano1": "",
          "ano2": "",
          "ano3": "",
          "ano4": "",
          "ano5": "",
          "ano6": "",
          "ano7": "",
          "ano8": "",
          "ano9": "",
          "ano10": ""
        },
        {
          "detalle": "Total Costos",
          "ano0": "",
          "ano1": "",
          "ano2": "",
          "ano3": "",
          "ano4": "",
          "ano5": "",
          "ano6": "",
          "ano7": "",
          "ano8": "",
          "ano9": "",
          "ano10": ""
        }
      ]
    }
  ]
}
```

---

# 10.04 INDICADORES DE RENTABILIDAD SOCIAL

---

## Campo 12.04.1 — INDICADORES DE RENTABILIDAD SOCIAL

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)


**Niveles (tabla jerárquica):**

| id | Nombre | Tipo |
|---|---|---|
| `tipo` | Tipo | texto_corto |
| `criterio` | Criterio de elección** | texto_corto |
| `valor_alt1` | Alternativa 1 | decimal |

**Config:** `{"filas": "jerarquicas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "12.04.1",
  "nombre": "INDICADORES DE RENTABILIDAD SOCIAL",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "jerarquicas",
    "columnas": "fijas",
    "agrupador": false,
    "abarca_filas": 1
  },
  "captura": {
    "fila_inicial": 64,
    "filas_base": 2,
    "columnas": [
      {
        "id": "tipo",
        "columna": "B",
        "abarca_columnas": 1
      },
      {
        "id": "criterio",
        "columna": "C",
        "abarca_columnas": 3
      },
      {
        "id": "valor_alt1",
        "columna": "F",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [],
  "niveles": [
    {
      "id": "tipo",
      "nombre": "Tipo",
      "tipo": "texto_corto",
      "combina_vertical": true
    },
    {
      "id": "criterio",
      "nombre": "Criterio de elección**",
      "tipo": "texto_corto"
    },
    {
      "id": "valor_alt1",
      "nombre": "Alternativa 1",
      "tipo": "decimal"
    }
  ],
  "valor": [
    {
      "tipo": "Costo / Eficacia a precios sociales",
      "hijos": [
        {
          "criterio": "Valor Actual de los Costos (VAC)",
          "hijos": [
            {
              "valor_alt1": ""
            }
          ]
        },
        {
          "criterio": "Costo por beneficiario directo",
          "hijos": [
            {
              "valor_alt1": ""
            }
          ]
        }
      ]
    }
  ]
}
```

---

# 10.05 ANÁLISIS DE SENSIBILIDAD

---

## Campo 12.05.1 — ANALISIS DE SENSIBILIDAD BIDIMENSIONAL

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)


**Niveles (tabla jerárquica):**

| id | Nombre | Tipo |
|---|---|---|
| `eje_beneficiarios` | ICE (S/) | texto_corto |
| `pct_beneficiarios` | Variación % del total de beneficiarios | decimal |
| `var_20` | 20 | decimal |
| `var_15` | 15 | decimal |
| `var_10` | 10 | decimal |
| `var_0` | 0 | decimal |
| `var_neg10` | -10 | decimal |
| `var_neg15` | -15 | decimal |
| `var_neg20` | -20 | decimal |

**Cabecera agrupada:**

```json
[
  {
    "titulo": "Variación % de los costos del proyecto",
    "hijos": [
      "var_20",
      "var_15",
      "var_10",
      "var_0",
      "var_neg10",
      "var_neg15",
      "var_neg20"
    ]
  }
]
```

**Config:** `{"filas": "jerarquicas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "12.05.1",
  "nombre": "ANALISIS DE SENSIBILIDAD BIDIMENSIONAL",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "jerarquicas",
    "columnas": "fijas",
    "agrupador": false,
    "abarca_filas": 1
  },
  "captura": {
    "fila_inicial": 73,
    "filas_base": 7,
    "columnas": [
      {
        "id": "eje_beneficiarios",
        "columna": "B",
        "abarca_columnas": 1
      },
      {
        "id": "pct_beneficiarios",
        "columna": "C",
        "abarca_columnas": 1
      },
      {
        "id": "var_20",
        "columna": "D",
        "abarca_columnas": 1
      },
      {
        "id": "var_15",
        "columna": "E",
        "abarca_columnas": 1
      },
      {
        "id": "var_10",
        "columna": "F",
        "abarca_columnas": 1
      },
      {
        "id": "var_0",
        "columna": "G",
        "abarca_columnas": 1
      },
      {
        "id": "var_neg10",
        "columna": "H",
        "abarca_columnas": 1
      },
      {
        "id": "var_neg15",
        "columna": "I",
        "abarca_columnas": 1
      },
      {
        "id": "var_neg20",
        "columna": "J",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "Variación % de los costos del proyecto",
      "hijos": [
        "var_20",
        "var_15",
        "var_10",
        "var_0",
        "var_neg10",
        "var_neg15",
        "var_neg20"
      ]
    }
  ],
  "niveles": [
    {
      "id": "eje_beneficiarios",
      "nombre": "ICE (S/)",
      "tipo": "texto_corto",
      "combina_vertical": true
    },
    {
      "id": "pct_beneficiarios",
      "nombre": "Variación % del total de beneficiarios",
      "tipo": "decimal"
    },
    {
      "id": "var_20",
      "nombre": "20",
      "tipo": "decimal"
    },
    {
      "id": "var_15",
      "nombre": "15",
      "tipo": "decimal"
    },
    {
      "id": "var_10",
      "nombre": "10",
      "tipo": "decimal"
    },
    {
      "id": "var_0",
      "nombre": "0",
      "tipo": "decimal"
    },
    {
      "id": "var_neg10",
      "nombre": "-10",
      "tipo": "decimal"
    },
    {
      "id": "var_neg15",
      "nombre": "-15",
      "tipo": "decimal"
    },
    {
      "id": "var_neg20",
      "nombre": "-20",
      "tipo": "decimal"
    }
  ],
  "valor": [
    {
      "eje_beneficiarios": "Variación % del total de beneficiarios",
      "hijos": [
        {
          "pct_beneficiarios": "35",
          "hijos": [
            {
              "var_20": "",
              "hijos": [
                {
                  "var_15": "",
                  "hijos": [
                    {
                      "var_10": "",
                      "hijos": [
                        {
                          "var_0": "",
                          "hijos": [
                            {
                              "var_neg10": "",
                              "hijos": [
                                {
                                  "var_neg15": "",
                                  "hijos": [
                                    {
                                      "var_neg20": ""
                                    }
                                  ]
                                }
                              ]
                            }
                          ]
                        }
                      ]
                    }
                  ]
                }
              ]
            }
          ]
        },
        {
          "pct_beneficiarios": "25",
          "hijos": [
            {
              "var_20": "",
              "hijos": [
                {
                  "var_15": "",
                  "hijos": [
                    {
                      "var_10": "",
                      "hijos": [
                        {
                          "var_0": "",
                          "hijos": [
                            {
                              "var_neg10": "",
                              "hijos": [
                                {
                                  "var_neg15": "",
                                  "hijos": [
                                    {
                                      "var_neg20": ""
                                    }
                                  ]
                                }
                              ]
                            }
                          ]
                        }
                      ]
                    }
                  ]
                }
              ]
            }
          ]
        },
        {
          "pct_beneficiarios": "15",
          "hijos": [
            {
              "var_20": "",
              "hijos": [
                {
                  "var_15": "",
                  "hijos": [
                    {
                      "var_10": "",
                      "hijos": [
                        {
                          "var_0": "",
                          "hijos": [
                            {
                              "var_neg10": "",
                              "hijos": [
                                {
                                  "var_neg15": "",
                                  "hijos": [
                                    {
                                      "var_neg20": ""
                                    }
                                  ]
                                }
                              ]
                            }
                          ]
                        }
                      ]
                    }
                  ]
                }
              ]
            }
          ]
        },
        {
          "pct_beneficiarios": "0",
          "hijos": [
            {
              "var_20": "",
              "hijos": [
                {
                  "var_15": "",
                  "hijos": [
                    {
                      "var_10": "",
                      "hijos": [
                        {
                          "var_0": "",
                          "hijos": [
                            {
                              "var_neg10": "",
                              "hijos": [
                                {
                                  "var_neg15": "",
                                  "hijos": [
                                    {
                                      "var_neg20": ""
                                    }
                                  ]
                                }
                              ]
                            }
                          ]
                        }
                      ]
                    }
                  ]
                }
              ]
            }
          ]
        },
        {
          "pct_beneficiarios": "-15",
          "hijos": [
            {
              "var_20": "",
              "hijos": [
                {
                  "var_15": "",
                  "hijos": [
                    {
                      "var_10": "",
                      "hijos": [
                        {
                          "var_0": "",
                          "hijos": [
                            {
                              "var_neg10": "",
                              "hijos": [
                                {
                                  "var_neg15": "",
                                  "hijos": [
                                    {
                                      "var_neg20": ""
                                    }
                                  ]
                                }
                              ]
                            }
                          ]
                        }
                      ]
                    }
                  ]
                }
              ]
            }
          ]
        },
        {
          "pct_beneficiarios": "-25",
          "hijos": [
            {
              "var_20": "",
              "hijos": [
                {
                  "var_15": "",
                  "hijos": [
                    {
                      "var_10": "",
                      "hijos": [
                        {
                          "var_0": "",
                          "hijos": [
                            {
                              "var_neg10": "",
                              "hijos": [
                                {
                                  "var_neg15": "",
                                  "hijos": [
                                    {
                                      "var_neg20": ""
                                    }
                                  ]
                                }
                              ]
                            }
                          ]
                        }
                      ]
                    }
                  ]
                }
              ]
            }
          ]
        },
        {
          "pct_beneficiarios": "-35",
          "hijos": [
            {
              "var_20": "",
              "hijos": [
                {
                  "var_15": "",
                  "hijos": [
                    {
                      "var_10": "",
                      "hijos": [
                        {
                          "var_0": "",
                          "hijos": [
                            {
                              "var_neg10": "",
                              "hijos": [
                                {
                                  "var_neg15": "",
                                  "hijos": [
                                    {
                                      "var_neg20": ""
                                    }
                                  ]
                                }
                              ]
                            }
                          ]
                        }
                      ]
                    }
                  ]
                }
              ]
            }
          ]
        }
      ]
    }
  ]
}
```

---

## Resumen de acción para autollenado

| ID | Nombre | Tipo | Editable | Acción sugerida |
|---|---|---|---|---|
| 12.01.1 | Benificios de la intervención | tabla | Sí | Llenar tabla |
| 12.02.1 | Transformación de costos de inversión a precios sociales | tabla | Sí | Llenar tabla |
| 12.02.2 | Transformación de costos de operación y mantenimiento a precios sociales | tabla | Sí | Llenar tabla |
| 12.03.1 | FLUJO DE COSTOS A PRECIOS SOCIALES (EVALUACIÓN SOCIAL) | tabla | Sí | Llenar tabla |
| 12.04.1 | INDICADORES DE RENTABILIDAD SOCIAL | tabla | Sí | Llenar tabla |
| 12.05.1 | ANALISIS DE SENSIBILIDAD BIDIMENSIONAL | tabla | Sí | Llenar tabla |
