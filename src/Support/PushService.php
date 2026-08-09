<?php

declare(strict_types=1);

namespace App\Support;

use App\Database\Connection;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Envío de Web Push notifications (protocolo estándar del navegador, no
 * requiere Apple/Google Developer). Usado por el módulo admin de
 * notificaciones programadas (bin/send-scheduled-notifications.php) y
 * podría reutilizarse más adelante para el pendiente ya documentado de
 * "le falta seguimiento" (docs/PLAN_A_1act sección 4.5).
 *
 * Requiere las variables de entorno VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY y
 * VAPID_SUBJECT (ver .env.example). Si no están configuradas, no falla:
 * registra el error y no envía nada (mismo patrón que Mailer::isConfigured).
 */
final class PushService
{
    public static function isConfigured(): bool
    {
        return Env::get('VAPID_PUBLIC_KEY') !== null
            && Env::get('VAPID_PRIVATE_KEY') !== null
            && Env::get('VAPID_SUBJECT') !== null;
    }

    /**
     * @param array<string,mixed> $notification fila de push_notifications
     * @return array{sent: int, failed: int}
     */
    public static function send(array $notification): array
    {
        if (!self::isConfigured()) {
            error_log('[wca] PushService: VAPID no configurado, no se envía la notificación #' . $notification['id']);
            return ['sent' => 0, 'failed' => 0];
        }

        $subscriptions = self::subscriptionsForAudience($notification);
        if ($subscriptions === []) {
            return ['sent' => 0, 'failed' => 0];
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject'    => Env::require('VAPID_SUBJECT'),
                'publicKey'  => Env::require('VAPID_PUBLIC_KEY'),
                'privateKey' => Env::require('VAPID_PRIVATE_KEY'),
            ],
        ]);

        $payload = json_encode([
            'title' => (string) $notification['title'],
            'body'  => (string) $notification['body'],
            'url'   => $notification['url'] !== null && $notification['url'] !== ''
                ? (string) $notification['url']
                : '/dashboard',
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        foreach ($subscriptions as $sub) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $sub['endpoint'],
                    'keys'     => ['p256dh' => $sub['p256dh_key'], 'auth' => $sub['auth_key']],
                ]),
                $payload
            );
        }

        $sent   = 0;
        $failed = 0;
        $expiredEndpoints = [];

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $sent++;
                continue;
            }

            $failed++;
            if ($report->isSubscriptionExpired()) {
                $expiredEndpoints[] = $report->getEndpoint();
            } else {
                error_log('[wca] PushService: envío falló (' . $report->getEndpoint() . '): ' . $report->getReason());
            }
        }

        if ($expiredEndpoints !== []) {
            self::deleteExpired($expiredEndpoints);
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Resuelve las suscripciones activas según audience_type de la
     * notificación. Solo usuarios activos (is_active = TRUE) reciben push.
     *
     * @param array<string,mixed> $notification
     * @return array<int, array<string,mixed>>
     */
    private static function subscriptionsForAudience(array $notification): array
    {
        $pdo = Connection::get();

        switch ($notification['audience_type']) {
            case 'all':
                return $pdo->query(
                    'SELECT ps.* FROM push_subscriptions ps
                       JOIN users u ON u.id = ps.user_id
                      WHERE u.is_active = TRUE'
                )->fetchAll();

            case 'role':
                $stmt = $pdo->prepare(
                    'SELECT ps.* FROM push_subscriptions ps
                       JOIN users u ON u.id = ps.user_id
                      WHERE u.is_active = TRUE AND u.role = :role'
                );
                $stmt->execute([':role' => (string) $notification['audience_role']]);
                return $stmt->fetchAll();

            case 'kit':
                $stmt = $pdo->prepare(
                    'SELECT ps.* FROM push_subscriptions ps
                       JOIN users u ON u.id = ps.user_id
                       JOIN client_kits ck ON ck.user_id = u.id AND ck.is_active = TRUE
                      WHERE u.is_active = TRUE AND ck.kit_slug = :slug'
                );
                $stmt->execute([':slug' => (string) $notification['audience_kit_slug']]);
                return $stmt->fetchAll();

            case 'user':
                $stmt = $pdo->prepare(
                    'SELECT ps.* FROM push_subscriptions ps
                       JOIN users u ON u.id = ps.user_id
                      WHERE u.is_active = TRUE AND ps.user_id = :id'
                );
                $stmt->execute([':id' => (int) $notification['audience_user_id']]);
                return $stmt->fetchAll();

            default:
                return [];
        }
    }

    /** @param array<int, string> $endpoints */
    private static function deleteExpired(array $endpoints): void
    {
        $stmt = Connection::get()->prepare('DELETE FROM push_subscriptions WHERE endpoint = :e');
        foreach ($endpoints as $endpoint) {
            $stmt->execute([':e' => $endpoint]);
        }
    }
}
