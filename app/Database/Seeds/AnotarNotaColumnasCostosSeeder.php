<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// Agrega `nota` (aclaración de unidad que no se deduce del nombre de columna) a las columnas de
// costo/cronograma de FTE-CUIDADO-DIURNO donde el llenado con IA venía fallando en silencio:
//   - 09.03.3 / 09.03.5 (O&M sin/con proyecto), columna "costo": es ANUAL, no mensual.
//   - 09.04.4 / 09.04.6 (cronograma de inversión), columnas "tri_1".."tri_8": es MONTO en soles de
//     ese trimestre, no el % del cronograma.
// Ver la propuesta de llenado híbrido (Franja B) — este es el mismo ajuste que ya se aplicó a
// data/plantillas_estructura.json (para nuevos entornos que se siembren desde cero); este seeder
// existe para actualizar un entorno donde la plantilla YA tiene asignado_archivo_id (caso en el que
// EstructuraPlantillasSeeder se salta por diseño, ver su propio comentario).
//
// Idempotente: si la columna ya tiene `nota`, no la vuelve a escribir.
// Uso: php spark db:seed AnotarNotaColumnasCostosSeeder
class AnotarNotaColumnasCostosSeeder extends Seeder
{
    private const CODIGO_PLANTILLA = 'FTE-CUIDADO-DIURNO';

    private const NOTA_COSTO_ANUAL = 'Costo ANUAL en soles (S/), no mensual. Si la fuente de la verdad '
        . 'trae el monto por mes (ej. "S/510 al mes"), multiplícalo ×12 antes de escribirlo aquí — esta '
        . 'tabla está etiquetada "Costos anual" en el Excel real.';

    private const NOTA_TRIMESTRE_MONTO = 'Monto en SOLES (S/) que corresponde a ESE trimestre, NO el '
        . 'porcentaje del cronograma. Si la fuente de la verdad trae un % por trimestre y el costo '
        . 'total del componente, calcula monto = % × costo total antes de escribirlo aquí.';

    public function run(): void
    {
        $plantilla = $this->db->table('plantillas')->where('codigo', self::CODIGO_PLANTILLA)->get()->getRowArray();
        if ($plantilla === null || ! $plantilla['asignado_archivo_id']) {
            echo 'No existe ' . self::CODIGO_PLANTILLA . ' con archivo asignado — nada que anotar.' . PHP_EOL;

            return;
        }

        $archivo = $this->db->table('archivos')->where('id', $plantilla['asignado_archivo_id'])->get()->getRowArray();
        if ($archivo === null || empty($archivo['contenido_json'])) {
            echo 'La plantilla no tiene contenido_json — nada que anotar.' . PHP_EOL;

            return;
        }

        $objetivos = [
            '09.03.3' => ['costo' => self::NOTA_COSTO_ANUAL],
            '09.03.5' => ['costo' => self::NOTA_COSTO_ANUAL],
            '09.04.4' => array_fill_keys(array_map(static fn ($i) => "tri_{$i}", range(1, 8)), self::NOTA_TRIMESTRE_MONTO),
            '09.04.6' => array_fill_keys(array_map(static fn ($i) => "tri_{$i}", range(1, 8)), self::NOTA_TRIMESTRE_MONTO),
        ];

        $contenido = json_decode((string) $archivo['contenido_json'], true);
        $tocados   = 0;
        $this->recorrer($contenido, $objetivos, $tocados);

        if ($tocados === 0) {
            echo 'Ninguna columna nueva que anotar (ya estaban anotadas, o no se encontraron los campos).' . PHP_EOL;

            return;
        }

        $this->db->table('archivos')->where('id', $archivo['id'])->update([
            'contenido_json' => json_encode($contenido, JSON_UNESCAPED_UNICODE),
        ]);
        echo "Columnas anotadas: {$tocados}." . PHP_EOL;
    }

    private function recorrer(mixed &$nodo, array $objetivos, int &$tocados): void
    {
        if (! is_array($nodo)) {
            return;
        }

        $identificador = $nodo['identificador'] ?? null;
        if (is_string($identificador) && isset($objetivos[$identificador])) {
            $columnas = &$nodo['configTabla']['columnas'];
            if (is_array($columnas)) {
                foreach ($columnas as &$col) {
                    $nota = $objetivos[$identificador][$col['id']] ?? null;
                    if ($nota !== null && ($col['nota'] ?? null) !== $nota) {
                        $col['nota'] = $nota;
                        $tocados++;
                    }
                }
                unset($col);
            }
            unset($columnas);
        }

        foreach ($nodo as &$hijo) {
            $this->recorrer($hijo, $objetivos, $tocados);
        }
        unset($hijo);
    }
}
