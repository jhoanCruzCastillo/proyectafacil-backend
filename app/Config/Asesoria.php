<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

// Parámetros económicos de las asesorías. Antes el honorario vivía como constante privada dentro
// de TicketsAsesoriaController; se movió acá porque ahora lo consumen dos pantallas distintas
// (la liquidación del administrativo y la del propio asesor) y tienen que coincidir sí o sí.
class Asesoria extends BaseConfig
{
    /**
     * Lo que se le paga al asesor por cada consulta completada, en soles.
     *
     * OJO: se aplica por igual a todo el histórico. Si algún día cambia la tarifa, los montos
     * pasados se recalcularían con el valor nuevo — para eso habría que guardar el honorario
     * vigente en cada solicitud al completarla, no leerlo de configuración.
     */
    public int $honorarioPorTicket = 550;
}
