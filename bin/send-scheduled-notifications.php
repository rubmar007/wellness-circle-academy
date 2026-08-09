<?php

declare(strict_types=1);

/**
 * Script CLI ejecutado por el Cron Job de Railway (ver railway.json).
 * Revisa push_notifications con status='pending' y scheduled_at <= ahora,
 * las envía con PushService, y:
 *   - si is_recurring = TRUE: recalcula scheduled_at (+1 día ó +7 días
 *     según recurrence_freq) y la deja en 'pending' para la próxima vez.
 *   - si no: la marca 'sent'.
 *
 * Uso:
 *   php bin/send-scheduled-notifications.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script solo se ejecuta por CLI.\n");
    exit(1);
}

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';

use App\Database\Connection;
use App\Support\Env;
use App\Support\PushService;

Env::load($basePath);

$pdo = Connection::get();

$due = $pdo->query(
    "SELECT * FROM push_notifications WHERE status = 'pending' AND scheduled_at <= NOW() ORDER BY scheduled_at ASC"
)->fetchAll();

if ($due === []) {
    fwrite(STDOUT, "Sin notificaciones pendientes.\n");
    exit(0);
}

foreach ($due as $notification) {
    $result = PushService::send($notification);

    fwrite(STDOUT, sprintf(
        "[#%d] \"%s\" -> enviadas: %d, fallidas: %d\n",
        (int) $notification['id'],
        (string) $notification['title'],
        $result['sent'],
        $result['failed']
    ));

    if ((bool) $notification['is_recurring']) {
        $next = new DateTimeImmutable((string) $notification['scheduled_at']);
        $next = $notification['recurrence_freq'] === 'weekly'
            ? $next->modify('+7 days')
            : $next->modify('+1 day');

        // Si por alguna razón el cron no corrió por un rato, evita quedar
        // "atorado" en el pasado: sigue sumando hasta que quede en el futuro.
        $now = new DateTimeImmutable('now');
        while ($next <= $now) {
            $next = $notification['recurrence_freq'] === 'weekly'
                ? $next->modify('+7 days')
                : $next->modify('+1 day');
        }

        $stmt = $pdo->prepare(
            'UPDATE push_notifications
                SET scheduled_at = :next, last_sent_at = NOW(),
                    last_sent_count = :sent, last_failed_count = :failed,
                    updated_at = NOW()
              WHERE id = :id'
        );
        $stmt->execute([
            ':next'   => $next->format('Y-m-d H:i:s'),
            ':sent'   => $result['sent'],
            ':failed' => $result['failed'],
            ':id'     => (int) $notification['id'],
        ]);
    } else {
        $stmt = $pdo->prepare(
            "UPDATE push_notifications
                SET status = 'sent', last_sent_at = NOW(),
                    last_sent_count = :sent, last_failed_count = :failed,
                    updated_at = NOW()
              WHERE id = :id"
        );
        $stmt->execute([
            ':sent'   => $result['sent'],
            ':failed' => $result['failed'],
            ':id'     => (int) $notification['id'],
        ]);
    }
}
