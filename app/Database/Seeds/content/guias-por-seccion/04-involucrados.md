# SECCIÓN N°04: DIAGNÓSTICO DE LOS INVOLUCRADOS

## Descripción de la sección

- **Qué representa:** el diagnóstico de la población afectada y de los involucrados con el proyecto de inversión del CIAI.
- **Objetivo (según instructivo):** identificar a los principales involucrados, conocer su percepción, expectativas y nivel de participación; además describir y caracterizar la población afectada y su posición frente al proyecto.
- **Qué información contiene:** tipología cuantitativa de población (total → objetivo), tasa de crecimiento, caracterización de niñas/niños demandantes, y matriz de involucrados.
- **Para qué sirve dentro de la ficha:** dimensiona a quién afecta el problema y con quién se alinea (o no) el proyecto a lo largo del ciclo de inversión.

**Hoja Excel:** `Involucrados`

**Nombre en JSON:** `SECCIÓN N°04: DIAGNÓSTICO DE LOS INVOLUCRADOS`

**Título en instructivo:** `SECCIÓN 4 - DIAGNÓSTICO DE LA POBLACIÓN AFECTADA E INVOLUCRADOS`

### Contenido (instructivo — Gráfico 6)

1. **4.01** Descripción de la población afectada
2. **4.02** Caracterización de la población afectada
3. **4.03** Matriz de involucrados

**Regla de ejemplos de este documento:** todo bloque de ejemplo es el **objeto `campo` completo** tal como aparece en `JSON EJEMPLO.json`.

---

# 4.01 Descripción de la población afectada

Según el instructivo, la **población afectada** es el conjunto de individuos afectados por la problemática del Servicio de Cuidado Diurno, por carencia de acceso (cobertura) o porque lo reciben de forma inadecuada (calidad).

Además debe presentarse la **tasa de crecimiento** de la población del área de influencia del CIAI (se usará luego para proyectar la demanda).

**Nota del JSON:** Importante: recoger información en estudio de campo.

### Tipos de población (instructivo + filas del JSON)

El instructivo define la cadena de población así (Ilustración 1 / texto):

1. **Población Total** — población del área de influencia (distritos de `2.02`). Fuente: INEI.
2. **Población de Referencia** — proporción de la población total en el grupo etario **6 a 36 meses** que podría recibir el SCD del PNCM. Fuente: INEI. Si el área es un centro poblado, estimar como proporción distrital × población del centro poblado.
3. **Población Demandante Potencial** — (definición en **prosa** del instructivo) segmento de la población de referencia **afectada por el problema** (no accede al SCD o accede inadecuadamente). Fuente: estadísticas UT PNCM / encuestas del formulador.
4. **Población Demandante Efectiva** — (definición en **prosa** del instructivo) segmento de la demandante potencial que **cumple criterios de focalización** del PNCM. Fuente: UT PNCM.
5. **Población Objetivo** — parte de la demandante efectiva que el proyecto puede atender de forma integral; la meta la determina el PNCM.

### Diferencia importante (prosa vs tabla del instructivo / JSON)

En la **tabla de ejemplo del instructivo** y en las **descripciones precargadas del JSON**, el orden de las filas 3 y 4 intercambia las etiquetas respecto a la prosa:

- Fila 3 del JSON describe: *segmento de la población de referencia que cumple criterios de focalización* (en la tabla impresa del instructivo aparece bajo el rótulo “Población Demandante Potencial”).
- Fila 4 del JSON describe: *segmento … que busca atención … y accede de forma inadecuada o no accede* (en la tabla impresa aparece bajo “Población Demandante Efectiva”).

**No se resuelve aquí cuál nomenclatura es la “correcta”.** Para autollenado: respetar el **texto de `Descripción` precargado en cada fila del JSON** y completar `Cantidad` / `%` según la fuente de verdad del proyecto. No inventar un cruce distinto.

En el JSON EJEMPLO, la columna `Tipo de población` (UUID) está vacía en las filas de datos; la identificación práctica de cada fila se hace por su `Descripción` precargada.

---

## Campo 4.01.01 — Descripción de la población afectada

**Tipo:** tabla

