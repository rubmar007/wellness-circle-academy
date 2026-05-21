<?php

declare(strict_types=1);

namespace App;

final class Security
{
    private static ?string $nonce = null;

    public static function nonce(): string
    {
        if (self::$nonce === null) {
            self::$nonce = base64_encode(random_bytes(16));
        }

        return self::$nonce;
    }

    public static function applyHeaders(bool $isHttps): void
    {
        $nonce = self::nonce();

        // Content Security Policy estricta. El único script permitido es el inline
        // o externo del propio origen cuyo nonce coincida con el de la respuesta.
        // frame-src permite embeber videos de YouTube (sin cookies) y Vimeo.
        $csp = [
            "default-src 'self'",
            "base-uri 'self'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "img-src 'self' data:",
            "style-src 'self'",
            "font-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "object-src 'none'",
            "connect-src 'self'",
            "frame-src 'self' https://www.youtube-nocookie.com https://www.youtube.com https://player.vimeo.com",
        ];

        header('Content-Security-Policy: ' . implode('; ', $csp));
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: same-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');

        if ($isHttps) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        // Cabeceras de cache por defecto para respuestas dinámicas.
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }

    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
            return true;
        }

        if (((int) ($_SERVER['SERVER_PORT'] ?? 0)) === 443) {
            return true;
        }

        return false;
    }

    public static function clientIp(): string
    {
        // Confiamos en REMOTE_ADDR como base. En Railway hay proxy delante, pero
        // sólo se usa para rate limiting; falsificarlo no compromete seguridad.
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        return is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}
