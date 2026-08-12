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
                'markdown' => "## Marco normativo\nSistema Nacional de Programación Multianual y Gestión de Inversiones (Invierte.pe), Directiva N.° 001-2019-EF/63.01.\n\n## Reglas\n- Usar la terminología oficial del MEF.\n- Distinguir con precisión las fases: Programación Multianual, Formulación y Evaluación, Ejecución y Funcionamiento.\n- La naturaleza de intervención debe ser una de las oficiales: Creación, Ampliación, Mejoramiento, Recuperación o Rehabilitación.",
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
                'markdown' => "## Sector Transportes y Comunicaciones\n- Tipologías frecuentes: carreteras, caminos vecinales, puentes, terminales.\n- El IMD (Índice Medio Diario) es el indicador base de demanda vial.\n\n## Reglas\n- Distinguir entre red vial nacional, departamental y vecinal.",
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
- Los campos tipo tabla (filas dinámicas, agrupadas o jerárquicas) quedan fuera del llenado automático por ahora — su estructura es demasiado específica para generarla a ciegas. Se llenan a mano o con ayuda puntual del asesor de IA, campo por campo.

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
