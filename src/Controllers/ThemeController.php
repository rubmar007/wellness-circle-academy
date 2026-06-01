<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Csrf;
use App\Security;
use App\View;

/**
 * Cambia el tema visual de la interfaz. El tema se guarda en una cookie
 * (wca_theme) que el layout lee en cada request para poner data-theme en
 * <html>. Sin JavaScript: es un POST normal con redirect de vuelta.
 */
final class ThemeController
{
    /** Temas válidos. Debe coincidir con los bloques de styles.css y con layout.php. */
    public const THEMES = ['oscuro', 'claro', 'lifewave', 'marino'];
    public const COOKIE = 'wca_theme';
    public const DEFAULT = 'marino';

    /** @param array<string,string> $params */
    public function set(array $params): void
    {
        Csrf::requireValid();

        $theme = (string) ($_POST['theme'] ?? self::DEFAULT);
        if (!in_array($theme, self::THEMES, true)) {
            $theme = self::DEFAULT;
        }

        setcookie(self::COOKIE, $theme, [
            'expires'  => time() + 31_536_000, // 1 año
            'path'     => '/',
            'domain'   => '',
            'secure'   => Security::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        View::redirect(self::safeBack((string) ($_POST['back'] ?? '/dashboard')));
    }

    /**
     * Lee el tema actual desde la cookie. Valida contra la whitelist.
     * Pensado para llamarse desde el layout en cada request.
     */
    public static function current(): string
    {
        $theme = (string) ($_COOKIE[self::COOKIE] ?? self::DEFAULT);
        return in_array($theme, self::THEMES, true) ? $theme : self::DEFAULT;
    }

    /**
     * Solo permite rutas internas (empiezan con un solo "/"), evitando
     * open-redirect hacia "//host" o esquemas externos.
     */
    private static function safeBack(string $back): string
    {
        if ($back === '' || $back[0] !== '/' || str_starts_with($back, '//')) {
            return '/inicio';
        }
        // Sin saltos de línea ni control chars (anti header injection).
        if (preg_match('/[\x00-\x1F\x7F]/', $back) === 1) {
            return '/inicio';
        }
        return $back;
    }
}
