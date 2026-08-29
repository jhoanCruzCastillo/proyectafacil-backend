<?php
namespace App\Commands;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class VerifPrompt3Command extends BaseCommand {
    protected $group = 'demo';
    protected $name = 'demo:verif-prompt3';

    private function full(string $url): string {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
        $body = curl_exec($ch);
        curl_close($ch);
        return is_string($body) ? trim($body) : '(no se pudo descargar)';
    }

    public function run(array $params) {
        $db = db_connect();
        $fila = $db->table('contextos_ia_general')->where('plantilla_id', 19)->where('nombre', 'Prompt del sistema')->get()->getRowArray();
        $texto = $this->full($fila['url']);
        CLI::write("LARGO TOTAL: " . mb_strlen($texto) . " caracteres");
        CLI::write($texto);
    }
}
