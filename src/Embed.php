<?php

declare(strict_types=1);

namespace App;

/**
 * Convierte URLs públicas de YouTube y Vimeo en URLs aptas para <iframe>
 * (embed), o devuelve null si la URL no es de un proveedor aceptado.
 *
 * No descarga nada del proveedor; solo construye la URL de embed.
 * Acepta:
 *   - https://www.youtube.com/watch?v=VIDEO_ID
 *   - https://youtu.be/VIDEO_ID
 *   - https://www.youtube.com/embed/VIDEO_ID
 *   - https://www.youtube.com/shorts/VIDEO_ID
 *   - https://m.youtube.com/watch?v=VIDEO_ID
 *   - https://vimeo.com/VIDEO_ID
 *   - https://player.vimeo.com/video/VIDEO_ID
 *
 * Cualquier otro host devuelve null. Esto es necesario porque la CSP del
 * sitio solo permite frames de youtube-nocookie.com y player.vimeo.com.
 */
final class Embed
{
    /** @return array{provider:string, embed_url:string, title:string}|null */
    public static function parseVideo(?string $url): ?array
    {
        if ($url === null) {
            return null;
        }
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }
        if (mb_strtolower($parts['scheme']) !== 'https') {
            return null;
        }
        $host = mb_strtolower($parts['host']);

        // ---- YouTube ----
        $ytHosts = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be'];
        if (in_array($host, $ytHosts, true)) {
            $id = self::extractYoutubeId($host, $parts);
            if ($id === null) {
                return null;
            }
            return [
                'provider'  => 'youtube',
                'embed_url' => 'https://www.youtube-nocookie.com/embed/' . $id,
                'title'     => 'Video del día (YouTube)',
            ];
        }

        // ---- Vimeo ----
        $vimeoHosts = ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'];
        if (in_array($host, $vimeoHosts, true)) {
            $id = self::extractVimeoId($host, $parts);
            if ($id === null) {
                return null;
            }
            return [
                'provider'  => 'vimeo',
                'embed_url' => 'https://player.vimeo.com/video/' . $id,
                'title'     => 'Video del día (Vimeo)',
            ];
        }

        return null;
    }

    /** @param array<string,mixed> $parts */
    private static function extractYoutubeId(string $host, array $parts): ?string
    {
        $path  = isset($parts['path']) ? (string) $parts['path'] : '';
        $query = [];
        if (!empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
        }

        if ($host === 'youtu.be') {
            // https://youtu.be/VIDEO_ID
            $candidate = ltrim($path, '/');
            return self::validVideoId($candidate);
        }

        // youtube.com variantes
        if (preg_match('#^/embed/([A-Za-z0-9_-]{6,20})#', $path, $m) === 1) {
            return self::validVideoId($m[1]);
        }
        if (preg_match('#^/shorts/([A-Za-z0-9_-]{6,20})#', $path, $m) === 1) {
            return self::validVideoId($m[1]);
        }
        if ($path === '/watch' && isset($query['v']) && is_string($query['v'])) {
            return self::validVideoId($query['v']);
        }

        return null;
    }

    /** @param array<string,mixed> $parts */
    private static function extractVimeoId(string $host, array $parts): ?string
    {
        $path = isset($parts['path']) ? (string) $parts['path'] : '';
        // /video/123456 (player.vimeo.com) o /123456 (vimeo.com)
        if (preg_match('#^/(?:video/)?(\d{5,15})#', $path, $m) === 1) {
            return $m[1];
        }
        return null;
    }

    private static function validVideoId(string $id): ?string
    {
        // IDs de YouTube: 11 caracteres en práctica, alfanuméricos + _ -.
        // Aceptamos 6-20 por defensa contra cambios futuros.
        return preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id) === 1 ? $id : null;
    }

    /**
     * Valida una URL de descarga (Google Drive). Devuelve la URL original
     * si pasa la whitelist, o null si no es aceptable.
     *
     * No descarga; solo se usa como href en un <a target="_blank">.
     */
    public static function sanitizeDownloadUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }
        if (mb_strtolower($parts['scheme']) !== 'https') {
            return null;
        }
        $host = mb_strtolower($parts['host']);
        $allowed = ['drive.google.com', 'docs.google.com', 'drive.usercontent.google.com'];
        if (in_array($host, $allowed, true)) {
            return $url;
        }
        if (str_ends_with($host, '.googleusercontent.com')) {
            return $url;
        }
        return null;
    }
}
