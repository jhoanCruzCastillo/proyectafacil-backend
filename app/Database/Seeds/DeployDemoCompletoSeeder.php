<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

// Pedido explícito del usuario: que el ambiente de Railway quede en el MISMO estado que el
// ambiente local — no solo catálogo/plantillas/superusuario (eso ya lo cubre
// DeployProduccionSeeder), sino también los usuarios de muestra, alumnos/docentes demo, tickets,
// solicitudes de asesoría en distintos estados, horarios y una conversación de ejemplo. Justificado
// porque este ambiente es una demo pública, no tiene todavía clientes reales.
//
// Orquesta, en orden, todo lo que existe hoy en local:
// 1. DeployProduccionSeeder  — catálogo base + plantillas + estructura + superusuario real.
// 2. UsuariosDemoProduccionSeeder — los 7 usuarios de muestra (mismo username/password que local).
// 3. AsesoriasDemoSeeder     — alumnos demo, 3 docentes demo (especialidades+horario), fotos,
//                              facturación/tickets, y un lote de solicitudes de asesoría en todos
//                              los estados (pendiente, en_espera, asignado, agendado, completado,
//                              cancelado) para que el dashboard/Cobertura/Liquidaciones no se vean
//                              vacíos.
// 4. CoberturaHorariosDemoSeeder — franjas adicionales de horario para que el mapa de calor de
//                              Cobertura de horarios tenga más variedad.
// 5. ChatsActivosPruebaJuanSeeder — rellena con una conversación de ejemplo los chats de Juan que
//                              ya quedaron en estado "asignado" por el paso 3.
//
// Deliberadamente NO incluye TicketsPruebaJuanSeeder — ese es una herramienta de prueba puntual
// que BORRA los tickets reales de Juan y los reemplaza por un conteo arbitrario (15/17) solo para
// verificar un contador en la UI; no representa un estado "normal" de demo. Correrlo aparte si
// hace falta ese caso específico.
//
// Cada paso es idempotente — se puede correr de nuevo sobre un ambiente ya sembrado sin duplicar
// nada ni fallar.
//
// Uso: php spark db:seed DeployDemoCompletoSeeder
class DeployDemoCompletoSeeder extends Seeder
{
    public function run(): void
    {
        $pasos = [
            DeployProduccionSeeder::class,
            UsuariosDemoProduccionSeeder::class,
            AsesoriasDemoSeeder::class,
            CoberturaHorariosDemoSeeder::class,
            ChatsActivosPruebaJuanSeeder::class,
        ];

        foreach ($pasos as $seeder) {
            CLI::write("=> {$seeder}", 'cyan');
            $this->call($seeder);
        }

        CLI::write('Listo — ambiente de demo sembrado por completo (catálogo, plantillas, usuarios, docentes, tickets y solicitudes).', 'green');
    }
}
