<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Database\Connection;
use App\Mailer;
use App\Security;
use App\Support\Env;
use App\View;
use Throwable;

/**
 * Recuperación de contraseña por email — endpoint "secreto" /ctoadmin.
 *
 * Flujo:
 *   GET  /ctoadmin                -> formulario para solicitar reset (form vacío).
 *   POST /ctoadmin                -> genera token único, envía email al admin si aplica.
 *   GET  /restablecer/{token}     -> valida token, muestra form de nueva password.
 *   POST /restablecer/{token}     -> aplica el cambio, invalida el token.
 *
 * Seguridad:
 *   - Solo se envían links a usuarios con rol 'admin' Y is_active = TRUE.
 *   - Para no filtrar enumeración, la respuesta es siempre genérica
 *     ("Si tu email es de administrador, recibirás un link").
 *   - Token: 32 bytes aleatorios, hex de 64 chars. Se guarda hash SHA-256
 *     en BD; el token en claro solo viaja en el email.
 *   - Expira en 15 minutos. Single-use (used_at).
 *   - Rate limit: máximo 3 solicitudes por email por hora.
 *   - URL no está vinculada desde ningún lado de la app.
 */
final class PasswordResetController
{
    private const TOKEN_TTL_MINUTES        = 15;
    private const MAX_REQUESTS_PER_HOUR    = 3;

    /** @param array<string,string> $params */
    public function show(array $params): void
    {
        if (Auth::check()) {
            View::redirect('/dashboard');
            return;
        }

        View::render('auth/forgot', [
            'errors'    => [],
            'old'       => ['email' => ''],
            'configured'=> Mailer::isConfigured(),
        ]);
    }

    /** @param array<string,string> $params */
    public function request(array $params): void
    {
        Csrf::requireValid();

        if (Auth::check()) {
            View::redirect('/dashboard');
            return;
        }

        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $errors = [];

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Email inválido.';
        }

        if ($errors !== []) {
            View::render('auth/forgot', [
                'errors'    => $errors,
                'old'       => ['email' => $email],
                'configured'=> Mailer::isConfigured(),
            ]);
            return;
        }

        // Procesamos siempre con respuesta genérica, sin filtrar si el email existe.
        $admin = self::findActiveAdminByEmail($email);

        if ($admin !== null && !self::isRateLimited((int) $admin['id'], $email)) {
            $tokenPlain = bin2hex(random_bytes(32));
            $tokenHash  = hash('sha256', $tokenPlain);
            $expiresAt  = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
                ->modify('+' . self::TOKEN_TTL_MINUTES . ' minutes')
                ->format('Y-m-d H:i:sP');

            $stmt = Connection::get()->prepare(
                'INSERT INTO password_resets (user_id, token_hash, expires_at, ip_address)
                 VALUES (:uid, :h, :exp, :ip)'
            );
            $stmt->execute([
                ':uid' => (int) $admin['id'],
                ':h'   => $tokenHash,
                ':exp' => $expiresAt,
                ':ip'  => Security::clientIp(),
            ]);

            $appUrl = (string) (Env::get('APP_URL', 'http://localhost:8080') ?? 'http://localhost:8080');
            $appUrl = rtrim($appUrl, '/');
            $link   = $appUrl . '/restablecer/' . $tokenPlain;

            try {
                Mailer::send(
                    $email,
                    'Recuperación de acceso — Wellness Circle Academy',
                    View::renderPartial('email/reset.txt',  ['name' => $admin['name'], 'link' => $link, 'ttl_minutes' => self::TOKEN_TTL_MINUTES]),
                    View::renderPartial('email/reset.html', ['name' => $admin['name'], 'link' => $link, 'ttl_minutes' => self::TOKEN_TTL_MINUTES])
                );
            } catch (Throwable $e) {
                // No exponemos al usuario el detalle del error; lo registramos.
                error_log('[wca] PasswordReset: envío falló para ' . $email . ': ' . $e->getMessage());
            }
        }

