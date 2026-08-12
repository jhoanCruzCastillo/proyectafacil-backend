# SECCIÓN N°05: PROBLEMA/OBJETIVO

## Descripción de la sección

- **Qué representa:** SECCIÓN N°05: PROBLEMA/OBJETIVO.
- **Objetivo (según instructivo):** plantear las alternativas de solución, que deben tener relación con el objetivo central y ser técnicamente posibles, pertinentes y comparables.
- **Hoja Excel:** `Problema-Objetivo`
- **JSON `id` de sección:** `5`

### Contexto del instructivo (extracto)

SECCIÓN 5 - PROBLEMA / OBJETIVO DEL PROYECTO DE INVERSIÓN En este punto se presentan las orientaciones para identificar el problema central, sus causas y efectos, la definición de los objetivos del proyecto de inversión del CIAI y la descripción de las alternativas de solución al problema identificado. Este punto tiene como objetivo plantear las alternativas de solución, que deben tener relación con el objetivo central y ser técnicamente posibles, pertinentes y comparables. En el siguiente gráfico se presenta el contenido del punto Problema / Objetivo del proyecto de inversión: Gráfico 7. Contenido del análisis del Problema y Objetivo del proyecto de inversión 5.01. Definición del problema, sus causas y efectos De acuerdo a lo precisado en el SNPMGI, el problema central es la situación negativa que afecta a toda la población o una parte de ella, dentro del área de influencia del proyecto. Sus causas y sus efectos son identificados utilizando la técnica del árbol de causa – efecto o árbol de problemas). De esta manera, se identifica, organiza y estructura el problema central, las causas que lo explican y los efectos que se derivan de su presencia. En el caso de proyectos de inversión de CIAI, el problema central puede estar referido a las siguientes situaciones:  Los niños y niñas, entre 6 y 36 meses de edad, acceden a una inadecuada prestación del Servicio de Cuidado Diurno, cuando existe el CIAI, pero está operando en condiciones inadecuadas. 5.03 5.02 5.01 Definición del problema, sus causas y efectos Definición de los objetivos del proyecto Descripción de las alternativas de solución al problema Objetivo: plantear las alternativas de solución, que deben tener relación con el objetivo central y ser técnicamente posibles, pertinentes y comparables Problema / objetivos / alternativa  Los niños y niñas, entre 6 y 36 meses de edad, no acceden al Servicio de Cuidado Diurno, cuando no existe el CIAI. Las causas problema central están en función a la brecha (de calidad o de cobertura) que se busca cerrar con la implementación del proyecto de inversión. Para la FTE, el problema central, sus causas y efectos, están referidos a la brecha de calidad. Para las intervenciones en CIAI, se considera las siguientes hipótesis: Gráfico 8. Planteamiento de la hipótesis del problema y sus causas: Calidad del servicio En base al diagnóstico realizado en los puntos anteriores, se debe identificar el problema central y sus causas, considerando las hipótesis definidas para proyectos de inversión de CIAI. Es importante presentar las evidencias de las causas identificadas. Los efectos identificados para la problemática del Servicio de Cuidado Diurno en los CIAI (tanto de calidad como de cobertura), son los siguientes: Gráfico 9. Planteamiento de la hipótesis del problema y sus efectos De igual forma, es importante presentar las evidencias de los efectos identificados. En la Ficha Técnica Estándar se ha definido, como hipótesis, el problema central, sus causas y sus efectos. Ejemplo: 5.02. Definición de los objetivos del proyecto De acuerdo a lo precisado en el SNPMGI, el objetivo central es la situación deseada que se espera lograr luego de la intervención con el proyecto. Siempre estará asociado a la solución del problema central. Entonces, el objetivo en proyectos de inversión de CIAI pueden ser:  Los niños y niñas, entre 6 y 36 meses de edad, acceden a una adecuada prestación del Servicio de Cuidado Diurno, cuando la intervención busca cerrar la brecha d

**Regla de ejemplos:** cada bloque de ejemplo es el **objeto `campo` completo** del `JSON EJEMPLO.json` correspondiente a esta sección/alternativa.

### Subsecciones / grupos

