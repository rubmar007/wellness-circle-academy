<?php

declare(strict_types=1);

namespace App\Session;

use App\Database\Connection;
use SessionHandlerInterface;

/**
 * Guarda las sesiones en la tabla `sessions` de Neon en vez de archivos
 * locales. Railway corre en un contenedor con disco efímero: cada deploy
 * (o cualquier reinicio del contenedor) borra los archivos de sesión y
 * desconecta a todo mundo de golpe. Con esto la sesión sobrevive a
 * deploys/reinicios porque vive en la misma base de datos persistente que
 * ya usa el resto de la app.
 */
final class DatabaseSessionHandler implements SessionHandlerInterface
{
    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $stmt = Connection::get()->prepare('SELECT data FROM sessions WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetchColumn();
        return $data !== false ? (string) $data : '';
    }

    public function write(string $id, string $data): bool
    {
        $stmt = Connection::get()->prepare(
            'INSERT INTO sessions (id, data, last_activity) VALUES (:id, :data, NOW())
             ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, last_activity = NOW()'
        );
        return $stmt->execute([':id' => $id, ':data' => $data]);
    }

    public function destroy(string $id): bool
    {
        $stmt = Connection::get()->prepare('DELETE FROM sessions WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = Connection::get()->prepare(
            "DELETE FROM sessions WHERE last_activity < NOW() - (:seconds || ' seconds')::interval"
        );
        $stmt->execute([':seconds' => $max_lifetime]);
        return $stmt->rowCount();
    }
}
