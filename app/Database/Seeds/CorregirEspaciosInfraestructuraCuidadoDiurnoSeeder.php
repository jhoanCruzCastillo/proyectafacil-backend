<?php

namespace App\Database\Seeds;

use App\Libraries\ExcelStorage;
use CodeIgniter\Database\Seeder;
use ZipArchive;

// Bug real encontrado en vivo (2026-08-24) diagnosticado a fondo llenando la ficha "aaa" (FTE
// Servicio de Cuidado Diurno): la tabla 08.06.1 (Metas físicas) mostraba #N/A en "Área normativa
// mínima requerida" para casi todos los activos. Causa: la hoja oculta "Infraestructura" del Excel
// oficial del MEF/PNCM trae 8 de sus 9 nombres de activo con un espacio de más al final ("Sala de
// cuidado diurno " en vez de "Sala de cuidado diurno") — el VLOOKUP exacto que usa 08.06.1 nunca
// calzaba contra el texto limpio que se copia desde 08.03.1 "Activo del CIAI".
//
// Esto NO se puede arreglar solo con código (ver excelFormulaEval.ts, que sí tuvo su propio bug
// real de `_xlfn.IFNA` corregido aparte): el Excel real que el cliente descarga tiene el mismo
// defecto y también mostraría #N/A si abre el archivo en Excel de verdad. Hay que corregir el
// archivo Excel asignado a la plantilla.
//
// Idempotente: si el archivo ya asignado ya no tiene ninguno de los 8 espacios de más, no hace
// nada. Sube una COPIA corregida como archivo nuevo (nunca pisa el archivo original — mismo
// criterio que "Reasignar Excel" del admin) y copia su `contenido_json` (la estructura de
// secciones/campos vive ahí, no en la plantilla — perderlo deja la ficha con 0 secciones).
//
// Uso: php spark db:seed CorregirEspaciosInfraestructuraCuidadoDiurnoSeeder
class CorregirEspaciosInfraestructuraCuidadoDiurnoSeeder extends Seeder
{
    /** texto con el espacio de más (tal como está en el Excel oficial) => texto corregido */
    private const TEXTOS_A_CORREGIR = [
        'Sala de cuidado diurno '                          => 'Sala de cuidado diurno',
        'Ambiente de servicios generales '                 => 'Ambiente de servicios generales',
        'Sala de usos múltiples '                          => 'Sala de usos múltiples',
        'Ambiente de recreación activa '                   => 'Ambiente de recreación activa',
        'Ambiente de preparación y expendio de alimentos ' => 'Ambiente de preparación y expendio de alimentos',
        'Almacén '                                         => 'Almacén',
        'Cerco perimétrico '                                => 'Cerco perimétrico',
        'Muro de contención '                              => 'Muro de contención',
    ];

    public function run(): void
    {
        if (! class_exists(ZipArchive::class)) {
            echo "[SALTADO] La extensión ext-zip de PHP no está disponible en este entorno — corrígelo a mano o habilita ext-zip y vuelve a correr este seeder.\n";
            return;
        }

        $plantilla = $this->db->table('plantillas')->where('codigo', 'FTE-CUIDADO-DIURNO')->get()->getRowArray();
        if (! $plantilla) {
            echo "[SALTADO] No existe la plantilla FTE-CUIDADO-DIURNO en esta base de datos.\n";
            return;
        }
        if (! $plantilla['asignado_archivo_id']) {
            echo "[SALTADO] FTE-CUIDADO-DIURNO no tiene ningún Excel asignado todavía.\n";
            return;
        }

        $archivoActual = $this->db->table('archivos')->where('id', $plantilla['asignado_archivo_id'])->get()->getRowArray();
        if (! $archivoActual || ($archivoActual['url'] ?? '') === '') {
            echo "[SALTADO] El archivo asignado no tiene URL — nada que descargar.\n";
            return;
        }

        $storage = new ExcelStorage();
        $bin     = $storage->leerContenido($archivoActual)['body'];

        $tmp = tempnam(sys_get_temp_dir(), 'pf_fix_infra_');
        $ruta = $tmp . '.xlsx';
        @unlink($tmp);
        file_put_contents($ruta, $bin);

        $zip = new ZipArchive();
        if ($zip->open($ruta) !== true) {
            echo "[ERROR] No se pudo abrir el Excel asignado como .xlsx válido.\n";
            @unlink($ruta);
            return;
        }

        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            echo "[SALTADO] El Excel no tiene xl/sharedStrings.xml (¿formato inesperado?).\n";
            $zip->close();
            @unlink($ruta);
            return;
        }

        $huboCambios = false;
        foreach (self::TEXTOS_A_CORREGIR as $conEspacio => $limpio) {
            $buscar     = '<t xml:space="preserve">' . $conEspacio . '</t>';
            $reemplazar = '<t>' . $limpio . '</t>';
            if (str_contains($xml, $buscar)) {
                $xml = str_replace($buscar, $reemplazar, $xml);
                $huboCambios = true;
            }
        }

        if (! $huboCambios) {
            echo "Listo — el Excel asignado ya tiene los nombres de Infraestructura sin el espacio de más. Nada que hacer.\n";
            $zip->close();
            @unlink($ruta);
            return;
        }

        $zip->addFromString('xl/sharedStrings.xml', $xml);
        $zip->close();

        $nuevaUrl = $storage->subirDesdeRuta($ruta, (string) $archivoActual['nombre']);
        @unlink($ruta);

        $ahora = date('Y-m-d H:i:s');
        $this->db->table('archivos')->insert([
            'propietario_tipo' => $archivoActual['propietario_tipo'],
            'plantilla_id'     => $archivoActual['plantilla_id'],
            'ejemplo_id'       => $archivoActual['ejemplo_id'],
            'nombre'           => $archivoActual['nombre'],
            'url'              => $nuevaUrl,
            // La estructura de secciones/campos vive acá, no en la plantilla — sin copiarla, la
            // ficha se queda con 0 secciones apenas se reasigna (encontrado en vivo).
            'contenido_json'   => $archivoActual['contenido_json'],
            'fecha_subida'     => $ahora,
        ]);
        $nuevoArchivoId = (int) $this->db->insertID();

        $this->db->table('plantillas')->where('id', $plantilla['id'])->update([
            'asignado_archivo_id' => $nuevoArchivoId,
        ]);

        echo "Listo — Excel de FTE-CUIDADO-DIURNO corregido (8 nombres de Infraestructura sin el espacio de más). Nuevo archivo #{$nuevoArchivoId} asignado.\n";
    }
}
