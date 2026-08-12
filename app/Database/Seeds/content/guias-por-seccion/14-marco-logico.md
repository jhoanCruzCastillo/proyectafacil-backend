# SECCIÓN N°14: MARCO LÓGICO (de la alternativa seleccionada)

## Descripción de la sección

- **Qué representa:** SECCIÓN N°14: MARCO LÓGICO (de la alternativa seleccionada).
- **Objetivo (según instructivo):** Información no determinada por los archivos proporcionados.
- **Hoja Excel:** `Marco Lógico`
- **JSON `id` de sección:** `18`

### Contexto del instructivo (extracto)

13.01. Impacto ambiental El propósito es identificar los impactos negativos que el proyecto puede generar sobre el ambiente y plantear medidas de gestión ambiental, concerniente a acciones de prevención, corrección y mitigación, de corresponder, acorde con las regulaciones ambientales que sean pertinentes para la fase de Formulación y Evaluación del proyecto. Los costos de implementación de las medidas deben formar parte del costo de inversión del proyecto. Ejemplo: SECCIÓN 14 - MARCO LÓGICO En este punto se presentan las orientaciones para construir el Marco Lógico, que es una herramienta que resume la información esencial de la coherencia y consisten cia de un proyecto. Es importante precisar que se debe desarrollar este punto para la alternativa seleccionada en la Evaluación social. 14.01. Marco Lógico El Marco Lógico se construye a partir de la definición del árbol de objetivos y la propuesta de acciones para alcanzar los medios fundamentales del proyecto de inversión del CIAI. En tal sentido, la columna de objetivos guarda consistencia con los instrume ntos desarrollados en los puntos Definición de los objetivos del proyecto y Costos del proyecto. 13.01 Impacto ambiental COSTO (S/) Se incluye dentro de las actividades previstas en el presupuesto del proyecto Durante el Funcionamiento Ninguna IMPACTOS NEGATIVOS MEDIDAS DE MITIGACIÓN Durante la Ejecución Impacto 1: Generación de ruido y emisión de polvareda, durante el proceso de construcción Se considerará, durante la ejecución de obra, acciones dirigidas a controlar y evitar la generación de ruido y polvo,

**Regla de ejemplos:** cada bloque de ejemplo es el **objeto `campo` completo** del `JSON EJEMPLO.json` correspondiente a esta sección/alternativa.

## Nota sobre alternativa seleccionada (instructivo)

Según el instructivo, este punto se desarrolla **para la alternativa seleccionada** (la elegida tras la evaluación social de las alternativas técnicas), no para las tres en paralelo.

### Subsecciones / grupos

- `18.01` — 14.01 RESUmen del proyecto: matriz del marco lógico

---

# 14.01 RESUmen del proyecto: matriz del marco lógico

---

## Campo 18.01.1 — Matriz del marco lógico

**Tipo:** Tabla.

**Editable:** Sí

**Qué representa:** el campo tal como se nombra en la ficha; su significado operativo se interpreta por el nombre, el tipo y el contexto de la subsección/instructivo.

### Estructura de la tabla (schema)


**Niveles (tabla jerárquica):**

| id | Nombre | Tipo |
|---|---|---|
| `878b1afe-119e-4d25-9561-fd74f8263991` | Supuestos | texto_corto |
| `3859cf7c-0bcb-4db9-9a5e-49b335fb49e3` | Medios de verificación | texto_corto |
| `822d3628-30d5-406b-81ac-bc869d454a23` | Nivel de objetivo | texto_corto |
| `29dfc096-5163-4abe-b88b-f562edbfb491` | Indicador | texto_corto |
| `6a618d31-c9ae-4718-808a-aecd6e3eb503` | Valor | texto_corto |

**Config:** `{"filas": "jerarquicas", "columnas": "fijas", "agrupador": false, "abarca_filas": 1}`

**Ejemplo (objeto campo completo del JSON EJEMPLO):**

