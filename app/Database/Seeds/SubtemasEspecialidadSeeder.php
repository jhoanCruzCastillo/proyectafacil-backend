<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Catálogo PROVISIONAL de subtemas por sector — el cliente todavía no definió la lista real, así
// que estos son plausibles según las tipologías de proyecto de Invierte.pe y los procesos de obra
// pública peruanos, suficientes para probar la pantalla "Temas de especialidad". Cuando llegue la
// lista oficial se reemplaza el arreglo de abajo y se vuelve a correr (es idempotente).
//
// Uso: php spark db:seed SubtemasEspecialidadSeeder
class SubtemasEspecialidadSeeder extends Seeder
{
    /** Subtemas por código de sector. */
    private const SUBTEMAS = [
        'SAL' => [
            'Establecimientos de salud del primer nivel',
            'Hospitales de segundo y tercer nivel',
            'Equipamiento biomédico',
            'Telemedicina y salud digital',
            'Gestión de residuos hospitalarios',
        ],
        'EDU' => [
            'Locales educativos de nivel inicial',
            'Locales educativos de primaria y secundaria',
            'Institutos y escuelas de educación superior',
            'Mobiliario y equipamiento educativo',
            'Infraestructura deportiva escolar',
        ],
        'TYC' => [
            'Carreteras y caminos vecinales',
            'Puentes y obras de arte',
            'Transporte urbano y terminales',
            'Conectividad y banda ancha',
            'Aeródromos y helipuertos',
        ],
        'VYS' => [
            'Agua potable en zonas rurales',
            'Alcantarillado y redes urbanas',
            'Plantas de tratamiento de aguas residuales',
            'Drenaje pluvial',
            'Programas de vivienda social',
        ],
        'DIS' => [
            'Centros de desarrollo infantil temprano',
            'Infraestructura para poblaciones vulnerables',
            'Comedores y programas alimentarios',
            'Centros del adulto mayor',
        ],
        'AGR' => [
            'Riego tecnificado parcelario',
            'Canales de irrigación y bocatomas',
            'Reservorios y represas',
            'Sanidad agraria e inocuidad',
            'Desarrollo productivo y cadenas de valor',
        ],
        'INT' => [
            'Comisarías y dependencias policiales',
            'Escuelas de formación policial',
            'Equipamiento y logística policial',
            'Seguridad ciudadana y videovigilancia',
        ],
        'AMB' => [
            'Gestión integral de residuos sólidos',
            'Recuperación de ecosistemas degradados',
            'Áreas naturales protegidas',
            'Monitoreo de calidad ambiental',
        ],
        'DEF' => [
            'Infraestructura militar',
            'Gestión del riesgo de desastres',
            'Defensas ribereñas y encauzamiento',
            'Equipamiento de las Fuerzas Armadas',
        ],
        'PCM' => [
            'Modernización de la gestión pública',
            'Gobierno digital e interoperabilidad',
            'Sedes y locales institucionales',
            'Fortalecimiento de capacidades',
        ],
        'CUL' => [
            'Puesta en valor de sitios arqueológicos',
            'Museos y salas de exposición',
            'Bibliotecas y centros culturales',
            'Patrimonio inmaterial',
        ],
        'CET' => [
            'Puesta en valor de recursos turísticos',
            'Infraestructura y servicios turísticos',
            'Desarrollo artesanal',
            'Promoción de comercio exterior',
        ],
        // Los que motivaron el pedido: procesos transversales de obra pública, no tipologías.
        'GEN' => [
            'Liquidación por administración directa',
            'Liquidación por administración indirecta',
            'Liquidación por contrata',
            'Saldos de obra',
            'Adicionales y deductivos de obra',
            'Ampliaciones de plazo',
        ],
    ];

    public function run(): void
    {
        $sectorIdPorCodigo = [];
        foreach ($this->db->table('sectores')->select('id, codigo')->get()->getResultArray() as $f) {
            $sectorIdPorCodigo[$f['codigo']] = (int) $f['id'];
        }

        $ahora = date('Y-m-d H:i:s');
        $insertados = 0;

        foreach (self::SUBTEMAS as $codigo => $nombres) {
            $sectorId = $sectorIdPorCodigo[$codigo] ?? null;
            if ($sectorId === null) {
                echo "Sector '{$codigo}' no existe — se omite (corre SectoresSeeder primero).\n";

                continue;
            }

            foreach ($nombres as $nombre) {
                $yaExiste = $this->db->table('subtemas_especialidad')
                    ->where('sector_id', $sectorId)
                    ->where('nombre', $nombre)
                    ->countAllResults() > 0;
                if ($yaExiste) {
                    continue;
                }

                $this->db->table('subtemas_especialidad')->insert([
                    'sector_id'  => $sectorId,
                    'nombre'     => $nombre,
                    'activo'     => 1,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
                $insertados++;
            }
        }

        echo "Listo — {$insertados} subtemas nuevos insertados.\n";

        $this->marcarSubtemasDemoAsesor1();
    }

    // Marca algunos subtemas para el asesor demo (Pedro Ríos), dentro de los sectores que ya tiene
    // como especialidad — así la pantalla no arranca vacía al probarla.
    private function marcarSubtemasDemoAsesor1(): void
    {
        $pedro = $this->db->table('usuarios')->where('usuario', 'asesor1')->get()->getRowArray();
        if ($pedro === null) {
            return;
        }
        $pedroId = (int) $pedro['id'];

        $sectoresDePedro = array_column(
            $this->db->table('asesor_especialidades')->select('sector_id')->where('usuario_id', $pedroId)->get()->getResultArray(),
            'sector_id',
        );
        if ($sectoresDePedro === []) {
            return;
        }

        $marcados = 0;
        foreach ($sectoresDePedro as $sectorId) {
            // Los 2 primeros subtemas de cada sector suyo — suficiente para ver el estado mixto
            // (sector con algunos subtemas marcados y otros no).
            $subtemas = $this->db->table('subtemas_especialidad')
                ->select('id')
                ->where('sector_id', $sectorId)
                ->orderBy('id', 'ASC')
                ->limit(2)
                ->get()->getResultArray();

            foreach ($subtemas as $s) {
                $yaExiste = $this->db->table('asesor_subtemas')
                    ->where('usuario_id', $pedroId)
                    ->where('subtema_id', (int) $s['id'])
                    ->countAllResults() > 0;
                if ($yaExiste) {
                    continue;
                }

                $this->db->table('asesor_subtemas')->insert([
                    'usuario_id' => $pedroId,
                    'subtema_id' => (int) $s['id'],
                ]);
                $marcados++;
            }
        }

        echo "Listo — {$marcados} subtemas marcados para asesor1 (Pedro Ríos).\n";
    }
}