**Editable:** Sí

**Configuración:** `filas` planas, `columnas` fijas, `agrupador` true, `agrupador_abarca_columnas` 6, `filas_base` 6.

### Cabecera

```
Tipo de población
├── Descripción
└── Tipo de población
%
└── % (con subcolumnas parte1 / parte2)
```

### Columnas (ids reales del JSON)

| id | Nombre | Tipo | Notas |
|---|---|---|---|
| `a1cbdf49-804b-4429-99ca-7e5abe92bc2b` | Tipo de población | texto_corto | En filas de datos del EJEMPLO suele ir vacío; en el agrupador de tasa lleva el título |
| `c2b05ce1-7f6c-42b8-91ca-7a74f2751858` | Descripción | texto_corto | Texto definitorio precargado por tipo |
| `dc2bd721-03ca-4e86-be68-d31f89820629` | Unidad de medida | texto_corto | Persona / Niños y niñas / Tasa |
| `2cf1bfda-e4fa-45ac-900f-60eaa88fabe3` | Cantidad | texto_corto | Valor a completar (en EJEMPLO es string numérico o porcentaje) |
| `f1281037-1b23-48d7-8706-a6082da6a6ec` | % | texto_corto con `subcolumnas` `parte1`/`parte2` | Puede ser string libre (fila 1) u objeto `{parte1, parte2}` |

### Forma del valor (agrupador)

El `valor` no es un array plano de filas: tiene bloques con `agrupador` + `valores[]`.

- **Bloque 1:** agrupador con `nombre` vacío; `valores` = 5 filas de tipos de población.
- **Bloque 2:** agrupador `Tasa de crecimiento de la población del área de influencia` con la cantidad en `agrupador.valores` (y `valores: []`).

La columna `%` usa **subcolumnas** (`parte1`, `parte2`) en varias filas (porcentaje partido en dos celdas Excel).

**Regla de llenado:** completar cantidades (y porcentajes cuando aplique). Conservar descripciones/unidades precargadas. La tasa de crecimiento va en el segundo bloque agrupador.

