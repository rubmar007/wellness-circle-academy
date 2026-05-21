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
        $sslmode = isset($query['sslmode']) && is_string($query['sslmode']) ? $query['sslmode'] : 'require';

        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
            $host,
            (int) $port,
            $db,
            $sslmode
        );

        self::$instance = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
            PDO::ATTR_PERSISTENT         => false,
        ]);

        return self::$instance;
    }
}
