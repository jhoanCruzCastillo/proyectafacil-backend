<?php

namespace App\Database\Seeds;

use App\Libraries\CloudinaryUploader;
use CodeIgniter\Database\Seeder;

// Catálogo inicial de contextos globales de IA — los reutilizables por cualquier ficha DE CUALQUIER
// SECTOR (a diferencia de los "contextos generales", que son propios de una sola ficha — ver
// ContextosIACuidadoDiurnoSeeder). El contenido es un punto de partida editable desde el panel
// "Contextos IA"; no pretende ser exhaustivo.
//
// El markdown se sube como archivo .md a Cloudinary (ver CloudinaryUploader::subirMarkdown) — la
// fila en BD solo guarda la URL resultante.
//
// Idempotente por nombre (que además es único en la tabla).
//
// Uso: php spark db:seed ContextosIAGlobalesSeeder
class ContextosIAGlobalesSeeder extends Seeder
{
    /** No es `const`: el último contexto lee su markdown de un archivo en tiempo de ejecución (file_get_contents no es una expresión constante válida en PHP). */
    private function contextos(): array
    {
        return [
            [
                'nombre' => 'Invierte.pe',
                'icono'  => 'faLandmark',
                // Enriquecido (2026-09-28) con la "Guía General para la Identificación, Formulación
                // y Evaluación de Proyectos de Inversión" (MEF/DGPMI, cuarta publicación, octubre
                // 2024) — Anexo 1 (naturalezas de intervención y denominación de un PI) y Anexo 8
                // (glosario). Antes era un stub de 4 líneas sin definiciones reales; el detalle
                // normativo profundo vivía duplicado dentro del contexto específico de cada ficha
                // (ver fte-ebr-contexto-general.md) en vez de estar centralizado acá. Este contexto
                // aplica a CUALQUIER ficha/sector — el detalle propio de cada ficha (límites de
                // inversión, activos estratégicos del sector) sigue viviendo en su "Contexto general".
                'markdown' => "## Marco normativo\nSistema Nacional de Programación Multianual y Gestión de Inversiones (Invierte.pe), Directiva N.° 001-2019-EF/63.01. Fases del Ciclo de Inversión: Programación Multianual, Formulación y Evaluación, Ejecución, Funcionamiento.\n\n## Naturalezas de intervención de un Proyecto de Inversión (PI) — son EXACTAMENTE estas 4, ninguna otra\n- **Creación**: dotar del bien o servicio en áreas donde NO existe una Unidad Productora (UP) — incrementa cobertura partiendo de cero.\n- **Mejoramiento**: intervenciones sobre una UP existente orientadas a cumplir el nivel de servicio y/o los estándares de calidad de los factores de producción establecidos por el Sector — presta servicios de mayor calidad a usuarios que ya disponen de él.\n- **Ampliación**: incrementa la capacidad de una UP existente para proveer el bien/servicio a NUEVOS usuarios — incrementa cobertura.\n- **Recuperación**: recupera la capacidad de prestación del bien/servicio en una UP existente cuyos factores de producción colapsaron o fueron dañados/destruidos.\nCada ficha técnica estándar admite solo un subconjunto de estas 4 (revisa el \"Contexto general\" propio de la ficha) — nunca asumas que las 4 aplican solo porque son las oficiales del marco normativo.\n\n## Denominación de un proyecto de inversión\nSiempre 3 elementos: **Naturaleza** (qué se va a hacer) + **Objeto de la intervención** (el bien/servicio y el nombre de la UP) + **Localización** (dónde se ubica la UP — centro poblado/distrito/provincia/departamento). Ejemplo oficial: \"Mejoramiento [naturaleza] del servicio de transitabilidad vial de la carretera vecinal PA 627 [objeto] del distrito de Chontabamba, de la provincia de Oxapampa, del departamento de Pasco [localización]\".\n\n## Glosario (Anexo 8 de la Guía General MEF/DGPMI)\n- **Unidad Productora (UP)**: conjunto de recursos o factores productivos (infraestructura, equipos, personal, organización, capacidades de gestión) que, articulados entre sí, tienen la capacidad de proveer bienes o servicios a la población objetivo.\n- **Brecha**: diferencia entre la oferta disponible optimizada (incluye infraestructura natural) y la demanda, a una fecha y ámbito geográfico determinado — se expresa en cantidad (cobertura) y/o calidad (condiciones del acceso al servicio).\n- **Factor de producción**: recurso tangible o intangible que usa una UP para producir el servicio (infraestructura física, equipo, mobiliario, terrenos, capacidades de gestión, personal, materiales, insumos).\n- **Horizonte de evaluación**: periodo para evaluar beneficios y costos de un PI — ejecución + funcionamiento.\n- **Precio social**: parámetro de evaluación que refleja el costo de oportunidad para la sociedad de usar un bien, servicio o factor de producción (distinto del precio de mercado).\n- **Meta física**: cantidad de activo generado o modificado por la inversión.\n- **Alternativa de solución**: opción que resulta del análisis de los medios fundamentales para lograr el objetivo central del PI. **Alternativa técnica**: opción del análisis de localización, tamaño y tecnología de una alternativa de solución.\n\n## Reglas\n- Usar la terminología oficial del MEF, nunca sinónimos imprecisos (\"obra\" en vez de \"proyecto de inversión\", \"sede\" en vez de \"Unidad Productora\").\n- Distinguir con precisión las fases del Ciclo de Inversión.\n- No confundir naturaleza de intervención con \"naturaleza de la acción\" (esta última es sobre UN activo específico: construcción, reforzamiento, remodelación, reparación integral o parcial).",
            ],
            [
                'nombre' => 'Finanzas',
                'icono'  => 'faFileInvoice',
                'markdown' => "## Criterios financieros\n- Los montos van en soles (S/) y a precios de mercado, salvo que la sección pida precios sociales.\n- Verificar consistencia entre el costo de inversión, el cronograma y los costos de operación y mantenimiento.\n\n## Reglas\n- No redondear cifras oficiales.\n- Si un monto no cuadra con su desagregado, advertirlo en vez de corregirlo por cuenta propia.",
            ],
            [
                'nombre' => 'Sociales',
                'icono'  => 'faHandHoldingHeart',
                'markdown' => "## Enfoque social\n- Caracterizar la población afectada con datos verificables (INEI, censos, encuestas propias fechadas).\n- Incluir enfoque de género e interculturalidad cuando la población lo amerite.\n\n## Reglas\n- Toda cifra poblacional debe citar su fuente y su año.",
            ],
            [
                'nombre' => 'Transporte',
                'icono'  => 'faRoad',
                // Enriquecido (2026-09-28) con el "Instructivo de la Ficha Técnica Estándar para la
                // Formulación y Evaluación de Proyectos de Inversión en Carreteras Interurbanas"
                // (MTC, julio 2023, V.2) — Orientaciones Generales y Siglas y Abreviaturas. Contenido
                // TRANSVERSAL a cualquier ficha del sector Transportes (FTE-CARRETERAS, FTS-MTC);
                // los límites de inversión, activos estratégicos y el umbral EXACTO de la metodología
                // de evaluación de UNA ficha puntual van en su "Contexto general" (ver
                // fte-carreteras-contexto-general.md), no acá — revisado 2026-09-28 para evitar que
                // el mismo párrafo (jerarquía vial, siglas, 200 veh/día) viajara duplicado en cada
                // llamada a la IA de FTE-CARRETERAS (el "Contexto general" de la ficha y este global
                // se inyectan JUNTOS en el mismo prompt, nunca uno en reemplazo del otro).
                'markdown' => "## Sector Transportes y Comunicaciones (MTC)\n- Tipologías frecuentes: carreteras interurbanas, caminos vecinales, puentes, terminales terrestres.\n- El IMD/IMDA (Índice Medio Diario / Índice Medio Diario Anual) es el indicador base de demanda vial — se obtiene de un conteo y clasificación vehicular en campo (ver Anexo N.° 01: Pautas para el Estudio de Tráfico de cada instructivo).\n\n## Jerarquía vial (SINAC) — no confundir los tres niveles\n- **Red Vial Nacional (RVN)**: los principales ejes longitudinales y transversales del país; recibe a las redes departamental y vecinal.\n- **Red Vial Departamental o Regional (RVD)**: circunscrita al ámbito de un Gobierno Regional; articula la RVN con la RVV.\n- **Red Vial Vecinal o Rural (RVV)**: circunscrita al ámbito local; articula capitales de provincia/distrito, centros poblados y las redes nacional/departamental.\n\n## Metodología de evaluación social — criterio general\nLa metodología (Costo-Eficiencia o Beneficio-Costo) depende del volumen de tráfico proyectado (IMDA) — el umbral exacto que separa una de otra varía por ficha; revisa el \"Contexto general\" de la ficha en curso para el valor vigente, nunca lo asumas desde acá.\n\n## Siglas y abreviaturas frecuentes del sector\nAE (Activo Estratégico), CUI (Código Único de Inversiones), DGPMI, FTE (Ficha Técnica Estándar), GL/GR (Gobierno Local/Regional), MTC, OPMI, PMI, RENAC (Registro Nacional de Carreteras), RVD/RVN/RVV, SINAC (Sistema Nacional de Carreteras), SNPMGI, UEI, UF, UP.\n\n## Reglas\n- Distinguir entre red vial nacional, departamental y vecinal — determina el Grupo Funcional, el límite máximo de inversión y a veces la metodología de evaluación aplicable.\n- El código RENAC identifica a la Unidad Productora (la vía) — nunca se inventa ni se aproxima si la fuente de la verdad no lo trae.\n- Todo dato de tráfico (IMD, conteo vehicular, ejes equivalentes) debe salir de un estudio de campo real citado en la fuente de la verdad, nunca de un valor típico o de un ejemplo genérico del instructivo.",
            ],
            [
                'nombre' => 'Educación',
                'icono'  => 'faGraduationCap',
                'markdown' => "## Sector Educación\n- Normas técnicas de infraestructura educativa del MINEDU.\n- La brecha se expresa habitualmente como porcentaje de locales educativos en condiciones inadecuadas.\n\n## Reglas\n- Diferenciar los niveles: inicial, primaria y secundaria.",
            ],
            [
                'nombre' => 'Estructura de datos — Fichas técnicas',
                'icono'  => 'faFileInvoice',
                // Copia íntegra de la documentación de Notion "DOCUMENTACIÓN, CONTEXTO PARA LA IA" —
                // cómo se organiza internamente CUALQUIER ficha de este sistema (convención de nodos
                // JSON, volcado a Excel, protección de celdas calculadas). Es tan larga que vive en su
                // propio archivo en vez de un heredoc inline. Sirve de base para los contextos de
                // sección, que sí hablan del dominio (CIAI, transporte, etc.) — asociar este contexto a
                // cualquier sección que lo necesite.
                'markdown' => file_get_contents(__DIR__ . '/content/estructura-datos-fichas-tecnicas.md'),
            ],
            [
                'nombre' => 'Reglas de llenado automático con IA',
                'icono'  => 'faWandMagicSparkles',
                // Reglas de comportamiento para el LLENADO AUTOMÁTICO (el cliente carga su "fuente de
                // la verdad" — PDF/TXT/MD + texto libre sobre su proyecto real — y la IA llena la ficha
                // completa). Es transversal a cualquier sector: se asocia siempre, junto con los
                // contextos de cada sección (que sí explican el dominio) y "Estructura de datos" (que
                // explica la forma). Este contexto explica el CRITERIO al llenar, no el contenido ni la
                // estructura.
                'markdown' => <<<'MD'
## Qué es el llenado automático
El cliente carga una "fuente de la verdad" (uno o más documentos PDF/TXT/MD y opcionalmente texto libre) que describe SU proyecto real. A partir de eso, y de los contextos de cada sección, se llena automáticamente toda la ficha técnica — campo por campo, no como conversación.

## La única fuente de datos es la fuente de la verdad
- Todo valor que se proponga debe poder rastrearse a algo que el cliente escribió o subió. Los contextos de sección y "Estructura de datos" explican CÓMO interpretar y dónde va cada dato — nunca son ellos mismos la fuente del dato.
- Los ejemplos ya resueltos (ejemplos de referencia) sirven solo como guía de **formato y estilo de redacción** — cómo se ve una respuesta bien escrita para ese campo. Nunca se copia su contenido real (nombres, cifras, ubicaciones) a la ficha del cliente, salvo que la fuente de la verdad del cliente diga exactamente eso.
- Si un campo no tiene con qué llenarse en la fuente de la verdad, se deja vacío. Nunca se inventa, aproxima ni "completa razonablemente" un dato ausente — un campo vacío es honesto; un dato inventado no lo es y puede terminar en un documento oficial.

## Qué campos no se llenan
- Nunca proponer valor para un campo que la ficha marca como calculado por el Excel (fórmula propia) — se resuelve solo a partir de los demás campos. Si no es evidente si un campo es calculado, mejor omitirlo que arriesgar un valor que de todas formas el Excel va a ignorar.
- Las tablas (filas dinámicas, agrupadas o jerárquicas) SÍ se llenan con IA, pero por un flujo aparte al de los campos de texto (una tabla a la vez, botón "Llenar con IA" del propio campo). La forma exacta — cantidad de filas, columnas o nodos — la exige y valida el sistema, no el modelo: nunca agregues, quites ni reordenes filas/columnas/nodos, solo completa los valores de las celdas vacías o corrígelas si hay evidencia mejor. Las columnas marcadas como calculadas (fórmula propia del Excel, ej. las que resuelven UBIGEO a Departamento/Provincia/Distrito) nunca se llenan, igual que un campo calculado fuera de tabla.

## Formato de cada valor
- Fechas, porcentajes y montos se escriben tal como se leerían en la hoja ("1.10%", "S/ 1,234"), nunca como el número crudo interno.
- Para campos de selección o catálogo (una lista cerrada de opciones), usar exactamente el texto de una opción válida si se conoce con certeza cuál aplica; si hay duda entre varias, dejarlo vacío en vez de elegir una al azar.
- Texto libre: redactar en español, en el registro formal de un documento técnico oficial — ni telegráfico ni conversacional.

## Cuando la fuente de la verdad no alcanza para una sección entera
Si ningún dato de la fuente de la verdad aplica a una sección completa, esa sección se deja tal como estaba (vacía o con lo que ya tenía) — no se rellena con contenido genérico solo para que "se vea completa".
MD,
            ],
        ];
    }

    public function run(): void
    {
        $cloudinary = new CloudinaryUploader();
        $ahora = date('Y-m-d H:i:s');
        $nuevos = 0;

        foreach ($this->contextos() as $c) {
            $existente = $this->db->table('contextos_ia_globales')->where('nombre', $c['nombre'])->get()->getRowArray();
            $url = $cloudinary->subirMarkdown($c['markdown'], "global-{$c['nombre']}.md");
            $fila = ['nombre' => $c['nombre'], 'icono' => $c['icono'], 'url' => $url];

            if ($existente === null) {
                $this->db->table('contextos_ia_globales')->insert($fila + ['created_at' => $ahora, 'updated_at' => $ahora]);
                $nuevos++;
            } else {
                $this->db->table('contextos_ia_globales')->where('id', $existente['id'])->update($fila + ['updated_at' => $ahora]);
            }
        }

        echo "Listo — {$nuevos} contextos globales de IA insertados (los ya existentes se actualizaron).\n";
    }
}