        View::render('auth/forgot-sent', [
            'email' => $email,
        ]);
    }

    /** @param array<string,string> $params */
    public function showReset(array $params): void
    {
        if (Auth::check()) {
            View::redirect('/dashboard');
            return;
        }

        $token = (string) ($params['token'] ?? '');
        $reset = self::findValidReset($token);

        if ($reset === null) {
            View::render('auth/reset-invalid', []);
            return;
        }

        View::render('auth/reset', [
            'token'  => $token,
            'errors' => [],
        ]);
    }

    /** @param array<string,string> $params */
    public function reset(array $params): void
    {
        Csrf::requireValid();

        if (Auth::check()) {
            View::redirect('/dashboard');
            return;
        }

        $token     = (string) ($params['token'] ?? '');
        $password  = (string) ($_POST['password'] ?? '');
        $password2 = (string) ($_POST['password2'] ?? '');

        $reset = self::findValidReset($token);
        if ($reset === null) {
            View::render('auth/reset-invalid', []);
            return;
        }

        $errors = [];
        if (mb_strlen($password) < 10) {
            $errors['password'] = 'La contraseña debe tener al menos 10 caracteres.';
        }
        if ($password !== $password2) {
            $errors['password2'] = 'Las contraseñas no coinciden.';
        }

        if ($errors !== []) {
            View::render('auth/reset', [
                'token'  => $token,
                'errors' => $errors,
            ]);
            return;
        }

        $pdo = Connection::get();
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
            $stmt->execute([
                ':h'  => Auth::hashPassword($password),
                ':id' => (int) $reset['user_id'],
            ]);

            $stmt = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id');
            $stmt->execute([':id' => (int) $reset['id']]);

            // Por defensa, invalidamos cualquier OTRO token activo del usuario.
            $stmt = $pdo->prepare(
                'UPDATE password_resets SET used_at = NOW()
                  WHERE user_id = :uid AND used_at IS NULL AND id <> :current'
            );
            $stmt->execute([
                ':uid'     => (int) $reset['user_id'],
                ':current' => (int) $reset['id'],
            ]);

            // Limpia intentos fallidos previos del rate-limit de login para que pueda
            // entrar inmediatamente con la nueva contraseña.
            $stmt = $pdo->prepare(
                'DELETE FROM login_attempts
                  WHERE succeeded = FALSE AND attempted_at > NOW() - INTERVAL \'1 hour\'
                    AND email = (SELECT email FROM users WHERE id = :uid)'
            );
            $stmt->execute([':uid' => (int) $reset['user_id']]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[wca] PasswordReset::reset falló: ' . $e->getMessage());
            View::render('auth/reset', [
                'token'  => $token,
                'errors' => ['general' => 'No se pudo actualizar la contraseña. Intenta de nuevo.'],
            ]);
            return;
        }

        View::render('auth/reset-done', []);
    }

    // ----------------------------------------------------------------

    /** @return array<string,mixed>|null */
    private static function findActiveAdminByEmail(string $email): ?array
    {
        $stmt = Connection::get()->prepare(
            "SELECT id, name, email, role
               FROM users
              WHERE email = :e AND role = 'admin' AND is_active = TRUE
              LIMIT 1"
        );
        $stmt->execute([':e' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static function isRateLimited(int $userId, string $email): bool
    {
        $stmt = Connection::get()->prepare(
            "SELECT COUNT(*) FROM password_resets
              WHERE user_id = :uid AND requested_at > NOW() - INTERVAL '1 hour'"
        );
        $stmt->execute([':uid' => $userId]);
        $count = (int) $stmt->fetchColumn();
        return $count >= self::MAX_REQUESTS_PER_HOUR;
    }

    /** @return array<string,mixed>|null */
    private static function findValidReset(string $tokenPlain): ?array
    {
        if (preg_match('/^[a-f0-9]{64}$/', $tokenPlain) !== 1) {
            return null;
        }
        $hash = hash('sha256', $tokenPlain);

        $stmt = Connection::get()->prepare(
            'SELECT id, user_id, expires_at, used_at
               FROM password_resets
              WHERE token_hash = :h
                AND used_at IS NULL
                AND expires_at > NOW()
              LIMIT 1'
        );
        $stmt->execute([':h' => $hash]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