```json
{
  "id": "18.01.1",
  "nombre": "Matriz del marco lógico",
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
    "columna_inicial": "B",
    "fila_inicial": 8,
    "filas_base": 19,
    "columnas": [
      {
        "id": "878b1afe-119e-4d25-9561-fd74f8263991",
        "columna": "L",
        "abarca_columnas": 3
      },
      {
        "id": "3859cf7c-0bcb-4db9-9a5e-49b335fb49e3",
        "columna": "I",
        "abarca_columnas": 3
      },
      {
        "id": "822d3628-30d5-406b-81ac-bc869d454a23",
        "columna": "B",
        "abarca_columnas": 3
      },
      {
        "id": "29dfc096-5163-4abe-b88b-f562edbfb491",
        "columna": "E",
        "abarca_columnas": 3
      },
      {
        "id": "6a618d31-c9ae-4718-808a-aecd6e3eb503",
        "columna": "H",
        "abarca_columnas": 1
      }
    ]
  },
  "cabecera": [],
  "niveles": [
    {
      "id": "878b1afe-119e-4d25-9561-fd74f8263991",
      "nombre": "Supuestos",
      "tipo": "texto_corto"
    },
    {
      "id": "3859cf7c-0bcb-4db9-9a5e-49b335fb49e3",
      "nombre": "Medios de verificación",
      "tipo": "texto_corto"
    },
    {
      "id": "822d3628-30d5-406b-81ac-bc869d454a23",
      "nombre": "Nivel de objetivo",
      "tipo": "texto_corto"
    },
    {
      "id": "29dfc096-5163-4abe-b88b-f562edbfb491",
      "nombre": "Indicador",
      "tipo": "texto_corto"
    },
    {
      "id": "6a618d31-c9ae-4718-808a-aecd6e3eb503",
      "nombre": "Valor",
      "tipo": "texto_corto"
    }
  ],
  "valor": [
    {
      "878b1afe-119e-4d25-9561-fd74f8263991": []
    },
    {
      "878b1afe-119e-4d25-9561-fd74f8263991": "",
      "hijos": [
        {
          "3859cf7c-0bcb-4db9-9a5e-49b335fb49e3": "Encuesta de Salud y Desarrollo en la Primera Infancia",
          "hijos": [
            {
              "822d3628-30d5-406b-81ac-bc869d454a23": "",
              "hijos": [
                {
                  "29dfc096-5163-4abe-b88b-f562edbfb491": "Porcentaje de niños/as menores de 36 meses que logran los hitos de desarrollo infantil temprano esperados para su edad, a partir del tercer año del inicio de la fase de funcionamiento",
                  "hijos": [
                    {
                      "6a618d31-c9ae-4718-808a-aecd6e3eb503": "85"
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
      "878b1afe-119e-4d25-9561-fd74f8263991": "",
      "hijos": [
        {
          "3859cf7c-0bcb-4db9-9a5e-49b335fb49e3": "",
          "hijos": [
            {
              "822d3628-30d5-406b-81ac-bc869d454a23": "Propósito",
              "hijos": [
                {
                  "29dfc096-5163-4abe-b88b-f562edbfb491": "",
                  "hijos": [
                    {
                      "6a618d31-c9ae-4718-808a-aecd6e3eb503": ""
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
      "878b1afe-119e-4d25-9561-fd74f8263991": "Padres toman conciencia de la importancia del cuidado y aprendizaje de las niñas y niños para su desarrollo\n\nActores comunales\ncomprometidos en el cuidado y atención a la niña y el niño menor de 3 años",
      "hijos": [
        {
          "3859cf7c-0bcb-4db9-9a5e-49b335fb49e3": "Ficha de seguimiento del Servicio de Cuidado Diurno",
          "hijos": [
            {
              "822d3628-30d5-406b-81ac-bc869d454a23": "",
              "hijos": [
                {
                  "29dfc096-5163-4abe-b88b-f562edbfb491": "Porcentaje de niños y niñas con un mínimo de permanencia de 6 meses atendidos en el CIAI, que cumplen con los estándares de calidad definidos por el PNCM, a partir del primer año del inicio de la fase de funcionamiento",
                  "hijos": [
                    {
                      "6a618d31-c9ae-4718-808a-aecd6e3eb503": "95"
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
      "878b1afe-119e-4d25-9561-fd74f8263991": "",
      "hijos": [
        {
          "3859cf7c-0bcb-4db9-9a5e-49b335fb49e3": "",
          "hijos": [
            {
              "822d3628-30d5-406b-81ac-bc869d454a23": "Componentes",
              "hijos": [
                {
                  "29dfc096-5163-4abe-b88b-f562edbfb491": "",
                  "hijos": [
                    {
                      "6a618d31-c9ae-4718-808a-aecd6e3eb503": ""
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
      "878b1afe-119e-4d25-9561-fd74f8263991": "Se asigna recursos necesarios para el adecuado mantenimiento disponibles durante el horizonte de evaluación",
      "hijos": [
        {
          "3859cf7c-0bcb-4db9-9a5e-49b335fb49e3": "Registro del Formato N° 09 - Registro del Cierre de Inversiones, en el Banco de Inversiones del SNPMGI",
          "hijos": [
            {
              "822d3628-30d5-406b-81ac-bc869d454a23": "",
              "hijos": [
                {
                  "29dfc096-5163-4abe-b88b-f562edbfb491": "Número de m2 de infraestructura del CIAI, implementados con el proyecto, que cumplen con los requerimientos normativos del PNCM, al concluir la fase de ejecución del proyecto",
                  "hijos": [
                    {
                      "6a618d31-c9ae-4718-808a-aecd6e3eb503": ""
                    }
                  ]
                }
              ]
            },
            {
              "822d3628-30d5-406b-81ac-bc869d454a23": "",
              "hijos": [
                {
                  "29dfc096-5163-4abe-b88b-f562edbfb491": "Número de equipos del CIAI, implementados con el proyecto, que cumplen con los requerimientos normativos del PNCM, al concluir la fase de ejecución del proyecto",
                  "hijos": [
                    {
                      "6a618d31-c9ae-4718-808a-aecd6e3eb503": ""
                    }
                  ]
                }
              ]
            },
            {
              "822d3628-30d5-406b-81ac-bc869d454a23": "",
              "hijos": [
                {
                  "29dfc096-5163-4abe-b88b-f562edbfb491": "Número de mobiliarios del CIAI, implementados con el proyecto, que cumplen con los requerimientos normativos del PNCM, al concluir la fase de ejecución del proyecto",
                  "hijos": [
                    {
                      "6a618d31-c9ae-4718-808a-aecd6e3eb503": ""
                    }
                  ]
                }
              ]
            },
            {
              "822d3628-30d5-406b-81ac-bc869d454a23": "",
              "hijos": [
                {
                  "29dfc096-5163-4abe-b88b-f562edbfb491": "Número de sesiones de capacitación desarrolladas, dirigidas de actores comunitarios, en el marco del proyecto, al concluir la fase de ejecución del proyecto",
                  "hijos": [
                    {
                      "6a618d31-c9ae-4718-808a-aecd6e3eb503": ""
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
      "878b1afe-119e-4d25-9561-fd74f8263991": "",
      "hijos": [
        {
          "3859cf7c-0bcb-4db9-9a5e-49b335fb49e3": "",
          "hijos": [
            {
              "822d3628-30d5-406b-81ac-bc869d454a23": "Acciones",
              "hijos": [
                {
                  "29dfc096-5163-4abe-b88b-f562edbfb491": "",
                  "hijos": [
                    {
                      "6a618d31-c9ae-4718-808a-aecd6e3eb503": ""
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
      "878b1afe-119e-4d25-9561-fd74f8263991": "",
      "hijos": [
        {
          "3859cf7c-0bcb-4db9-9a5e-49b335fb49e3": "",
          "hijos": [
            {
              "822d3628-30d5-406b-81ac-bc869d454a23": "Componente 1:",
              "hijos": [
                {
                  "29dfc096-5163-4abe-b88b-f562edbfb491": "…",
                  "hijos": [
                    {
                      "6a618d31-c9ae-4718-808a-aecd6e3eb503": ""
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
      "878b1afe-119e-4d25-9561-fd74f8263991": "Se cumple con la asignación presupuestal oportuna para la ejecución del proyecto.",
      "hijos": [
        {
          "3859cf7c-0bcb-4db9-9a5e-49b335fb49e3": "Recepción y conformidad de construcción, habilitación y/o adecuación de infraestructura del CIAI\n\nRecepción y conformidad de equipamiento adquirido.\n\nRecepción y conformidad de mobiliario adquirido.\n\nConformidad de servicio de capacitación realziado.",
          "hijos": [
            {
              "822d3628-30d5-406b-81ac-bc869d454a23": "Construcción, habilitación y/o adecuación de infraestructura del CIAI",
              "hijos": [
                {
                  "29dfc096-5163-4abe-b88b-f562edbfb491": "Ejecución del componente del proyecto, por un monto de:",
                  "hijos": [
                    {
                      "6a618d31-c9ae-4718-808a-aecd6e3eb503": ""
                    }
                  ]
                }
              ]
            },
            {
              "822d3628-30d5-406b-81ac-bc869d454a23": "Componente 2",
              "hijos": [
                {
                  "29dfc096-5163-4abe-b88b-f562edbfb491": "",
                  "hijos": [
                    {
                      "6a618d31-c9ae-4718-808a-aecd6e3eb503": ""
                    }
                  ]
                }
              ]
            },
            {
              "822d3628-30d5-406b-81ac-bc869d454a23": "Adquisición de equipamiento del CIAI",
              "hijos": [
                {
                  "29dfc096-5163-4abe-b88b-f562edbfb491": "Ejecución del componente del proyecto, por un monto de:",
                  "hijos": [
                    {
                      "6a618d31-c9ae-4718-808a-aecd6e3eb503": ""
                    }
                  ]
                }
              ]
            },
            {
              "822d3628-30d5-406b-81ac-bc869d454a23": "Componente 3",
              "hijos": [
                {
                  "29dfc096-5163-4abe-b88b-f562edbfb491": "",
                  "hijos": [
                    {
                      "6a618d31-c9ae-4718-808a-aecd6e3eb503": ""
                    }
                  ]
                }
              ]
            },
            {
              "822d3628-30d5-406b-81ac-bc869d454a23": "Adquisición de mobiliario del CIAI",
              "hijos": [
                {
                  "29dfc096-5163-4abe-b88b-f562edbfb491": "Ejecución del componente del proyecto, por un monto de:",
                  "hijos": [
                    {
                      "6a618d31-c9ae-4718-808a-aecd6e3eb503": ""
                    }
                  ]
                }
              ]
            },
            {
              "822d3628-30d5-406b-81ac-bc869d454a23": "Componente 4",
              "hijos": [
                {
                  "29dfc096-5163-4abe-b88b-f562edbfb491": "",
                  "hijos": [
                    {
                      "6a618d31-c9ae-4718-808a-aecd6e3eb503": ""
                    }
                  ]
                }
              ]
            },
            {
              "822d3628-30d5-406b-81ac-bc869d454a23": "Capacitación de recursos humanos que operan en el CIAI",
              "hijos": [
                {
                  "29dfc096-5163-4abe-b88b-f562edbfb491": "Ejecución del componente del proyecto, por un monto de:",
                  "hijos": [
                    {
                      "6a618d31-c9ae-4718-808a-aecd6e3eb503": ""
                    }
                  ]
                }
              ]
            },
            {
              "822d3628-30d5-406b-81ac-bc869d454a23": "Implementación de medidas de reducción del riesgo de desastre y mitigación ambiental",
              "hijos": [
                {
                  "29dfc096-5163-4abe-b88b-f562edbfb491": "Ejecución de las medidas, por un monto de:",
                  "hijos": [
                    {
                      "6a618d31-c9ae-4718-808a-aecd6e3eb503": ""
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
| 18.01.1 | Matriz del marco lógico | tabla | Sí | Llenar tabla |
