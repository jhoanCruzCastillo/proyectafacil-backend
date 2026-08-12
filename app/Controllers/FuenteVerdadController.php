<?php

namespace App\Controllers;

use App\Libraries\CloudinaryUploader;
use App\Models\EjemploModel;
use CodeIgniter\HTTP\ResponseInterface;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

// "Fuente de la verdad" de una ficha de cliente (ver migración CreateFuenteVerdad): documentos
// (PDF/TXT/MD) y texto libre que el cliente carga sobre su proyecto real. Es el insumo — el QUÉ
// llenar — que el llenado automático con IA combina con los Contextos IA (el CÓMO llenar, ver
// ContextosIAController) para completar la ficha (ver LlenadoIAController).
class FuenteVerdadController extends BaseController
{
    private const EXTENSIONES_PERMITIDAS = ['pdf', 'txt', 'md'];
    private const TAMANO_MAXIMO = 10 * 1024 * 1024; // 10 MB
    private const TEXTO_MAXIMO = 5000;

    public function index($ejemploId = null): ResponseInterface
    {
        return $this->response->setJSON($this->estado((int) $ejemploId));
    }

    public function guardarArchivo($ejemploId = null): ResponseInterface
    {
        $ejemploId = (int) $ejemploId;
        $dto       = $this->request->getJSON(true) ?? [];
        $nombre    = trim((string) ($dto['nombre'] ?? ''));
        $dataUrl   = (string) ($dto['dataUrl'] ?? '');
        $extension = strtolower((string) pathinfo($nombre, PATHINFO_EXTENSION));

        if ($nombre === '' || $dataUrl === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Falta el nombre o el archivo']);
        }
        if (! in_array($extension, self::EXTENSIONES_PERMITIDAS, true)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Solo se admiten archivos PDF, TXT o MD']);
        }

        [$binario, $tamano] = $this->decodificarDataUrl($dataUrl);
        if ($tamano === 0) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'El archivo llegó vacío']);
        }
        if ($tamano > self::TAMANO_MAXIMO) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'El archivo supera los 10 MB permitidos']);
        }

        $texto = $this->extraerTexto($extension, $binario);

        try {
            $url = (new CloudinaryUploader())->subirDocumento($dataUrl, $nombre);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo subir el archivo: ' . $e->getMessage()]);
        }

        db_connect()->table('fuente_verdad_archivos')->insert([
            'ejemplo_id'      => $ejemploId,
            'nombre'          => $nombre,
            'extension'       => $extension,
            'url'             => $url,
            'contenido_texto' => $texto,
            'tamano_bytes'    => $tamano,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON($this->estado($ejemploId));
    }

    public function eliminarArchivo($ejemploId = null, $archivoId = null): ResponseInterface
    {
        db_connect()->table('fuente_verdad_archivos')
            ->where('id', (int) $archivoId)
            ->where('ejemplo_id', (int) $ejemploId)
            ->delete();

        return $this->response->setJSON($this->estado((int) $ejemploId));
    }

    public function guardarTexto($ejemploId = null): ResponseInterface
    {
        $dto   = $this->request->getJSON(true) ?? [];
        $texto = (string) ($dto['texto'] ?? '');
        if (mb_strlen($texto) > self::TEXTO_MAXIMO) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'El texto adicional no puede superar los ' . self::TEXTO_MAXIMO . ' caracteres']);
        }

        (new EjemploModel())->update((int) $ejemploId, ['fuente_verdad_texto' => $texto]);

        return $this->response->setJSON($this->estado((int) $ejemploId));
    }

    private function estado(int $ejemploId): array
    {
        $archivos = db_connect()->table('fuente_verdad_archivos')
            ->where('ejemplo_id', $ejemploId)
            ->orderBy('created_at', 'ASC')
            ->get()->getResultArray();

        $ejemplo = (new EjemploModel())->find($ejemploId);

        return [
            'archivos' => array_map(static fn (array $a) => [
                'id'          => (string) $a['id'],
                'nombre'      => $a['nombre'],
                'extension'   => $a['extension'],
                'tamanoBytes' => (int) $a['tamano_bytes'],
            ], $archivos),
            'textoAdicional' => $ejemplo['fuente_verdad_texto'] ?? '',
        ];
    }

    /** @return array{0:string,1:int} [contenido binario decodificado, tamaño en bytes] */
    private function decodificarDataUrl(string $dataUrl): array
    {
        $partes  = explode(',', $dataUrl, 2);
        $binario = isset($partes[1]) ? (string) base64_decode($partes[1], true) : '';

        return [$binario, strlen($binario)];
    }

    private function extraerTexto(string $extension, string $binario): string
    {
        if ($extension === 'txt' || $extension === 'md') {
            // El origen puede traer BOM/CRLF de Windows — no afecta al contexto de la IA, se deja
            // tal cual para no perder nada del texto original.
            return $binario;
        }

        try {
            $pdf = (new PdfParser())->parseContent($binario);

            return trim($pdf->getText());
        } catch (Throwable $e) {
            // Un PDF raro (escaneado como imagen, corrupto, protegido) no debe tumbar la subida —
            // el archivo se guarda igual, solo sin texto extraído; el llenado con IA simplemente no
            // tendrá ese archivo como fuente.
            log_message('error', '[fuente-verdad] No se pudo extraer texto del PDF "{nombre}": {msg}', [
                'nombre' => $extension,
                'msg'    => $e->getMessage(),
            ]);

            return '';
        }
    }
}
