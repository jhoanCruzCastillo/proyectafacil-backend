<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Catálogo inicial de contextos globales de IA — los reutilizables por cualquier ficha. El contenido
// es un punto de partida editable desde el panel "Contextos IA"; no pretende ser exhaustivo.
//
// Idempotente por nombre (que además es único en la tabla).
//
// Uso: php spark db:seed ContextosIAGlobalesSeeder
class ContextosIAGlobalesSeeder extends Seeder
{
    private const CONTEXTOS = [
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
    ];

    public function run(): void
    {
        $ahora = date('Y-m-d H:i:s');
        $nuevos = 0;

        foreach (self::CONTEXTOS as $c) {
            $existe = $this->db->table('contextos_ia_globales')->where('nombre', $c['nombre'])->countAllResults() > 0;
            if ($existe) {
                continue;
            }
            $this->db->table('contextos_ia_globales')->insert($c + ['created_at' => $ahora, 'updated_at' => $ahora]);
            $nuevos++;
        }

        echo "Listo — {$nuevos} contextos globales de IA insertados.\n";
    }
}
