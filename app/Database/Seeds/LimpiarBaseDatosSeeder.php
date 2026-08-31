<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

// PELIGRO — borra TODAS las filas de TODAS las tablas de la aplicación (deja el esquema intacto,
// solo vacía los datos) y reinicia los contadores de autoincremento. Pensado como el primer paso
// de una "siembra limpia" antes de un deploy: correr esto, y después DeployProduccionSeeder.
//
// Pide confirmación interactiva explícita (escribir la frase exacta) — nunca corre sin ella, ni
// siquiera con -n/--no-interaction, porque no hay forma de deshacer un TRUNCATE. Si se necesita en
// un pipeline no interactivo, exportar CONFIRMAR_LIMPIEZA_TOTAL=SI antes de invocar el seeder.
//
// Uso: php spark db:seed LimpiarBaseDatosSeeder
class LimpiarBaseDatosSeeder extends Seeder
{
    // Orden no importa — TRUNCATE ... CASCADE resuelve las dependencias de FK por su cuenta
    // (Postgres). Si se agrega una tabla nueva a una migración, agregarla acá también.
    private const TABLAS = [
        'usuario_permisos', 'roles_permisos_base', 'permisos_catalogo', 'tipos_usuario',
        'plantilla_tipologia_ioarr', 'ejemplo_tipologia_ioarr', 'archivos', 'ejemplos', 'plantillas',
        'plan_features', 'add_on_niveles_disponibles', 'facturacion_addons', 'facturas', 'facturaciones', 'add_ons', 'planes',
        'actividad_reciente', 'historial_cambio_campos', 'historial_cambios',
        'contexto_seccion_globales', 'contextos_ia_seccion', 'contextos_ia_globales',
        'contextos_ia_general', 'contextos_ia_pasos',
        'horarios_docente', 'horario_excepciones_docente', 'asesor_especialidades', 'asesor_subtemas',
        'subtemas_especialidad', 'mensajes_asesoria', 'solicitud_notificaciones',
        'notificaciones', 'tickets_consulta', 'solicitudes_asesoria', 'configuracion_sla',
        'fuente_verdad_archivos', 'llenado_ia_lotes', 'sesiones',
        'beneficios', 'cuenta_beneficios', 'cliente_intereses',
        'usuarios', 'sectores',
    ];

    public function run(): void
    {
        CLI::write('=== LIMPIEZA TOTAL DE DATOS ===', 'red');
        CLI::write('Esto va a BORRAR todas las filas de las ' . count(self::TABLAS) . ' tablas de la aplicación:', 'yellow');
        CLI::write(implode(', ', self::TABLAS), 'yellow');
        CLI::write('El esquema (tablas/columnas) NO se toca — solo los datos. No se puede deshacer.', 'yellow');
        CLI::newLine();

        if (getenv('CONFIRMAR_LIMPIEZA_TOTAL') !== 'SI') {
            $respuesta = CLI::prompt('Escribe exactamente BORRAR TODO para continuar');
            if (trim($respuesta) !== 'BORRAR TODO') {
                CLI::write('Cancelado — no se borró nada.', 'green');

                return;
            }
        }

        $tablasEscapadas = implode(', ', array_map(static fn (string $t) => '"' . $t . '"', self::TABLAS));
        $this->db->query("TRUNCATE TABLE {$tablasEscapadas} RESTART IDENTITY CASCADE");

        CLI::write('Listo — todas las tablas quedaron vacías. Corre DeployProduccionSeeder para volver a poblar el catálogo base.', 'green');
    }
}
