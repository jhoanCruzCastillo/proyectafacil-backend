<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

// Orquesta la siembra de un ambiente real (producción/staging) con SOLO datos reales — catálogo
// base + estructura de plantillas + el superusuario inicial. Deliberadamente NO incluye:
// - UsuariosSeeder: usa credenciales de desarrollo hardcodeadas, nunca en un ambiente real.
// - AsesoriasDemoSeeder / CoberturaHorariosDemoSeeder / TicketsPruebaJuanSeeder /
//   ChatsActivosPruebaJuanSeeder: alumnos, asesores, tickets y chats FALSOS para probar
//   localmente — no deben existir en producción.
//
// Se puede correr sobre una BD ya poblada (cada seeder hijo es idempotente) o justo después de
// LimpiarBaseDatosSeeder para una siembra desde cero. No borra nada por su cuenta.
//
// Requiere las variables de entorno SEED_SUPERUSER_USUARIO / SEED_SUPERUSER_PASSWORD (ver
// SuperusuarioProduccionSeeder) ya configuradas antes de correr esto.
//
// Uso: php spark db:seed DeployProduccionSeeder
class DeployProduccionSeeder extends Seeder
{
    public function run(): void
    {
        $pasos = [
            SectoresSeeder::class,
            PlanesYAddOnsSeeder::class,
            PermisosCatalogoSeeder::class,
            RolesPermisosBaseSeeder::class,
            PlantillasSeeder::class,
            EstructuraPlantillasSeeder::class,
            // Sin estos 5, el llenado con IA arranca en un ambiente real sin reglas, sin contexto de
            // dominio, sin guía por sección y sin las asignaciones de paso del admin — el modelo solo
            // vería el schema JSON crudo (o el fallback por nombre reservado, no lo configurado a
            // propósito). Estaban sembrados en el ambiente local a mano (nunca vía seeder) hasta que
            // se detectó el hueco.
            ContextosIAGlobalesSeeder::class,
            ContextosIACuidadoDiurnoSeeder::class,
            ContextoGeneralCuidadoDiurnoSeeder::class,
            PromptSistemaCuidadoDiurnoSeeder::class,
            // Qué insumo puntual usa cada paso asignable (tab "Estructura" del panel Contextos IA) —
            // va DESPUÉS de los 4 de arriba: busca sus filas por nombre, tienen que existir ya.
            ContextosIAPasosCuidadoDiurnoSeeder::class,
            SuperusuarioProduccionSeeder::class,
        ];

        foreach ($pasos as $seeder) {
            CLI::write("-> {$seeder}", 'cyan');
            $this->call($seeder);
        }

        CLI::write('Listo — catálogo base, plantillas y superusuario sembrados.', 'green');
    }
}
