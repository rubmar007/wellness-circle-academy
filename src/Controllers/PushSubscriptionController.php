<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Database\Connection;
use App\View;

/**
 * Guarda/borra la suscripción push del navegador (endpoint + llaves p256dh/
 * auth que entrega el Service Worker al llamar a pushManager.subscribe()).
 * Se llama desde JS con fetch() y cuerpo JSON, por eso el CSRF se valida a
 * mano contra Csrf::verify() en vez de Csrf::requireValid() (que lee $_POST).
 */
final class PushSubscriptionController
{
    /** @param array<string,string> $params */
    public function store(array $params): void
    {
        Auth::requireLogin();
        $data = self::readJsonBody();

        if (!Csrf::verify(is_string($data['_csrf'] ?? null) ? $data['_csrf'] : null)) {
            View::json(['ok' => false, 'error' => 'csrf'], 419);
            return;
        }

        $endpoint = trim((string) ($data['endpoint'] ?? ''));
        $keys     = is_array($data['keys'] ?? null) ? $data['keys'] : [];
        $p256dh   = trim((string) ($keys['p256dh'] ?? ''));
        $authKey  = trim((string) ($keys['auth'] ?? ''));

        if ($endpoint === '' || $p256dh === '' || $authKey === '' || filter_var($endpoint, FILTER_VALIDATE_URL) === false) {
            View::json(['ok' => false, 'error' => 'invalid'], 422);
            return;
        }

        $user = Auth::user();
        $stmt = Connection::get()->prepare(
            'INSERT INTO push_subscriptions (user_id, endpoint, p256dh_key, auth_key, user_agent)
             VALUES (:u, :e, :p, :a, :ua)
             ON CONFLICT (endpoint) DO UPDATE SET
                 user_id     = EXCLUDED.user_id,
                 p256dh_key  = EXCLUDED.p256dh_key,
                 auth_key    = EXCLUDED.auth_key,
                 user_agent  = EXCLUDED.user_agent,
                 updated_at  = now()'
        );
        $stmt->execute([
            ':u'  => (int) $user['id'],
            ':e'  => $endpoint,
            ':p'  => $p256dh,
            ':a'  => $authKey,
            ':ua' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);

        View::json(['ok' => true]);
    }

    /** @param array<string,string> $params */
    public function destroy(array $params): void
    {
        Auth::requireLogin();
        $data = self::readJsonBody();

        if (!Csrf::verify(is_string($data['_csrf'] ?? null) ? $data['_csrf'] : null)) {
            View::json(['ok' => false, 'error' => 'csrf'], 419);
            return;
        }

        $endpoint = trim((string) ($data['endpoint'] ?? ''));
        if ($endpoint === '') {
            View::json(['ok' => false, 'error' => 'invalid'], 422);
            return;
        }

        $user = Auth::user();
        $stmt = Connection::get()->prepare(
            'DELETE FROM push_subscriptions WHERE endpoint = :e AND user_id = :u'
        );
        $stmt->execute([':e' => $endpoint, ':u' => (int) $user['id']]);

        View::json(['ok' => true]);
    }

    /** @return array<string,mixed> */
    private static function readJsonBody(): array
    {
        $raw     = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
