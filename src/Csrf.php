<?php

declare(strict_types=1);

namespace App;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['_csrf'];
    }

    public static function verify(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $expected = $_SESSION['_csrf'] ?? null;

        if (!is_string($expected) || $expected === '') {
            return false;
        }

        return hash_equals($expected, $token);
    }

    public static function requireValid(): void
    {
        $token = $_POST['_csrf'] ?? null;

        if (!is_string($token) || !self::verify($token)) {
            http_response_code(419);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'CSRF token inválido o ausente.';
            exit;
        }
    }

    public static function hiddenField(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<input type="hidden" name="_csrf" value="' . $token . '">';
    }
}