**Fuentes (instructivo):** INEI (total/referencia); UT PNCM / trabajo de campo (demandante); PNCM (meta objetivo).

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "4.01.01",
  "nombre": "Descripción de la población afectada",
  "tipo_nodo": "campo",
  "tipo": "tabla",
  "editable": true,
  "config": {
    "filas": "planas",
    "columnas": "fijas",
    "agrupador": true,
    "abarca_filas": 1,
    "agrupador_abarca_columnas": 6
  },
  "captura": {
    "columna_inicial": "B",
    "fila_inicial": 8,
    "filas_base": 6,
    "columnas": [
      {
        "id": "a1cbdf49-804b-4429-99ca-7e5abe92bc2b",
        "columna": "B",
        "abarca_columnas": 2
      },
      {
        "id": "c2b05ce1-7f6c-42b8-91ca-7a74f2751858",
        "columna": "D",
        "abarca_columnas": 4
      },
      {
        "id": "dc2bd721-03ca-4e86-be68-d31f89820629",
        "columna": "H",
        "abarca_columnas": 1
      },
      {
        "id": "2cf1bfda-e4fa-45ac-900f-60eaa88fabe3",
        "columna": "I",
        "abarca_columnas": 1
      },
      {
        "id": "f1281037-1b23-48d7-8706-a6082da6a6ec",
        "columna": "J",
        "abarca_columnas": 2,
        "subcolumnas": [
          {
            "id": "parte1",
            "columna": "J",
            "abarca_columnas": 1
          },
          {
            "id": "parte2",
            "columna": "K",
            "abarca_columnas": 1
          }
        ]
      }
    ]
  },
  "cabecera": [
    {
      "titulo": "Tipo de población",
      "hijos": [
        "c2b05ce1-7f6c-42b8-91ca-7a74f2751858",
        "a1cbdf49-804b-4429-99ca-7e5abe92bc2b"
      ]
    },
    {
      "titulo": "%",
      "hijos": [
        "f1281037-1b23-48d7-8706-a6082da6a6ec"
      ]
    }
  ],
  "columnas": [
    {
      "id": "a1cbdf49-804b-4429-99ca-7e5abe92bc2b",
      "nombre": "Tipo de población",
      "tipo": "texto_corto"
    },
    {
      "id": "c2b05ce1-7f6c-42b8-91ca-7a74f2751858",
      "nombre": "Descripción",
      "tipo": "texto_corto"
    },
    {
      "id": "dc2bd721-03ca-4e86-be68-d31f89820629",
      "nombre": "Unidad de medida",
      "tipo": "texto_corto"
    },
    {
      "id": "2cf1bfda-e4fa-45ac-900f-60eaa88fabe3",
      "nombre": "Cantidad",
      "tipo": "texto_corto"
    },
    {
      "id": "f1281037-1b23-48d7-8706-a6082da6a6ec",
      "nombre": "%",
      "tipo": "texto_corto",
      "subcolumnas": [
        {
          "id": "parte1",
          "nombre": " ",
          "tipo": "texto_corto"
        },
        {
          "id": "parte2",
          "nombre": " ",
          "tipo": "texto_corto"
        }
      ]
    }
  ],
  "valor": [
    {
      "agrupador": {
        "inicia": "a1cbdf49-804b-4429-99ca-7e5abe92bc2b",
        "abarca_columnas": 2,
        "nombre": "",
        "valores": {}
      },
      "valores": [
        {
          "a1cbdf49-804b-4429-99ca-7e5abe92bc2b": "",
          "c2b05ce1-7f6c-42b8-91ca-7a74f2751858": "Es la población total del área de influencia definida en el diagnóstico. Se debe considerar la información de la población de los distritos identificados en el punto Localización del área de influencia del proyecto. ",
          "dc2bd721-03ca-4e86-be68-d31f89820629": "Persona",
          "2cf1bfda-e4fa-45ac-900f-60eaa88fabe3": "112536",
          "f1281037-1b23-48d7-8706-a6082da6a6ec": "Población del distrito de San Sebastián, al año 2023. Fuente: INEI"
        },
        {
          "a1cbdf49-804b-4429-99ca-7e5abe92bc2b": "",
          "c2b05ce1-7f6c-42b8-91ca-7a74f2751858": "Es la proporción de la población total, vinculada con el objetivo central del proyecto de inversión, es decir, que está dentro del grupo etario (de 6 a 36 meses) que podría recibir el Servicio de Cuidado Diurno, que brinda el PNCM. Para su estimación, se considera la población de dicho rango de edad, de la población total. ",
          "dc2bd721-03ca-4e86-be68-d31f89820629": "Niños y niñas",
          "2cf1bfda-e4fa-45ac-900f-60eaa88fabe3": "5788",
          "f1281037-1b23-48d7-8706-a6082da6a6ec": {
            "parte1": "",
            "parte2": "de la población total"
          }
        },
        {
          "a1cbdf49-804b-4429-99ca-7e5abe92bc2b": "",
          "c2b05ce1-7f6c-42b8-91ca-7a74f2751858": "Es el segmento de la población de referencia, que cumple con los criterios de focalización que utiliza el PNCM. ",
          "dc2bd721-03ca-4e86-be68-d31f89820629": "Niños y niñas",
          "2cf1bfda-e4fa-45ac-900f-60eaa88fabe3": "850",
          "f1281037-1b23-48d7-8706-a6082da6a6ec": {
            "parte1": "",
            "parte2": "de la población de referencia"
          }
        },
        {
          "a1cbdf49-804b-4429-99ca-7e5abe92bc2b": "",
          "c2b05ce1-7f6c-42b8-91ca-7a74f2751858": "Es el segmento de la población demandante potencial, que busca atención del SCD en un CIAI y accede de forma inadecuada o no accede al servicio, es decir, que es afectada por el problema central.",
          "dc2bd721-03ca-4e86-be68-d31f89820629": "Niños y niñas",
          "2cf1bfda-e4fa-45ac-900f-60eaa88fabe3": "87",
          "f1281037-1b23-48d7-8706-a6082da6a6ec": {
            "parte1": "",
            "parte2": "de la población demandante potencial"
          }
        },
        {
          "a1cbdf49-804b-4429-99ca-7e5abe92bc2b": "",
          "c2b05ce1-7f6c-42b8-91ca-7a74f2751858": "Es aquella parte de la población demandante efectiva que el proyecto está en condiciones de atender de forma integral en un CIAI, considerando la política de focalización del Sector, la definición de la meta correspondiente o alguna restricción que imposibilite la atención del total de la población demandante potencial. La población objetivo es determinada por el PNCM",
          "dc2bd721-03ca-4e86-be68-d31f89820629": "Niños y niñas",
          "2cf1bfda-e4fa-45ac-900f-60eaa88fabe3": "60",
          "f1281037-1b23-48d7-8706-a6082da6a6ec": {
            "parte1": "",
            "parte2": "de la población demandante efectiva"
          }
        }
      ]
    },
    {
      "agrupador": {
        "inicia": "a1cbdf49-804b-4429-99ca-7e5abe92bc2b",
        "abarca_columnas": 2,
        "nombre": "Tasa de crecimiento de la población del área de influencia",
        "valores": {
          "a1cbdf49-804b-4429-99ca-7e5abe92bc2b": "Tasa de crecimiento de la población del área de influencia",
          "c2b05ce1-7f6c-42b8-91ca-7a74f2751858": "",
          "dc2bd721-03ca-4e86-be68-d31f89820629": "Tasa",
          "2cf1bfda-e4fa-45ac-900f-60eaa88fabe3": "1.10%",
          "f1281037-1b23-48d7-8706-a6082da6a6ec": ""
        }
      },
      "valores": []
    }
  ]
}
```

---

# 4.02 Caracterización de la población afectada

Según el instructivo, presentar una breve caracterización de la población demandante potencial, incidiendo en variables de desarrollo infantil temprano:

- Edad
- Género
- Niños y niñas con desnutrición
- Niños y niñas con anemia
- Niños y niñas que acceden a Servicio de Cuidado Diurno
- Niños y niñas que acceden a Servicio Alimentario
- Niños y niñas con adecuado nivel de desarrollo cognitivo, en base a su edad
- Niños y niñas que cumplen con su control CRED

**Fuentes posibles (instructivo):** estadísticas del CIAI; establecimiento de salud; INEI; estudios territoriales; trabajo de campo (encuestas, entrevistas, talleres); otras fuentes confiables. Referencia nutricional citada: Sistema de Información del Estado Nutricional en EESS (INS).

### Observación de naming

- El **campo JSON** se llama: *Niños y niñas que comprenden la población demandante potencial*.
- El **encabezado del ejemplo impreso** del instructivo dice: *… población demandante efectiva*.
- La columna de porcentaje del schema se llama: *% respecto a la población demandante efectiva*.

Documentar ambos; al llenar, seguir los nombres/columnas del JSON.

---

## Campo 4.02.01 — Niños y niñas que comprenden la población demandante potencial

**Tipo:** tabla (jerárquica)

**Editable:** Sí

**Configuración:** `filas` jerarquicas, `columnas` fijas, `agrupador` false, `filas_base` 10.

### Niveles

| id | Nombre | Tipo |
|---|---|---|
| `variable` | Variables /indicadores | texto_corto |
| `categoria` | Categorías | texto_corto |
| `ninos` | Número de niños | numero |
| `ninas` | Número de niñas | numero |
| `porcentaje` | % respecto a la población demandante efectiva | decimal |
| `fuente` | Fuente | texto_corto |

**Variables precargadas:** Edad (categorías 6–12, 13–24, 25–36 meses); Género; desnutrición; anemia; acceso a SCD; acceso a Servicio Alimentario; desarrollo cognitivo adecuado; control CRED.

**Regla de llenado:** completar `ninos`, `ninas`, `porcentaje` (si aplica) y `fuente` por categoría. Conservar la jerarquía `variable → categoria → …`.

### Observación ESTRUCTURA vs EJEMPLO

En el JSON EJEMPLO, `porcentaje` aparece vacío (`""`) en todas las hojas aunque el instructivo impreso muestra porcentajes. Priorizar completar el porcentaje si la fuente de verdad lo aporta.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "4.02.01",
  "nombre": "Niños y niñas que comprenden la población demandante potencial",
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
    "fila_inicial": 22,
    "filas_base": 10,
    "columnas": [
      {
        "id": "variable",
        "columna": "B",
        "abarca_columnas": 3
      },
      {
        "id": "categoria",
        "columna": "E",
        "abarca_columnas": 2
      },
      {
        "id": "ninos",
        "columna": "G",
        "abarca_columnas": 1
      },
      {
        "id": "ninas",
        "columna": "H",
        "abarca_columnas": 1
      },
      {
        "id": "porcentaje",
        "columna": "I",
        "abarca_columnas": 1
      },
      {
        "id": "fuente",
        "columna": "J",
        "abarca_columnas": 2
      }
    ]
  },
  "cabecera": [],
  "niveles": [
    {
      "id": "variable",
      "nombre": "Variables /indicadores",
      "tipo": "texto_corto"
    },
    {
      "id": "categoria",
      "nombre": "Categorías",
      "tipo": "texto_corto"
    },
    {
      "id": "ninos",
      "nombre": "Número de niños",
      "tipo": "numero"
    },
    {
      "id": "ninas",
      "nombre": "Número de niñas",
      "tipo": "numero"
    },
    {
      "id": "porcentaje",
      "nombre": "% respecto a la población demandante efectiva",
      "tipo": "decimal"
    },
    {
      "id": "fuente",
      "nombre": "Fuente",
      "tipo": "texto_corto"
    }
  ],
  "valor": [
    {
      "variable": "Edad",
      "hijos": [
        {
          "categoria": "de 6 a 12 meses",
          "hijos": [
            {
              "ninos": "10",
              "hijos": [
                {
                  "ninas": "10",
                  "hijos": [
                    {
                      "porcentaje": "",
                      "hijos": [
                        {
                          "fuente": "Estadísticas del CIAI"
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
          "categoria": "de 13 a 24 meses",
          "hijos": [
            {
              "ninos": "10",
              "hijos": [
                {
                  "ninas": "10",
                  "hijos": [
                    {
                      "porcentaje": "",
                      "hijos": [
                        {
                          "fuente": "Estadísticas del CIAI"
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
          "categoria": "de 25 a 36 meses",
          "hijos": [
            {
              "ninos": "10",
              "hijos": [
                {
                  "ninas": "10",
                  "hijos": [
                    {
                      "porcentaje": "",
                      "hijos": [
                        {
                          "fuente": "Estadísticas del CIAI"
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
      "variable": "Género",
      "hijos": [
        {
          "categoria": "",
          "hijos": [
            {
              "ninos": "30",
              "hijos": [
                {
                  "ninas": "30",
                  "hijos": [
                    {
                      "porcentaje": "",
                      "hijos": [
                        {
                          "fuente": "Estadísticas del CIAI"
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
      "variable": "Niños y niñas con desnutrición",
      "hijos": [
        {
          "categoria": "Cantidad de niños y niñas",
          "hijos": [
            {
              "ninos": "20",
              "hijos": [
                {
                  "ninas": "25",
                  "hijos": [
                    {
                      "porcentaje": "",
                      "hijos": [
                        {
                          "fuente": "Estadísticas del CIAI"
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
      "variable": "Niños y niñas con anemia",
      "hijos": [
        {
          "categoria": "Cantidad de niños y niñas",
          "hijos": [
            {
              "ninos": "25",
              "hijos": [
                {
                  "ninas": "27",
                  "hijos": [
                    {
                      "porcentaje": "",
                      "hijos": [
                        {
                          "fuente": "Estadísticas del CIAI"
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
      "variable": "Niños y niñas que acceden a Servicio de Cuidado Diurno en el CIAI",
      "hijos": [
        {
          "categoria": "Cantidad de niños y niñas",
          "hijos": [
            {
              "ninos": "30",
              "hijos": [
                {
                  "ninas": "30",
                  "hijos": [
                    {
                      "porcentaje": "",
                      "hijos": [
                        {
                          "fuente": "Estadísticas del CIAI"
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
      "variable": "Niños y niñas que acceden al Servicio Alimentario en el CIAI",
      "hijos": [
        {
          "categoria": "Cantidad de niños y niñas",
          "hijos": [
            {
              "ninos": "0",
              "hijos": [
                {
                  "ninas": "0",
                  "hijos": [
                    {
                      "porcentaje": "",
                      "hijos": [
                        {
                          "fuente": "Estadísticas del CIAI"
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
      "variable": "Niños y niñas con adecuado nivel de desarrollo cognitivo, en base a su edad",
      "hijos": [
        {
          "categoria": "Cantidad de niños y niñas",
          "hijos": [
            {
              "ninos": "10",
              "hijos": [
                {
                  "ninas": "10",
                  "hijos": [
                    {
                      "porcentaje": "",
                      "hijos": [
                        {
                          "fuente": "Estadísticas del CIAI"
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
      "variable": "Niños y niñas que cumplen con su control CRED",
      "hijos": [
        {
          "categoria": "Cantidad de niños y niñas",
          "hijos": [
            {
              "ninos": "5",
              "hijos": [
                {
                  "ninas": "8",
                  "hijos": [
                    {
                      "porcentaje": "",
                      "hijos": [
                        {
                          "fuente": "Estadísticas del CIAI"
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

# 4.03 Matriz de involucrados

Según el instructivo (`4.03 Diagnóstico de los agentes involucrados`), resumir en la matriz a los agentes del proyecto en cualquiera de las fases del Ciclo de Inversión, para conocer:

- **Posición** frente al PI: Cooperante, Beneficiario, Oponente o Perjudicado
- **Percepción** de la problemática del SCD en la localidad
- **Intereses/expectativas** sobre la solución
- **Disposición/posibilidades de participar**, especialmente en Ejecución y Funcionamiento

La información debe recopilarse con instrumentos objetivos (talleres, entrevistas, grupos focales).

**IMPORTANTE (instructivo):** anexar evidencias (fotografías, listas firmadas, actas de acuerdos, etc.).

---

## Campo 4.03.01 — Matriz de involucrados

**Tipo:** tabla

**Editable:** Sí

**Configuración:** `filas` planas, `columnas` fijas, `agrupador` false, `filas_base` 7.

### Columnas

| id | Nombre | Tipo | Notas |
|---|---|---|---|
| `grupo` | Grupos involucrados | texto_corto | Nombre del actor/institución |
| `posicion` | Posición | texto_corto | Etiquetas: Cooperante, Beneficiario, Oponente, Perjudicado |
| `situacion` | Situación negativa percibida | texto_largo | En el schema aparecen las mismas `etiquetas` que `posicion` (posible desajuste del schema); en el EJEMPLO el valor es texto narrativo libre |
| `intereses` | Intereses o expectativas | texto_largo | |
| `estrategias` | Estrategias del Proyecto de Inversión | texto_largo | |
| `acuerdos` | Acuerdos y compromisos | texto_largo | |

**Regla de llenado:** una fila por grupo involucrado. Elegir `posicion` del catálogo. Redactar el resto según evidencia de participación. Filas vacías de la plantilla pueden quedar en blanco.

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "4.03.01",
  "nombre": "Matriz de involucrados",
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
    "fila_inicial": 38,
    "filas_base": 7,
    "columnas": [
      {
        "id": "grupo",
        "columna": "B",
        "abarca_columnas": 2
      },
      {
        "id": "posicion",
        "columna": "D",
        "abarca_columnas": 1
      },
      {
        "id": "situacion",
        "columna": "E",
        "abarca_columnas": 3
      },
      {
        "id": "intereses",
        "columna": "H",
        "abarca_columnas": 3
      },
      {
        "id": "estrategias",
        "columna": "K",
        "abarca_columnas": 3
      },
      {
        "id": "acuerdos",
        "columna": "N",
        "abarca_columnas": 3
      }
    ]
  },
  "cabecera": [],
  "columnas": [
    {
      "id": "grupo",
      "nombre": "Grupos involucrados",
      "tipo": "texto_corto"
    },
    {
      "id": "posicion",
      "nombre": "Posición",
      "tipo": "texto_corto",
      "etiquetas": [
        "Cooperante",
        "Beneficiario",
        "Oponente",
        "Perjudicado"
      ]
    },
    {
      "id": "situacion",
      "nombre": "Situación negativa percibida",
      "tipo": "texto_largo",
      "etiquetas": [
        "Cooperante",
        "Beneficiario",
        "Oponente",
        "Perjudicado"
      ]
    },
    {
      "id": "intereses",
      "nombre": "Intereses o expectativas",
      "tipo": "texto_largo"
    },
    {
      "id": "estrategias",
      "nombre": "Estrategias del Proyecto de Inversión",
      "tipo": "texto_largo"
    },
    {
      "id": "acuerdos",
      "nombre": "Acuerdos y compromisos",
      "tipo": "texto_largo"
    }
  ],
  "valor": [
    {
      "grupo": "Municipalidad distrital de San Sebastián",
      "posicion": "Cooperante",
      "situacion": "Deficiente servicio de cuidado diurno por inadecuada infraestructura",
      "intereses": "Prevenir ydisminuir los factores que causan la desnulnición ypobreza en las familias en siluación de\nriesgo de su población",
      "estrategias": "Involucrar a la municipalidad en el proceso de seguimiento de compromisos de actores comunales",
      "acuerdos": "Destinar el local, mediante cesión en uso, a favor del PNCM"
    },
    {
      "grupo": "Programa Nacional Cuna Más",
      "posicion": "Cooperante",
      "situacion": "Prevalencia de desnutrición y bajos niveles de desarrollo infantil temprano",
      "intereses": "El desarrolo infantil de niñas yniños menores de 36 meses de edad en zonas en situación de pobreza y pobreza extrema",
      "estrategias": "Ejecutar el proyecto para mejorar la calidad de los servicios existentes y ampliar su cobertura.",
      "acuerdos": "Implementar el proyecto para el mejoramiento del servicio de cuidado diurno"
    },
    {
      "grupo": "Comité de gestión de San Antonio",
      "posicion": "Cooperante",
      "situacion": "Inadecuadas condiciones de infraestructura, mobiliario y equipos en el CIAI",
      "intereses": "Contar con adecuadas condiciones en el CIAI, para brindar el servicio de cuidado diurno",
      "estrategias": "Mantener informado a los usuarios sobre los avances en La ejecución del PI.",
      "acuerdos": "Participar activamente en la fase de Ejecución y Funcionamiento del proyecto"
    },
    {
      "grupo": "Padres y madres de niños y niñas afectados con bajo nivel de Desarrollo Infantil Temprano",
      "posicion": "Beneficiario",
      "situacion": "Bajos niveles de desarrollo cognitivo de sus hijos, además de altos niveles de anemia y desnutrición",
      "intereses": "Mejorar las condiciones nutricionales y el desarrollo psicomotriz de sus hijos",
      "estrategias": "Mantener informado a los usuarios sobre los avances en La ejecución del PI.",
      "acuerdos": "Participar activamente en la fase de Ejecución y Funcionamiento del proyecto"
    },
    {
      "grupo": "",
      "posicion": "",
      "situacion": "",
      "intereses": "",
      "estrategias": "",
      "acuerdos": ""
    },
    {
      "grupo": "",
      "posicion": "",
      "situacion": "",
      "intereses": "",
      "estrategias": "",
      "acuerdos": ""
    },
    {
      "grupo": "",
      "posicion": "",
      "situacion": "",
      "intereses": "",
      "estrategias": "",
      "acuerdos": ""
    }
  ]
}
```

---

## Resumen de acción para autollenado (Sección 04)

| ID | Nombre | Tipo | Editable | Acción sugerida |
|---|---|---|---|---|
| 4.01.01 | Descripción de la población afectada | tabla (agrupador) | Sí | Completar cantidades/%; conservar descripciones |
| 4.02.01 | Caracterización (demandante potencial) | tabla jerárquica | Sí | Completar niños/niñas/fuente (y % si aplica) |
| 4.03.01 | Matriz de involucrados | tabla | Sí | Completar grupos y columnas de la matriz |
