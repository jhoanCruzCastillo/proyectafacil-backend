<?php

namespace App\Controllers;

use App\Models\ActividadModel;
use CodeIgniter\HTTP\ResponseInterface;
use Stripe\StripeClient;
use Stripe\Webhook;
use Throwable;

// Flujo de compra real vía Stripe Checkout — modo de PRUEBA por ahora (ver Config/Stripe.php).
// Cubre tres cosas separadas, cada una con su propio `metadata.tipo` para que webhook() sepa qué
// hacer: 'beneficio' (checkout() — sistema de Beneficios), 'plan' (checkoutPlan()) y 'addon'
// (checkoutAddon()). Una vez que existe una suscripción activa (Nivel 1/2), cambiar de plan o
// ajustar la cantidad de un add-on recurrente ya NO pasa por Checkout — se actualiza la
// suscripción existente directo vía API (cambiarPlan()/ajustarAddonEnSuscripcion()), instantáneo,
// cobrando/prorrateando de verdad con la tarjeta que Stripe ya guardó en el Customer. Actualizar
// la tarjeta guardada, ver facturas reales, o cancelar, se hace en el Customer Portal de Stripe
// (portal()) — no hay que construir nada de eso a mano.
class PagosController extends BaseController
{
    private const ADDON_SLUGS = [
        'consultoria-1a1'     => 'Consultoría 1 a 1',
        'usuario-adicional'   => 'Usuario adicional',
        'plantilla-adicional' => 'Plantilla adicional',
    ];

