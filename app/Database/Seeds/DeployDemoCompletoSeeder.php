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
// 5. AsesoriasDemoAsesor1Seeder — le da a asesor1 (Pedro Ríos) sus sectores de especialidad y un
//                              lote de consultas en varios estados. AsesoriasDemoSeeder solo cubre
//                              a los docentes demo 12-14, no a asesor1.
// 6. SubtemasEspecialidadSeeder — catálogo de subtemas por sector, y marca algunos para asesor1.
//                              Va DESPUÉS del paso 5: para elegir qué subtemas marcarle necesita
//                              que ya tenga sectores asignados.
// 7. CronogramaDemoAsesor1Seeder / NoAtendidasDemoAsesor1Seeder / LiquidacionDemoAsesor1Seeder —
//                              el resto de pantallas del asesor demo: citas del cronograma,
//                              consultas perdidas e historial de liquidación. El de liquidación
//                              necesita los subtemas del paso 6.
// 7. ChatsActivosPruebaJuanSeeder — rellena con una conversación de ejemplo los chats de Juan que
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
            AsesoriasDemoAsesor1Seeder::class,
            // Catálogo ILPIIE (temas + subtemas). Temas va ANTES de subtemas; ambos son
            // idempotentes. Si la BD ya tenía el catálogo viejo (PIN/EJO), correr aparte
            // SyncTemasIlpiieSeeder para reemplazarlo.
            TemasEspecialidadSeeder::class,
            SubtemasEspecialidadSeeder::class,
            ContextosIAGlobalesSeeder::class,
            ContextosIACuidadoDiurnoSeeder::class,
            ContextoGeneralCuidadoDiurnoSeeder::class,
            PromptSistemaCuidadoDiurnoSeeder::class,
            ContextosIAPasosCuidadoDiurnoSeeder::class,
            CorregirEspaciosInfraestructuraCuidadoDiurnoSeeder::class,
            AnotarOpcionesIncluidoPIEnTabla0803Seeder::class,
            CorregirTiposCostosAlternativa1Seeder::class,
            CorregirRiesgosSostenibilidadSeeder::class,
            // FTE-EBR-V03 (Educación). Los tres primeros siembran sus contextos IA (sección,
            // general y prompt del sistema); el cuarto completa la ficha para el llenado con IA
            // por el mecanismo de "Descripción / ayuda" por campo. Sin ellos, un ambiente recién
            // sembrado tenía la ficha cargada pero sin nada de lo que la IA consume.
            ContextosIAFTEEBRSeeder::class,
            ContextoGeneralFTEEBRSeeder::class,
            PromptSistemaFTEEBRSeeder::class,
            PrepararIAFTEEBRSeeder::class,
            // FTE-CARRETERAS (Transporte). Es la ficha más grande del catálogo — 488 campos en 15
            // hojas —, así que sus descripciones están repartidas en varios seeders por tramo en vez
            // de uno solo gigante. El ORDEN importa: CorregirDuplicados... renumera identificadores
            // duplicados de las subsecciones "D) Costos Incrementales", y D1D2/E1E2 escriben sus
            // descripciones contra los identificadores YA renumerados; CorregirDescripciones... va al
            // final porque repara justamente el texto que esos dos dejaron en los 4 campos calculados.
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
            // FTE-PE-SAL (Salud, ES de 12 h con rol Puerta de Entrada). Mismo juego de cuatro que
            // EBR: prompt del sistema, contexto general, guías por sección y descripciones por
            // campo. Alcance actual: Sección A — los tres primeros ya cubren toda la ficha, el
            // cuarto y las guías se van completando sección por sección.
            PromptSistemaFTEPESALSeeder::class,
            ContextoGeneralFTEPESALSeeder::class,
            ContextosIAFTEPESALSeeder::class,
            PrepararIAFTEPESALSeeder::class,
            // Formato 07-C (Registro de IOARR). Son tres y no cuatro: este formato NO lleva la
            // "guía general" de las FTE — en su lugar va el contexto general "Lineamientos IOARR",
            // porque no se formula ni se evalúan alternativas, solo se registra.
            PromptSistemaIOARR7CSeeder::class,
            LineamientosIOARR7CSeeder::class,
            ContextosIAIOARR7CSeeder::class,
            PrepararIAIOARR7CSeeder::class,
            // FTE-SAN-URBANO (Vivienda y Saneamiento, ámbito urbano). Mismo juego de cuatro que
            // EBR y PE-SAL. Alcance actual: Sección I — los dos primeros ya cubren toda la ficha,
            // las guías y las descripciones se completan sección por sección.
            PromptSistemaFTESANURBANOSeeder::class,
            ContextoGeneralFTESANURBANOSeeder::class,
            ContextosIAFTESANURBANOSeeder::class,
            PrepararIAFTESANURBANOSeeder::class,
            CronogramaDemoAsesor1Seeder::class,
            NoAtendidasDemoAsesor1Seeder::class,
            LiquidacionDemoAsesor1Seeder::class,
            ChatsActivosPruebaJuanSeeder::class,
            BeneficiosDemoSeeder::class,
            // Va al FINAL a propósito: recorre TODAS las plantillas ya sembradas y le devuelve sus
            // filas a las tablas que quedaron con valorEjemplo vacío pese a declarar filasBase. Sin
            // esto, esas tablas no se pueden llenar con IA (ver el comentario del seeder).
            RellenarFilasBaseTablasSeeder::class,
        ];

        foreach ($pasos as $seeder) {
            CLI::write("=> {$seeder}", 'cyan');
            $this->call($seeder);
        }

        CLI::write('Listo — ambiente de demo sembrado por completo (catálogo, plantillas, usuarios, docentes, tickets y solicitudes).', 'green');
    }
}
