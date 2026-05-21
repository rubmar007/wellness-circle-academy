<?php

declare(strict_types=1);

namespace App;

use App\Database\Connection;
use PDO;

final class Auth
{
    private const MAX_LOGIN_ATTEMPTS_PER_EMAIL = 5;
    private const MAX_LOGIN_ATTEMPTS_PER_IP    = 20;
    private const LOCKOUT_WINDOW_MINUTES       = 15;

    /** @var array<string, mixed>|null */
    private static ?array $cachedUser = null;
    private static bool   $cacheSet   = false;

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (self::$cacheSet) {
            return self::$cachedUser;
        }

        self::$cacheSet = true;

        if (empty($_SESSION['user_id'])) {
            self::$cachedUser = null;
            return null;
        }

        $stmt = Connection::get()->prepare(
            'SELECT id, name, email, role, is_active FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => (int) $_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || !$user['is_active']) {
            self::logout();
            self::$cachedUser = null;
            return null;
        }

        self::$cachedUser = $user;
        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user !== null && $user['role'] === 'admin';
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            View::redirect('/login');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            require dirname(__DIR__) . '/templates/errors/403.php';
            exit;
        }
    }

    /**
     * Intenta iniciar sesión. Devuelve un array con el resultado:
     *   ['ok' => bool, 'error' => string|null]
     *
     * @return array{ok: bool, error: string|null}
     */
    public static function attemptLogin(string $email, string $password): array
    {
        $email = mb_strtolower(trim($email));
        $ip    = Security::clientIp();

        if (self::isRateLimited($email, $ip)) {
            return ['ok' => false, 'error' => 'Demasiados intentos. Espera unos minutos.'];
        }

        $stmt = Connection::get()->prepare(
            'SELECT id, password_hash, is_active FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        $valid = false;
        if ($user && $user['is_active']) {
            $valid = password_verify($password, (string) $user['password_hash']);
        } else {
            // Hash dummy para evitar timing attacks que diferencien email inexistente vs. password incorrecto.
            password_verify($password, '$2y$12$0000000000000000000000.0000000000000000000000000000000000');
        }

        self::recordAttempt($email, $ip, $valid);

        if (!$valid) {
            return ['ok' => false, 'error' => 'Email o contraseña incorrectos.'];
        }

        // Renueva el id de sesión para evitar session fixation.
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];

        // Refrescar el password si el algoritmo recomendado ha cambiado.
        if (password_needs_rehash((string) $user['password_hash'], self::passwordAlgo(), self::passwordOptions())) {
            $newHash = password_hash($password, self::passwordAlgo(), self::passwordOptions());
            $update  = Connection::get()->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
            $update->execute([':h' => $newHash, ':id' => (int) $user['id']]);
        }

        return ['ok' => true, 'error' => null];
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires'  => time() - 42000,
                    'path'     => $params['path']     ?? '/',
                    'domain'   => $params['domain']   ?? '',
                    'secure'   => (bool) ($params['secure']   ?? false),
                    'httponly' => (bool) ($params['httponly'] ?? true),
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        self::$cachedUser = null;
        self::$cacheSet   = false;
    }

    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, self::passwordAlgo(), self::passwordOptions());
    }

    public static function createUser(string $name, string $email, string $password, string $role = 'member'): int
    {
        $hash  = self::hashPassword($password);
        $email = mb_strtolower(trim($email));

        $stmt = Connection::get()->prepare(
            'INSERT INTO users (name, email, password_hash, role) VALUES (:n, :e, :h, :r) RETURNING id'
        );
        $stmt->execute([
            ':n' => $name,
            ':e' => $email,
            ':h' => $hash,
            ':r' => $role,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public static function emailExists(string $email): bool
    {
        $email = mb_strtolower(trim($email));
        $stmt  = Connection::get()->prepare('SELECT 1 FROM users WHERE email = :e LIMIT 1');
        $stmt->execute([':e' => $email]);
        return (bool) $stmt->fetchColumn();
    }

    // ----------------- Rate limiting -----------------

    private static function isRateLimited(string $email, string $ip): bool
    {
        $pdo = Connection::get();

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM login_attempts
              WHERE email = :e AND succeeded = FALSE
                AND attempted_at > NOW() - (:m || ' minutes')::interval"
        );
        $stmt->bindValue(':e', $email);
        $stmt->bindValue(':m', (string) self::LOCKOUT_WINDOW_MINUTES);
        $stmt->execute();
        $perEmail = (int) $stmt->fetchColumn();

        if ($perEmail >= self::MAX_LOGIN_ATTEMPTS_PER_EMAIL) {
            return true;
        }

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM login_attempts
              WHERE ip_address = :ip AND succeeded = FALSE
                AND attempted_at > NOW() - (:m || ' minutes')::interval"
        );
        $stmt->bindValue(':ip', $ip);
        $stmt->bindValue(':m', (string) self::LOCKOUT_WINDOW_MINUTES);
        $stmt->execute();
        $perIp = (int) $stmt->fetchColumn();

        return $perIp >= self::MAX_LOGIN_ATTEMPTS_PER_IP;
    }

    private static function recordAttempt(string $email, string $ip, bool $succeeded): void
    {
        $stmt = Connection::get()->prepare(
            'INSERT INTO login_attempts (email, ip_address, succeeded) VALUES (:e, :ip, :s)'
        );
        $stmt->bindValue(':e',  $email);
        $stmt->bindValue(':ip', $ip);
        $stmt->bindValue(':s',  $succeeded, PDO::PARAM_BOOL);
        $stmt->execute();
    }

    private static function passwordAlgo(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
    }

    /** @return array<string, int> */
    private static function passwordOptions(): array
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return [
                'memory_cost' => 65536,
                'time_cost'   => 4,
                'threads'     => 1,
            ];
        }
        return ['cost' => 12];
    }
}