- `5.01` — 5.01 Definición del problema, sus causas y efectos
- `5.02` — 5.02 Definición de los objetivos del proyecto
- `5.03` — 5.03 Descripción de la alternativa de solución al problema

---

# 5.01 Definición del problema, sus causas y efectos

---

## Campo 5.01.01 — Definición del problema central

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `descripcion_problema` | Descripción del problema central | texto_largo |
| `indicador` | Indicador | texto_corto |
| `descripcion_indicador` | Descripción del indicador | texto_largo |
| `um` | UM | texto_corto |
| `valor` | Valor | decimal |

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "5.01.01",
  "nombre": "Definición del problema central",
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
    "filas_base": 1,
    "columnas": [
      {
        "id": "descripcion_problema",
        "columna": "B",
        "abarca_columnas": 4
      },
      {
        "id": "indicador",
        "columna": "F",
        "abarca_columnas": 4
      },
      {
        "id": "descripcion_indicador",
        "columna": "J",
        "abarca_columnas": 3
      },
      {
        "id": "um",
        "columna": "M",
        "abarca_columnas": 1
      },
      {
        "id": "valor",
        "columna": "N",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    {
      "id": "descripcion_problema",
      "nombre": "Descripción del problema central",
      "tipo": "texto_largo"
    },
    {
      "id": "indicador",
      "nombre": "Indicador",
      "tipo": "texto_corto"
    },
    {
      "id": "descripcion_indicador",
      "nombre": "Descripción del indicador",
      "tipo": "texto_largo"
    },
    {
      "id": "um",
      "nombre": "UM",
      "tipo": "texto_corto"
    },
    {
      "id": "valor",
      "nombre": "Valor",
      "tipo": "decimal"
    }
  ],
  "valor": [
    {
      "descripcion_problema": "",
      "indicador": "",
      "descripcion_indicador": "",
      "um": "CIAI",
      "valor": 0
    }
  ]
}
```

---

## Campo 5.01.02 — Causas directas, indirectas y evidencias

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)


**Niveles (tabla jerárquica):**

| id | Nombre | Tipo |
|---|---|---|
| `cd` | Causas Directas (CD) | texto_largo |
| `ci` | Causas indirectas (CI) | texto_largo |
| `evidencias` | Evidencias | texto_largo |

**Config:** `{"filas": "jerarquicas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "5.01.02",
  "nombre": "Causas directas, indirectas y evidencias",
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
    "fila_inicial": 12,
    "filas_base": 4,
    "columnas": [
      {
        "id": "cd",
        "columna": "B",
        "abarca_columnas": 4
      },
      {
        "id": "ci",
        "columna": "F",
        "abarca_columnas": 4
      },
      {
        "id": "evidencias",
        "columna": "J",
        "abarca_columnas": 4
      }
    ]
  },
  "cabecera": [],
  "niveles": [
    {
      "id": "cd",
      "nombre": "Causas Directas (CD)",
      "tipo": "texto_largo",
      "combina_vertical": true
    },
    {
      "id": "ci",
      "nombre": "Causas indirectas (CI)",
      "tipo": "texto_largo"
    },
    {
      "id": "evidencias",
      "nombre": "Evidencias",
      "tipo": "texto_largo"
    }
  ],
  "valor": [
    {
      "cd": "Inadecuadas condiciones físicas para brindar el servicio de cuidado diurno",
      "hijos": [
        {
          "ci": "Infraestructura del CIAI no cumple con los estándares de calidad",
          "hijos": [
            {
              "evidencias": "El 100% de la infraestructura ha sido declarada inhabitable por la autoridad municipal."
            }
          ]
        },
        {
          "ci": "Equipos del CIAI no cumplen con los estándares de calidad",
          "hijos": [
            {
              "evidencias": "El 100% de los equipos ha superado su vida útil."
            }
          ]
        },
        {
          "ci": "Mobiliario del CIAI no cumple con los estándares de calidad",
          "hijos": [
            {
              "evidencias": "El 100% del mobiliario no cumple con los estándares definidos por el PNCM, además de haber superado su vida útil"
            }
          ]
        }
      ]
    },
    {
      "cd": "Insuficiente capacidad para la gestión y atención del servicio de cuidado diurno",
      "hijos": [
        {
          "ci": "Insuficiente capacitación de recursos humanos que operan en el CIAI",
          "hijos": [
            {
              "evidencias": "El 65% de los actores comunales no han recibido capacitación, en el último año, respecto a temas de gestión y/o  temas de cuidado integral, en el marco de las disposiciones del PNCM"
            }
          ]
        }
      ]
    }
  ]
}
```

---

## Campo 5.01.03 — Efectos directos, sustento y evidencias

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)


**Niveles (tabla jerárquica):**

| id | Nombre | Tipo |
|---|---|---|
| `ei` | Sustento (evidencias) | texto_largo |
| `evidencias` | Sustento (evidencias) | texto_largo |
| `ed` | Efectos Directos (ED) | texto_largo |

**Config:** `{"filas": "jerarquicas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "5.01.03",
  "nombre": "Efectos directos, sustento y evidencias",
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
    "fila_inicial": 19,
    "filas_base": 3,
    "columnas": [
      {
        "id": "ei",
        "columna": "F",
        "abarca_columnas": 4
      },
      {
        "id": "evidencias",
        "columna": "J",
        "abarca_columnas": 4
      },
      {
        "id": "ed",
        "columna": "B",
        "abarca_columnas": 4
      }
    ]
  },
  "cabecera": [],
  "niveles": [
    {
      "id": "ei",
      "nombre": "Sustento (evidencias)",
      "tipo": "texto_largo",
      "combina_vertical": true
    },
    {
      "id": "evidencias",
      "nombre": "Sustento (evidencias)",
      "tipo": "texto_largo"
    },
    {
      "id": "ed",
      "nombre": "Efectos Directos (ED)",
      "tipo": "texto_largo"
    }
  ],
  "valor": [
    {
      "ei": "Efecto indirecto 1: Inadecuado desarrollo cognitivo de niñas y niños de 6 a 36 meses",
      "hijos": [
        {
          "evidencias": "El 77% de niños y niñas uo muestran un adecuado nivel de desarrollo cognitivo, en base a su edad",
          "hijos": [
            {
              "ed": "Efecto directo 1: Menor participación en experiencias de aprendizaje infantil de niñas y niños de 6 a 36 meses"
            }
          ]
        }
      ]
    },
    {
      "ei": "Efecto indirecto 2: Ingesta inadecuada de nutrientes por parte de niñas y niños de 6 a 36 meses",
      "hijos": [
        {
          "evidencias": "El 59.8% de niños y niñas que asisten al CIAI presentan un cuadro de anemia severo",
          "hijos": [
            {
              "ed": "Efecto directo 2: Menor acceso de niñas y niños de 6 a 36 meses, a raciones alimentarias y suplemento con hierro"
            },
            {
              "ed": "Efecto directo 3: Niñas y niños de 6 a 36 meses reciben insuficiente seguimiento al control CRED "
            }
          ]
        }
      ]
    }
  ]
}
```

---

# 5.02 Definición de los objetivos del proyecto

**Nota del JSON:** * Esta información proviene de la información registrada en la tabla 1.04

---

## Campo 5.02.01 — Definición del objetivo central

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `descripcion_objetivo` | Descripción del objetivo central | texto_largo |
| `indicador` | Indicador | texto_corto |
| `descripcion_indicador` | Descripción del indicador | texto_largo |
| `um` | UM | texto_corto |
| `valor` | Valor | decimal |

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "5.02.01",
  "nombre": "Definición del objetivo central",
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
    "fila_inicial": 33,
    "filas_base": 1,
    "columnas": [
      {
        "id": "descripcion_objetivo",
        "columna": "B",
        "abarca_columnas": 4
      },
      {
        "id": "indicador",
        "columna": "F",
        "abarca_columnas": 4
      },
      {
        "id": "descripcion_indicador",
        "columna": "J",
        "abarca_columnas": 3
      },
      {
        "id": "um",
        "columna": "M",
        "abarca_columnas": 1
      },
      {
        "id": "valor",
        "columna": "N",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    {
      "id": "descripcion_objetivo",
      "nombre": "Descripción del objetivo central",
      "tipo": "texto_largo"
    },
    {
      "id": "indicador",
      "nombre": "Indicador",
      "tipo": "texto_corto"
    },
    {
      "id": "descripcion_indicador",
      "nombre": "Descripción del indicador",
      "tipo": "texto_largo"
    },
    {
      "id": "um",
      "nombre": "UM",
      "tipo": "texto_corto"
    },
    {
      "id": "valor",
      "nombre": "Valor",
      "tipo": "decimal"
    }
  ],
  "valor": [
    {
      "descripcion_objetivo": "",
      "indicador": "",
      "descripcion_indicador": "",
      "um": "",
      "valor": ""
    }
  ]
}
```

