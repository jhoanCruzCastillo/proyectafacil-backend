<?php

namespace App\Database\Migrations\Support;

// CodeIgniter no traduce 'type' => 'ENUM' para el driver Postgre (a diferencia de MySQL/MariaDB,
// donde ENUM es un tipo nativo) — Postgres no tiene un tipo ENUM inline, solo tipos con nombre vía
// CREATE TYPE. Para no atarnos a un solo motor (hoy Postgres en desarrollo, MariaDB en producción,
// ver docker-compose.yml), los "enums" del esquema se guardan como VARCHAR + un CHECK constraint
// que valida los valores permitidos — funciona igual en ambos motores sin SQL condicional.
trait PortableEnumTrait
{
    private function enumField(array $values, ?string $default = null, bool $nullable = false): array
    {
        $maxLen = max(array_map('strlen', $values));

        $field = ['type' => 'VARCHAR', 'constraint' => max(20, $maxLen)];
        if ($nullable) {
            $field['null'] = true;
        }
        if ($default !== null) {
            $field['default'] = $default;
        }

        return $field;
    }

    private function addEnumCheck(string $table, string $column, array $values): void
    {
        $quoted = implode(', ', array_map(
            static fn (string $v): string => "'" . str_replace("'", "''", $v) . "'",
            $values,
        ));

        $this->db->query("ALTER TABLE {$table} ADD CONSTRAINT chk_{$table}_{$column} CHECK ({$column} IN ({$quoted}))");
    }
}
