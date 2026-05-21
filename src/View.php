<?php

declare(strict_types=1);

namespace App;

use App\Support\Env;

final class View
{
    /**
     * Renderiza un template PHP envolviéndolo en el layout por defecto.
     *
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = [], ?string $layout = 'layout'): void
    {
        $content = self::renderPartial($template, $data);

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutData = array_merge($data, ['content' => $content]);
        echo self::renderPartial($layout, $layoutData);
    }

    /**
     * Renderiza un template sin envolverlo en layout. Devuelve el HTML capturado.
     *
     * @param array<string, mixed> $data
     */
    public static function renderPartial(string $template, array $data = []): string
    {
        $path = self::resolvePath($template);

        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('Template no encontrado: %s', $template));
        }

        $auth   = Auth::user();
        $csrf   = Csrf::token();
        $nonce  = Security::nonce();
        $appName = Env::get('APP_NAME', 'Wellness Circle Academy');

        ob_start();
        // Variables disponibles dentro de la plantilla:
        //   $auth (?array), $csrf (string), $nonce (string), $appName (string),
        //   y todas las claves de $data.
        extract($data, EXTR_SKIP);
        require $path;
        return (string) ob_get_clean();
    }

    private static function resolvePath(string $template): string
    {
        $relative = ltrim($template, '/');
        if (!str_ends_with($relative, '.php')) {
            $relative .= '.php';
        }
        return dirname(__DIR__) . '/templates/' . $relative;
    }

    public static function redirect(string $location, int $status = 302): void
    {
        header('Location: ' . $location, true, $status);
        exit;
    }

    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
