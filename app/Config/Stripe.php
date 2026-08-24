<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

// Credenciales de Stripe para el sistema de "beneficios" comprables (ver PagosController,
// BeneficiosController). Modo de PRUEBA por ahora: las keys son las de prueba del Dashboard de
// Stripe (sk_test_.../pk_test_...), no hay dinero real involucrado — el código es idéntico entre
// modo prueba y modo real, Stripe decide por el prefijo de la key.
//
// En el .env del backend:
//   stripe.secretKey = sk_test_...
//   stripe.publishableKey = pk_test_...
//   stripe.webhookSecret = whsec_...   (lo imprime `stripe listen` en desarrollo)
//   stripe.frontendBaseUrl = http://localhost:5180   (adonde vuelve Checkout tras pagar/cancelar)
class Stripe extends BaseConfig
{
    public string $secretKey = '';
    public string $publishableKey = '';
    public string $webhookSecret = '';
    public string $frontendBaseUrl = 'http://localhost:5180';
}
