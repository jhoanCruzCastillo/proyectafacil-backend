<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

// Catálogo de "beneficios" comprables y los que ya posee la cuenta en sesión — ver
// PagosController para el flujo de compra en sí (Stripe Checkout + webhook). Un beneficio es una
// propiedad binaria (se tiene o no), a diferencia de tickets_consulta (créditos que se consumen).
class BeneficiosController extends BaseController
{
    public function index(): ResponseInterface
    {
        $filas = db_connect()->table('beneficios')
            ->where('activo', 1)
            ->orderBy('precio', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON(array_map([$this, 'toDto'], $filas));
    }

    // Los de la CUENTA (titular + colaboradores comparten beneficios, igual que tickets_consulta)
    // que están activos ahora mismo.
    public function misBeneficios(): ResponseInterface
    {
        $usuarioId = (int) ($this->request->getGet('usuarioId') ?? 0);
        $cuentaId  = $this->idCuentaDe($usuarioId);

        $filas = db_connect()->table('cuenta_beneficios cb')
            ->select('b.*')
            ->join('beneficios b', 'b.id = cb.beneficio_id')
            ->where('cb.cuenta_id', $cuentaId)
            ->where('cb.estado', 'activo')
            ->get()->getResultArray();

        return $this->response->setJSON(array_map([$this, 'toDto'], $filas));
    }

    private function idCuentaDe(int $usuarioId): int
    {
        $u = db_connect()->table('usuarios')->select('cuenta_cliente_id')->where('id', $usuarioId)->get()->getRowArray();

        return $u && $u['cuenta_cliente_id'] !== null ? (int) $u['cuenta_cliente_id'] : $usuarioId;
    }

    private function toDto(array $b): array
    {
        return [
            'id'            => (string) $b['id'],
            'slug'          => $b['slug'],
            'nombre'        => $b['nombre'],
            'descripcion'   => $b['descripcion'] ?? null,
            'precio'        => (float) $b['precio'],
            'recurrente'    => (bool) $b['recurrente'],
            'comprable'     => ! empty($b['stripe_price_id']),
        ];
    }
}