---

## Campo 5.02.02 — Medios fundamentales

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `numero` | N° | numero |
| `medio` | Medios fundamentales (componentes) | texto_largo |
| `acciones` | Acciones | texto_largo |

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "5.02.02",
  "nombre": "Medios fundamentales",
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
    "columna_inicial": "B",
    "fila_inicial": 40,
    "filas_base": 1,
    "columnas": [
      {
        "id": "numero",
        "columna": "B",
        "abarca_columnas": 1
      },
      {
        "id": "medio",
        "columna": "C",
        "abarca_columnas": 4
      },
      {
        "id": "acciones",
        "columna": "G",
        "abarca_columnas": 4
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    {
      "id": "numero",
      "nombre": "N°",
      "tipo": "numero"
    },
    {
      "id": "medio",
      "nombre": "Medios fundamentales (componentes)",
      "tipo": "texto_largo"
    },
    {
      "id": "acciones",
      "nombre": "Acciones",
      "tipo": "texto_largo"
    }
  ],
  "valor": [
    {
      "numero": 1,
      "medio": "",
      "acciones": "Acción 1.1.2  Reforzamiento de infraestructura del CIAI, que cumpla con los estándares de calidad"
    }
  ]
}
```

---

## Campo 5.02.04 — Medios fundamentales

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)

| id | Nombre | Tipo |
|---|---|---|
| `6310a86f-00ef-433f-8b4a-2d1003d3e21a` | N° | texto_corto |
| `44919ce7-f080-48db-a612-c40238359718` | Medios fundamentales (componentes) | texto_corto |
| `f9652897-d300-44f9-95de-2ed2cf45a7a7` | Acciones | texto_corto |

**Config:** `{"filas": "planas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "5.02.04",
  "nombre": "Medios fundamentales",
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
    "columna_inicial": "B",
    "fila_inicial": 44,
    "filas_base": 3,
    "columnas": [
      {
        "id": "6310a86f-00ef-433f-8b4a-2d1003d3e21a",
        "columna": "B",
        "abarca_columnas": 1
      },
      {
        "id": "44919ce7-f080-48db-a612-c40238359718",
        "columna": "C",
        "abarca_columnas": 4
      },
      {
        "id": "f9652897-d300-44f9-95de-2ed2cf45a7a7",
        "columna": "G",
        "abarca_columnas": 4
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    {
      "id": "6310a86f-00ef-433f-8b4a-2d1003d3e21a",
      "nombre": "N°",
      "tipo": "texto_corto"
    },
    {
      "id": "44919ce7-f080-48db-a612-c40238359718",
      "nombre": "Medios fundamentales (componentes)",
      "tipo": "texto_corto"
    },
    {
      "id": "f9652897-d300-44f9-95de-2ed2cf45a7a7",
      "nombre": "Acciones",
      "tipo": "texto_corto"
    }
  ],
  "valor": [
    {
      "6310a86f-00ef-433f-8b4a-2d1003d3e21a": "2",
      "44919ce7-f080-48db-a612-c40238359718": "",
      "f9652897-d300-44f9-95de-2ed2cf45a7a7": "Acción 1.2.1 Adquisición y/o reposición de equipos para el CIAI, de acuerdo a los estándares definidos por el PNCM."
    },
    {
      "6310a86f-00ef-433f-8b4a-2d1003d3e21a": "3",
      "44919ce7-f080-48db-a612-c40238359718": "",
      "f9652897-d300-44f9-95de-2ed2cf45a7a7": "Acción 1.3.1 Adquisición y/o reposición de mobiliario para el CIAI, de acuerdo a los estándares definidos por el PNCM."
    },
    {
      "6310a86f-00ef-433f-8b4a-2d1003d3e21a": "4",
      "44919ce7-f080-48db-a612-c40238359718": "",
      "f9652897-d300-44f9-95de-2ed2cf45a7a7": "Acción 2.1.1  Implementación de capacidades humanas a los actores comunitarios que operan el CIAI"
    }
  ]
}
```

---

## Campo 5.02.03 — Fines directos e indirectos

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)


**Niveles (tabla jerárquica):**

| id | Nombre | Tipo |
|---|---|---|
| `fi` | Fines Indirectos (FI) | texto_largo |
| `fd` | Fines directos (FD) | texto_largo |

**Config:** `{"filas": "jerarquicas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "5.02.03",
  "nombre": "Fines directos e indirectos",
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
    "fila_inicial": 52,
    "filas_base": 3,
    "columnas": [
      {
        "id": "fi",
        "columna": "F",
        "abarca_columnas": 4
      },
      {
        "id": "fd",
        "columna": "B",
        "abarca_columnas": 4
      }
    ]
  },
  "cabecera": [],
  "niveles": [
    {
      "id": "fi",
      "nombre": "Fines Indirectos (FI)",
      "tipo": "texto_largo",
      "combina_vertical": true
    },
    {
      "id": "fd",
      "nombre": "Fines directos (FD)",
      "tipo": "texto_largo"
    }
  ],
  "valor": [
    {
      "fi": "Fin indirecto 1: Adecuado desarrollo cognitivo de niñas y niños de 6 a 36 meses",
      "hijos": [
        {
          "fd": "Fin directo 1: Mayor participación en experiencias de aprendizaje infantil de niñas y niños de 6 a 36 meses"
        }
      ]
    },
    {
      "fi": "Fin indirecto 2: Ingesta adecuada de nutrientes por parte de niñas y niños de 6 a 36 meses",
      "hijos": [
        {
          "fd": "Fin directo 2: Mayor acceso de niñas y niños de 6 a 36 meses, a raciones alimentarias y suplemento con hierro"
        },
        {
          "fd": "Fin directo 3: Niñas y niños de 6 a 36 meses reciben suficiente seguimiento al control CRED "
        }
      ]
    }
  ]
}
```

---

# 5.03 Descripción de la alternativa de solución al problema

---

## Campo 5.03.01 — Alternativa de solución

**Tipo:** Calculado (no captura manual).

**Editable:** No

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

**Regla:** no tratar como captura manual si es calculado / no editable.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "5.03.01",
  "nombre": "Alternativa de solución",
  "tipo_nodo": "campo",
  "tipo": "calculado",
  "editable": false,
  "captura": {
    "columna": "F",
    "fila": 65,
    "abarca_columnas": 9,
    "abarca_filas": 3
  },
  "valor": "Acción 1.1.2  Reforzamiento de infraestructura del CIAI, que cumpla con los estándares de calidad, , , , Acción 1.2.1 Adquisición y/o reposición de equipos para el CIAI, de acuerdo a los estándares definidos por el PNCM., Acción 1.3.1 Adquisición y/o reposición de mobiliario para el CIAI, de acuerdo a los estándares definidos por el PNCM., Acción 2.1.1  Implementación de capacidades humanas a los actores comunitarios que operan el CIAI, "
}
```

---

## Resumen de acción para autollenado

| ID | Nombre | Tipo | Editable | Acción sugerida |
|---|---|---|---|---|
| 5.01.01 | Definición del problema central | tabla | Sí | Llenar tabla |
| 5.01.02 | Causas directas, indirectas y evidencias | tabla | Sí | Llenar tabla |
| 5.01.03 | Efectos directos, sustento y evidencias | tabla | Sí | Llenar tabla |
| 5.02.01 | Definición del objetivo central | tabla | Sí | Llenar tabla |
| 5.02.02 | Medios fundamentales | tabla | Sí | Llenar tabla |
| 5.02.04 | Medios fundamentales | tabla | Sí | Llenar tabla |
| 5.02.03 | Fines directos e indirectos | tabla | Sí | Llenar tabla |
| 5.03.01 | Alternativa de solución | calculado | No | NO LLENAR |