    public function checkout(): ResponseInterface
    {
        $dto        = $this->request->getJSON(true) ?? [];
        $usuarioId  = (int) ($dto['usuarioId'] ?? 0);
        $beneficioId = (int) ($dto['beneficioId'] ?? 0);
        $cuentaId   = $this->idCuentaDe($usuarioId);

        $config = config('Stripe');
        if ($config->secretKey === '') {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Stripe no está configurado — falta stripe.secretKey en el .env']);
        }

        $beneficio = db_connect()->table('beneficios')->where('id', $beneficioId)->where('activo', 1)->get()->getRowArray();
        if (! $beneficio) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Beneficio no encontrado']);
        }
        if (empty($beneficio['stripe_price_id'])) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Este beneficio todavía no tiene un precio configurado en Stripe']);
        }

        $usuario = db_connect()->table('usuarios')->select('correo')->where('id', $usuarioId)->get()->getRowArray();

        $params = [
            'mode'                => $beneficio['recurrente'] ? 'subscription' : 'payment',
            'line_items'          => [['price' => $beneficio['stripe_price_id'], 'quantity' => 1]],
            'client_reference_id' => (string) $cuentaId,
            'metadata'            => ['tipo' => 'beneficio', 'beneficioId' => (string) $beneficioId, 'cuentaId' => (string) $cuentaId],
            'success_url'         => rtrim($config->frontendBaseUrl, '/') . '/?beneficio_checkout=success',
            'cancel_url'          => rtrim($config->frontendBaseUrl, '/') . '/?beneficio_checkout=cancel',
        ];
        // El SDK de Stripe manda `null` como cadena vacía en vez de omitirlo — customer_email
        // solo se agrega si de verdad hay un correo (en BD queda '' cuando nadie lo completó, no
        // NULL, y Stripe rechaza esa cadena vacía por formato inválido).
        if (! empty($usuario['correo'])) {
            $params['customer_email'] = $usuario['correo'];
        }

        $stripe = new StripeClient($config->secretKey);
        try {
            $session = $stripe->checkout->sessions->create($params);
        } catch (Throwable $e) {
            log_message('error', 'PagosController::checkout falló: {msg}', ['msg' => $e->getMessage()]);

            return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo iniciar el pago con Stripe. Intenta de nuevo en unos minutos.']);
        }

        return $this->response->setJSON(['url' => $session->url]);
    }

    public function checkoutPlan(): ResponseInterface
    {
        $dto       = $this->request->getJSON(true) ?? [];
        $usuarioId = (int) ($dto['usuarioId'] ?? 0);
        $cuentaId  = $this->idCuentaDe($usuarioId);

        $config = config('Stripe');
        if ($config->secretKey === '') {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Stripe no está configurado — falta stripe.secretKey en el .env']);
        }

        $numeroNivel = (int) str_replace('nivel-', '', (string) ($dto['planId'] ?? ''));
        $plan        = db_connect()->table('planes')->where('numero_nivel', $numeroNivel)->get()->getRowArray();
        if (! $plan) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Plan no encontrado']);
        }
        if (empty($plan['stripe_price_id'])) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Este plan todavía no tiene un precio configurado en Stripe']);
        }

        $stripe     = new StripeClient($config->secretKey);
        $customerId = $this->stripeCustomerIdDe($cuentaId, $stripe);

        $params = [
            'mode'                => $plan['periodicidad'] === 'Único' ? 'payment' : 'subscription',
            'customer'            => $customerId,
            'line_items'          => [['price' => $plan['stripe_price_id'], 'quantity' => 1]],
            'client_reference_id' => (string) $cuentaId,
            'metadata'            => ['tipo' => 'plan', 'planId' => (string) $plan['id'], 'cuentaId' => (string) $cuentaId],
            'success_url'         => rtrim($config->frontendBaseUrl, '/') . '/?facturacion_checkout=success',
            'cancel_url'          => rtrim($config->frontendBaseUrl, '/') . '/?facturacion_checkout=cancel',
        ];

        try {
            $session = $stripe->checkout->sessions->create($params);
        } catch (Throwable $e) {
            log_message('error', 'PagosController::checkoutPlan falló: {msg}', ['msg' => $e->getMessage()]);

            return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo iniciar el pago con Stripe. Intenta de nuevo en unos minutos.']);
        }

        return $this->response->setJSON(['url' => $session->url]);
    }

    // Cambia el plan de una suscripción YA activa (Nivel 1↔2) — swap directo del price del ítem
    // principal, con prorrateo real de Stripe. No sirve para entrar/salir de Nivel 0 (pago único,
    // no es un ítem de suscripción) — eso sigue siendo checkoutPlan() (y, para salir de una
    // suscripción hacia Nivel 0, primero hay que cancelarla desde el Portal).
    public function cambiarPlan(): ResponseInterface
    {
        $dto       = $this->request->getJSON(true) ?? [];
        $usuarioId = (int) ($dto['usuarioId'] ?? 0);
        $cuentaId  = $this->idCuentaDe($usuarioId);

        $config = config('Stripe');
        if ($config->secretKey === '') {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Stripe no está configurado — falta stripe.secretKey en el .env']);
        }

        $numeroNivel = (int) str_replace('nivel-', '', (string) ($dto['planId'] ?? ''));
        $plan        = db_connect()->table('planes')->where('numero_nivel', $numeroNivel)->get()->getRowArray();
        if (! $plan) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Plan no encontrado']);
        }
        if (empty($plan['stripe_price_id'])) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Este plan todavía no tiene un precio configurado en Stripe']);
        }
        if ($plan['periodicidad'] === 'Único') {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Este plan no es una suscripción — cancela tu plan actual desde el portal de facturación antes de elegirlo']);
        }

        $facturacion = db_connect()->table('facturaciones')->where('usuario_id', $cuentaId)->get()->getRowArray();
        if (! $facturacion || empty($facturacion['stripe_subscription_id']) || empty($facturacion['stripe_subscription_item_id'])) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Todavía no tienes una suscripción activa']);
        }

        $stripe = new StripeClient($config->secretKey);
        try {
            $stripe->subscriptions->update($facturacion['stripe_subscription_id'], [
                'items'               => [['id' => $facturacion['stripe_subscription_item_id'], 'price' => $plan['stripe_price_id']]],
                'proration_behavior'  => 'create_prorations',
            ]);
        } catch (Throwable $e) {
            log_message('error', 'PagosController::cambiarPlan falló: {msg}', ['msg' => $e->getMessage()]);

            return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo cambiar el plan en Stripe. Intenta de nuevo en unos minutos.']);
        }

        db_connect()->table('facturaciones')->where('usuario_id', $cuentaId)->update(['plan_id' => $plan['id'], 'updated_at' => date('Y-m-d H:i:s')]);
        TicketsConsultaController::emitirTicketsDePlan($cuentaId, (int) $plan['id']);

        return $this->response->setJSON(['ok' => true]);
    }

    public function checkoutAddon(): ResponseInterface
    {
        $dto       = $this->request->getJSON(true) ?? [];
        $usuarioId = (int) ($dto['usuarioId'] ?? 0);
        $slug      = (string) ($dto['addonSlug'] ?? '');
        $cantidad  = max(1, (int) ($dto['cantidad'] ?? 1));
        $cuentaId  = $this->idCuentaDe($usuarioId);

        $config = config('Stripe');
        if ($config->secretKey === '') {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Stripe no está configurado — falta stripe.secretKey en el .env']);
        }

        $nombre = self::ADDON_SLUGS[$slug] ?? null;
        $addon  = $nombre !== null ? db_connect()->table('add_ons')->where('nombre', $nombre)->get()->getRowArray() : null;
        if (! $addon) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Add-on no encontrado']);
        }
        if (empty($addon['stripe_price_id'])) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Este add-on todavía no tiene un precio configurado en Stripe']);
        }

        $stripe      = new StripeClient($config->secretKey);
        $customerId  = $this->stripeCustomerIdDe($cuentaId, $stripe);
        $facturacion = db_connect()->table('facturaciones')->where('usuario_id', $cuentaId)->get()->getRowArray();

        // Recurrente y ya suscrito: se agrega/ajusta directo en la suscripción existente, sin
        // Checkout — instantáneo, cobrando con la tarjeta que Stripe ya tiene guardada.
        if ($addon['recurrente'] && $facturacion && ! empty($facturacion['stripe_subscription_id'])) {
            try {
                $this->ajustarAddonEnSuscripcion($stripe, $facturacion, $addon, $cuentaId, $cantidad);
            } catch (Throwable $e) {
                log_message('error', 'PagosController::checkoutAddon (ajuste directo) falló: {msg}', ['msg' => $e->getMessage()]);

                return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo ajustar el add-on en Stripe. Intenta de nuevo en unos minutos.']);
            }

            return $this->response->setJSON(['ok' => true]);
        }

        $params = [
            'mode'                => $addon['recurrente'] ? 'subscription' : 'payment',
            'customer'            => $customerId,
            'line_items'          => [['price' => $addon['stripe_price_id'], 'quantity' => $cantidad]],
            'client_reference_id' => (string) $cuentaId,
            'metadata'            => ['tipo' => 'addon', 'addonSlug' => $slug, 'cantidad' => (string) $cantidad, 'cuentaId' => (string) $cuentaId],
            'success_url'         => rtrim($config->frontendBaseUrl, '/') . '/?facturacion_checkout=success',
            'cancel_url'          => rtrim($config->frontendBaseUrl, '/') . '/?facturacion_checkout=cancel',
        ];

        try {
            $session = $stripe->checkout->sessions->create($params);
        } catch (Throwable $e) {
            log_message('error', 'PagosController::checkoutAddon falló: {msg}', ['msg' => $e->getMessage()]);

            return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo iniciar el pago con Stripe. Intenta de nuevo en unos minutos.']);
        }

        return $this->response->setJSON(['url' => $session->url]);
    }

    // Ítem ya existente en la suscripción: solo se ajusta la cantidad (suma a lo ya comprado —
    // mismo criterio "nunca resta" que TicketsConsultaController::emitirTicketsDeAddon). Primera
    // vez que este add-on se agrega a una suscripción que ya existe por otro motivo (el plan, u
    // otro add-on): se crea el ítem nuevo.
    private function ajustarAddonEnSuscripcion(StripeClient $stripe, array $facturacion, array $addon, int $cuentaId, int $cantidadNueva): void
    {
        $db          = db_connect();
        $cuentaId    = (int) $facturacion['usuario_id'];
        $filaAddon   = $db->table('facturacion_addons')->where('facturacion_usuario_id', $cuentaId)->where('add_on_id', $addon['id'])->get()->getRowArray();

        if ($filaAddon && ! empty($filaAddon['stripe_subscription_item_id'])) {
            $cantidadTotal = (int) $filaAddon['cantidad'] + $cantidadNueva;
            $stripe->subscriptionItems->update($filaAddon['stripe_subscription_item_id'], ['quantity' => $cantidadTotal]);
            $db->table('facturacion_addons')->where('facturacion_usuario_id', $cuentaId)->where('add_on_id', $addon['id'])
                ->update(['cantidad' => $cantidadTotal]);

            return;
        }

        $cantidadTotal = ($filaAddon['cantidad'] ?? 0) + $cantidadNueva;
        $item          = $stripe->subscriptionItems->create([
            'subscription' => $facturacion['stripe_subscription_id'],
            'price'        => $addon['stripe_price_id'],
            'quantity'     => $cantidadTotal,
        ]);

        if ($filaAddon) {
            $db->table('facturacion_addons')->where('facturacion_usuario_id', $cuentaId)->where('add_on_id', $addon['id'])
                ->update(['cantidad' => $cantidadTotal, 'stripe_subscription_item_id' => $item->id]);
        } else {
            $db->table('facturacion_addons')->insert([
                'facturacion_usuario_id'       => $cuentaId,
                'add_on_id'                    => $addon['id'],
                'cantidad'                     => $cantidadTotal,
                'stripe_subscription_item_id'  => $item->id,
            ]);
        }
    }

    // Quita 1 unidad de un add-on RECURRENTE ya contratado — reduce la cantidad del ítem de
    // suscripción en Stripe de verdad (o lo elimina si llega a 0), a diferencia de la compra que
    // solo suma. Solo aplica a recurrentes: "Consultoría 1 a 1" es un pago único ya cobrado, no
    // hay nada que "dejar de cobrar" — el frontend no debe ofrecer esta acción para esa.
    public function quitarAddon(): ResponseInterface
    {
        $dto       = $this->request->getJSON(true) ?? [];
        $usuarioId = (int) ($dto['usuarioId'] ?? 0);
        $slug      = (string) ($dto['addonSlug'] ?? '');
        $cuentaId  = $this->idCuentaDe($usuarioId);

        $config = config('Stripe');
        if ($config->secretKey === '') {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Stripe no está configurado — falta stripe.secretKey en el .env']);
        }

        $nombre = self::ADDON_SLUGS[$slug] ?? null;
        $addon  = $nombre !== null ? db_connect()->table('add_ons')->where('nombre', $nombre)->get()->getRowArray() : null;
        if (! $addon) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Add-on no encontrado']);
        }
        if (! $addon['recurrente']) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Este add-on es de pago único — no se puede quitar, ya está pagado']);
        }

        $db        = db_connect();
        $filaAddon = $db->table('facturacion_addons')->where('facturacion_usuario_id', $cuentaId)->where('add_on_id', $addon['id'])->get()->getRowArray();
        if (! $filaAddon || (int) $filaAddon['cantidad'] <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'No tienes ninguno contratado']);
        }

        $nuevaCantidad = (int) $filaAddon['cantidad'] - 1;

        if (! empty($filaAddon['stripe_subscription_item_id'])) {
            $stripe = new StripeClient($config->secretKey);
            try {
                if ($nuevaCantidad <= 0) {
                    $stripe->subscriptionItems->delete($filaAddon['stripe_subscription_item_id']);
                } else {
                    $stripe->subscriptionItems->update($filaAddon['stripe_subscription_item_id'], [
                        'quantity'           => $nuevaCantidad,
                        'proration_behavior' => 'create_prorations',
                    ]);
                }
            } catch (Throwable $e) {
                log_message('error', 'PagosController::quitarAddon falló: {msg}', ['msg' => $e->getMessage()]);

                return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo ajustar el add-on en Stripe. Intenta de nuevo en unos minutos.']);
            }
        }

        if ($nuevaCantidad <= 0) {
            $db->table('facturacion_addons')->where('facturacion_usuario_id', $cuentaId)->where('add_on_id', $addon['id'])->delete();
        } else {
            $db->table('facturacion_addons')->where('facturacion_usuario_id', $cuentaId)->where('add_on_id', $addon['id'])->update(['cantidad' => $nuevaCantidad]);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    // Página hospedada por Stripe donde el cliente actualiza su tarjeta real, ve sus facturas
    // reales, y cancela su suscripción — nada de esto se construye a mano. Solo disponible una vez
    // que existe un Customer real (después de la primera compra real de algo).
    public function portal(): ResponseInterface
    {
        $usuarioId = (int) ($this->request->getGet('usuarioId') ?? 0);
        $cuentaId  = $this->idCuentaDe($usuarioId);

        $config = config('Stripe');
        if ($config->secretKey === '') {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Stripe no está configurado — falta stripe.secretKey en el .env']);
        }

        $facturacion = db_connect()->table('facturaciones')->where('usuario_id', $cuentaId)->get()->getRowArray();
        if (! $facturacion || empty($facturacion['stripe_customer_id'])) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Todavía no tienes ninguna compra real — el portal se activa después de tu primera compra (un plan o un add-on).']);
        }

        $stripe = new StripeClient($config->secretKey);
        try {
            $session = $stripe->billingPortal->sessions->create([
                'customer'   => $facturacion['stripe_customer_id'],
                'return_url' => rtrim($config->frontendBaseUrl, '/') . '/',
            ]);
        } catch (Throwable $e) {
            log_message('error', 'PagosController::portal falló: {msg}', ['msg' => $e->getMessage()]);

            return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo abrir el portal de facturación. Intenta de nuevo en unos minutos.']);
        }

        return $this->response->setJSON(['url' => $session->url]);
    }

    // Resuelve el Customer real de Stripe de una cuenta — lo crea la primera vez que hace falta
    // (lazy) y lo persiste en facturaciones.stripe_customer_id. Si la cuenta todavía no tiene fila
    // en facturaciones (nunca abrió la pantalla de Facturación), la crea con lo mínimo — sin
    // inventar un plan pagado, a diferencia de FacturacionController::crearDefault() (que sigue
    // existiendo para el "estado de muestra" que ve cualquier cuenta nueva antes de comprar algo
    // real).
    private function stripeCustomerIdDe(int $cuentaId, StripeClient $stripe): string
    {
        $db   = db_connect();
        $fila = $db->table('facturaciones')->where('usuario_id', $cuentaId)->get()->getRowArray();
        if ($fila && ! empty($fila['stripe_customer_id'])) {
            return $fila['stripe_customer_id'];
        }

        $usuario = $db->table('usuarios')->select('correo')->where('id', $cuentaId)->get()->getRowArray();
        $params  = [];
        if (! empty($usuario['correo'])) {
            $params['email'] = $usuario['correo'];
        }
        $customer = $stripe->customers->create($params);
        $ahora    = date('Y-m-d H:i:s');

        if ($fila) {
            $db->table('facturaciones')->where('usuario_id', $cuentaId)->update(['stripe_customer_id' => $customer->id, 'updated_at' => $ahora]);
        } else {
            $planBase = $db->table('planes')->where('numero_nivel', 1)->get()->getRowArray();
            $db->table('facturaciones')->insert([
                'usuario_id'         => $cuentaId,
                'plan_id'            => $planBase['id'],
                'cancelada'          => 0,
                'metodo_pago'        => 'tarjeta',
                'stripe_customer_id' => $customer->id,
                'created_at'         => $ahora,
                'updated_at'         => $ahora,
            ]);
        }

        return $customer->id;
    }

    // Stripe llama a esto directo (sin sesión, sin CSRF) — la única garantía de que el request es
    // legítimo es la firma en el header Stripe-Signature, verificada contra stripe.webhookSecret.
    public function webhook(): ResponseInterface
    {
        $config = config('Stripe');
        $payload   = $this->request->getBody();
        $sigHeader = $this->request->getHeaderLine('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $config->webhookSecret);
        } catch (Throwable $e) {
            log_message('error', 'PagosController::webhook firma inválida: {msg}', ['msg' => $e->getMessage()]);

            return $this->response->setStatusCode(400)->setBody('Firma inválida');
        }

        $db = db_connect();

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $tipo    = $session->metadata->tipo ?? 'beneficio'; // sesiones viejas de beneficio no tenían 'tipo'
            $ahora   = date('Y-m-d H:i:s');

            if ($tipo === 'beneficio') {
                $db->table('cuenta_beneficios')->insert([
                    'cuenta_id'                  => (int) $session->metadata->cuentaId,
                    'beneficio_id'               => (int) $session->metadata->beneficioId,
                    'estado'                     => 'activo',
                    'stripe_checkout_session_id' => $session->id,
                    'stripe_subscription_id'     => $session->subscription ?? null,
                    'fecha_inicio'               => $ahora,
                    'created_at'                 => $ahora,
                    'updated_at'                 => $ahora,
                ]);
            } elseif ($tipo === 'plan') {
                $cuentaId  = (int) $session->metadata->cuentaId;
                $planId    = (int) $session->metadata->planId;
                $subId     = $session->subscription ?? null;
                $itemId    = null;
                if ($subId) {
                    $stripe = new StripeClient($config->secretKey);
                    $sub    = $stripe->subscriptions->retrieve($subId);
                    $itemId = $sub->items->data[0]->id ?? null;
                }

                $cambios = [
                    'plan_id'                     => $planId,
                    'cancelada'                   => 0,
                    'fecha_inicio_plan'           => $ahora,
                    'stripe_customer_id'          => $session->customer,
                    'stripe_subscription_id'      => $subId,
                    'stripe_subscription_item_id' => $itemId,
                    'updated_at'                  => $ahora,
                ];
                if ($db->table('facturaciones')->where('usuario_id', $cuentaId)->countAllResults() > 0) {
                    $db->table('facturaciones')->where('usuario_id', $cuentaId)->update($cambios);
                } else {
                    $cambios['usuario_id']  = $cuentaId;
                    $cambios['metodo_pago'] = 'tarjeta';
                    $cambios['created_at']  = $ahora;
                    $db->table('facturaciones')->insert($cambios);
                }

                TicketsConsultaController::emitirTicketsDePlan($cuentaId, $planId);
            } elseif ($tipo === 'addon') {
                $cuentaId = (int) $session->metadata->cuentaId;
                $slug     = (string) $session->metadata->addonSlug;
                $cantidad = (int) $session->metadata->cantidad;
                $nombre   = self::ADDON_SLUGS[$slug] ?? null;
                $addon    = $nombre !== null ? $db->table('add_ons')->where('nombre', $nombre)->get()->getRowArray() : null;

                if ($addon) {
                    $filaAddon     = $db->table('facturacion_addons')->where('facturacion_usuario_id', $cuentaId)->where('add_on_id', $addon['id'])->get()->getRowArray();
                    $cantidadTotal = ($filaAddon['cantidad'] ?? 0) + $cantidad;

                    $itemId = null;
                    if ($addon['recurrente'] && $session->subscription) {
                        $stripe = new StripeClient($config->secretKey);
                        $sub    = $stripe->subscriptions->retrieve($session->subscription);
                        $itemId = $sub->items->data[0]->id ?? null;
                    }

                    if ($filaAddon) {
                        $cambiosAddon = ['cantidad' => $cantidadTotal];
                        if ($itemId) {
                            $cambiosAddon['stripe_subscription_item_id'] = $itemId;
                        }
                        $db->table('facturacion_addons')->where('facturacion_usuario_id', $cuentaId)->where('add_on_id', $addon['id'])->update($cambiosAddon);
                    } else {
                        $db->table('facturacion_addons')->insert([
                            'facturacion_usuario_id'      => $cuentaId,
                            'add_on_id'                   => $addon['id'],
                            'cantidad'                    => $cantidadTotal,
                            'stripe_subscription_item_id' => $itemId,
                        ]);
                    }

                    if ($slug === 'consultoria-1a1') {
                        TicketsConsultaController::emitirTicketsDeAddon($cuentaId, $cantidadTotal);
                    }
                }
            }
        }

        if ($event->type === 'customer.subscription.updated') {
            $sub     = $event->data->object;
            $cambios = ['updated_at' => date('Y-m-d H:i:s'), 'cancelada' => $sub->cancel_at_period_end ? 1 : 0];
            if (isset($sub->current_period_end)) {
                $cambios['fecha_renovacion'] = date('Y-m-d', $sub->current_period_end);
            }
            $db->table('facturaciones')->where('stripe_customer_id', $sub->customer)->update($cambios);
        }

        if ($event->type === 'customer.subscription.deleted') {
            $sub = $event->data->object;
            $db->table('facturaciones')->where('stripe_customer_id', $sub->customer)->update(['cancelada' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
        }

        // Fuente única de verdad para el historial de facturas real — reemplaza lo que antes
        // fabricaba el cliente. Cubre tanto la primera factura de una suscripción nueva como cada
        // renovación futura.
        if ($event->type === 'invoice.paid') {
            $invoice     = $event->data->object;
            $facturacion = $db->table('facturaciones')->where('stripe_customer_id', $invoice->customer)->get()->getRowArray();
            if ($facturacion) {
                $db->table('facturas')->insert([
                    'facturacion_usuario_id' => $facturacion['usuario_id'],
                    'fecha'                  => date('Y-m-d', $invoice->created),
                    'total'                  => $invoice->amount_paid / 100,
                    'estado'                 => 'Pagado',
                ]);

                // 'subscription_cycle' = renovación automática de un periodo ya vigente. El primer
                // cobro de una suscripción nueva llega como 'subscription_create' y no es "renovó"
                // — es la contratación inicial, ya reflejada por separado en checkout.session.completed.
                if (($invoice->billing_reason ?? null) === 'subscription_cycle') {
                    (new ActividadModel())->insert([
                        'mensaje'    => 'Renovó su plan',
                        'color'      => 'green',
                        'categoria'  => 'Facturación',
                        'actor_id'   => (int) $facturacion['usuario_id'],
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }

        return $this->response->setJSON(['recibido' => true]);
    }

    private function idCuentaDe(int $usuarioId): int
    {
        $u = db_connect()->table('usuarios')->select('cuenta_cliente_id')->where('id', $usuarioId)->get()->getRowArray();

        return $u && $u['cuenta_cliente_id'] !== null ? (int) $u['cuenta_cliente_id'] : $usuarioId;
    }
}
