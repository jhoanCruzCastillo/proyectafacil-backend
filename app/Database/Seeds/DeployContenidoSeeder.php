<?php

namespace App\Database\Seeds;

use App\Libraries\SincronizadorContenidoPlantillas;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

// Publica en un ambiente CON USUARIOS REALES todo el contenido de fichas construido en local
// (estructuras JSON, prompts, contextos y guías de IA, descripciones por campo, ejemplo de
// referencia) SIN borrar ni crear usuarios. Alternativa segura a "LimpiarBaseDatosSeeder +
// DeployDemoCompletoSeeder", que destruye usuarios y las fichas de los clientes.
//
// Qué NO hace, a propósito: no corre LimpiarBaseDatosSeeder, ni ningún seeder de usuarios/demo
// (UsuariosDemo…, AsesoriasDemo…, Cronograma/NoAtendidas/Liquidacion/Chats/Beneficios demo,
// Superusuario…), ni toca catálogos de asesoría (temas/subtemas/planes/permisos). Ver
// SincronizadorContenidoPlantillas para el detalle exacto de lo que escribe en las plantillas.
//
// Antes de correrlo por primera vez conviene la simulación:
//   php spark plantillas:sincronizar-contenido
//
// Requiere el snapshot data/plantillas_estructura.json al día (php spark plantillas:export-estructura
// en local, commit y push) y las credenciales de Cloudinary configuradas (los seeders de contexto
// suben sus .md ahí).
//
// Uso: php spark db:seed DeployContenidoSeeder
class DeployContenidoSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Metadata de plantillas nuevas (insert-only) y estructura/ejemplos de referencia EN SU SITIO.
        CLI::write('=> PlantillasSeeder', 'cyan');
        $this->call(PlantillasSeeder::class);

        CLI::write('=> SincronizadorContenidoPlantillas (estructuras + ejemplos de referencia)', 'cyan');
        $resumen = (new SincronizadorContenidoPlantillas($this->db, true, static fn (string $l) => CLI::write($l)))->ejecutar();
        foreach ($resumen as $clave => $n) {
            CLI::write(sprintf('  %-28s %d', str_replace('_', ' ', $clave), $n));
        }

        // 2) Contextos/prompts/descripciones de IA — mismo orden que DeployDemoCompletoSeeder, sin
        //    ninguno de sus pasos de usuarios/demo. Todos son idempotentes.
        $pasos = [
            ContextosIAGlobalesSeeder::class,
            ContextosIACuidadoDiurnoSeeder::class,
            ContextoGeneralCuidadoDiurnoSeeder::class,
            PromptSistemaCuidadoDiurnoSeeder::class,
            ContextosIAPasosCuidadoDiurnoSeeder::class,
            CorregirEspaciosInfraestructuraCuidadoDiurnoSeeder::class,
            AnotarOpcionesIncluidoPIEnTabla0803Seeder::class,
            CorregirTiposCostosAlternativa1Seeder::class,
            CorregirRiesgosSostenibilidadSeeder::class,
            ContextosIAFTEEBRSeeder::class,
            ContextoGeneralFTEEBRSeeder::class,
            PromptSistemaFTEEBRSeeder::class,
            PrepararIAFTEEBRSeeder::class,
            PromptSistemaFTECarreterasSeeder::class,
            ContextoGeneralFTECarreterasSeeder::class,
            ContextosIAFTECarreterasSeeder::class,
            PrepararIAFTECarreterasSeeder::class,
            PrepararIAFTECarreterasA1TraficoSeeder::class,
            PrepararIAFTECarreterasA2DemandaSeeder::class,
            PrepararIAFTECarreterasAnexosMenoresSeeder::class,
            PrepararIAFTECarreterasA5A6A7Seeder::class,
            PrepararIAFTECarreterasCCostosSeeder::class,
            CorregirDuplicadosCostosIncrementalesFTECarreterasSeeder::class,
            PrepararIAFTECarreterasD1D2Seeder::class,
            PrepararIAFTECarreterasE1E2Seeder::class,
            CorregirDescripcionesCostosIncrementalesFTECarreterasSeeder::class,
            PromptSistemaFTEPESALSeeder::class,
            ContextoGeneralFTEPESALSeeder::class,
            ContextosIAFTEPESALSeeder::class,
            PrepararIAFTEPESALSeeder::class,
            PromptSistemaIOARR7CSeeder::class,
            LineamientosIOARR7CSeeder::class,
            ContextosIAIOARR7CSeeder::class,
            PrepararIAIOARR7CSeeder::class,
            PromptSistemaFTESANURBANOSeeder::class,
            ContextoGeneralFTESANURBANOSeeder::class,
            ContextosIAFTESANURBANOSeeder::class,
            PrepararIAFTESANURBANOSeeder::class,
            // Al final, igual que en DeployDemoCompletoSeeder: recorre todas las plantillas.
            RellenarFilasBaseTablasSeeder::class,
        ];

        foreach ($pasos as $seeder) {
            CLI::write("=> {$seeder}", 'cyan');
            $this->call($seeder);
        }

        CLI::write('Listo — contenido de plantillas e IA publicado; usuarios y fichas de clientes intactos.', 'green');
    }
}
