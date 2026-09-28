<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// Un trabajo = una corrida de "Llenar toda la ficha" delegada al backend: el cliente la dispara y
// puede cerrar la pestaña/sesión de inmediato — un Cron Job de Railway (ver
// LlenadoIAController::ejecutarLlenadoCompletoAsync() y los comandos ia:procesar-llenados-pendientes/
// ia:ejecutar-llenado) la procesa en segundo plano, sin depender del navegador del cliente ni de
// ningún límite de tiempo de PHP/gateway HTTP, y avisa por correo (CorreoService) + notificación
// in-app al terminar. Reemplaza, para este caso, el flujo síncrono de useLlenadoIAProgreso.ts que
// obligaba a mantener la pestaña abierta 40+ minutos en una ficha grande.
//
// No es la misma tabla que `llenado_ia_lotes` (esa es del mecanismo viejo pensado para la Batch API
// real de OpenAI, hoy dormida — ver su propia migración) — se separan a propósito para no mezclar dos
// conceptos de "lote"/"trabajo" con historias distintas.
class CreateLlenadoIATrabajos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ejemplo_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            // Quién lo disparó — para saber a qué correo avisar al terminar (usuarios.correo). SET
            // NULL si se borra la cuenta: el trabajo/resultado ya guardado en el ejemplo no depende
            // de que la cuenta siga existiendo.
            'usuario_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            // Filtro opcional de secciones (JSON de ids) — null = toda la ficha.
            'seccion_ids'       => ['type' => 'TEXT', 'null' => true],
            // pendiente -> procesando -> completado | error (cancelado: reservado para más adelante,
            // no se construye todavía ningún botón que lo dispare).
            'estado'            => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pendiente'],
            'progreso_texto'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'campos_completados' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'campos_totales'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'costo_usd'         => ['type' => 'DECIMAL', 'constraint' => '10,5', 'default' => 0],
            // Resumen final (contadores por fase, tablas fallidas, costo) — mismo criterio que
            // llenado_ia_lotes.resultado_json: se llena una sola vez al completar.
            'resultado_json'    => ['type' => 'TEXT', 'null' => true],
            'error'             => ['type' => 'TEXT', 'null' => true],
            'iniciado_en'       => ['type' => 'DATETIME', 'null' => true],
            'terminado_en'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('ejemplo_id');
        $this->forge->addKey('estado');
        $this->forge->addForeignKey('ejemplo_id', 'ejemplos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('llenado_ia_trabajos');
    }

    public function down()
    {
        $this->forge->dropTable('llenado_ia_trabajos');
    }
}
