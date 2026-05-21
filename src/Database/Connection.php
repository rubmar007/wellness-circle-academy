<?php

declare(strict_types=1);

namespace App\Database;

use App\Support\Env;
use PDO;
use RuntimeException;

final class Connection
{
    private static ?PDO $instance = null;

    public static function get(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $url = Env::require('DATABASE_URL');
        $parts = parse_url($url);

        if ($parts === false || !isset($parts['host'], $parts['user'], $parts['path'])) {
            throw new RuntimeException('DATABASE_URL is malformed. Expected: postgresql://user:pass@host[:port]/db?sslmode=require');
        }

        $host = $parts['host'];
        $port = $parts['port'] ?? 5432;
        $db   = ltrim($parts['path'], '/');
        $user = urldecode($parts['user']);
        $pass = isset($parts['pass']) ? urldecode($parts['pass']) : '';

        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        // Parámetros de libpq que aceptamos en la URL. Se validan estrictamente
        // antes de incrustarse en el DSN para evitar inyección de opciones.
        $allowed = ['sslmode', 'channel_binding', 'connect_timeout', 'application_name', 'sslrootcert'];
        $dsnExtras = [];
        foreach ($allowed as $key) {
            if (!isset($query[$key]) || !is_string($query[$key]) || $query[$key] === '') {
                continue;
            }
            if (preg_match('/^[A-Za-z0-9._\\-\\/]+$/', $query[$key]) !== 1) {
                continue;
            }
            $dsnExtras[] = $key . '=' . $query[$key];
        }
        if (!isset($query['sslmode'])) {
            $dsnExtras[] = 'sslmode=require';
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;%s',
            $host,
            (int) $port,
            $db,
            implode(';', $dsnExtras)
        );

        // EMULATE_PREPARES=true: el escape de parámetros se hace en el cliente
        // (PDO_pgsql) y manda SQL ya armado al servidor. Necesario para los
        // poolers tipo pgbouncer (Neon usa uno propio) que no manejan bien
        // múltiples prepared statements server-side dentro de la misma
        // transacción cuando tocan tablas distintas. El escape de PDO_pgsql
        // está probado y es seguro contra SQL injection.
        self::$instance = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
            PDO::ATTR_PERSISTENT         => false,
        ]);

        return self::$instance;
    }
}
