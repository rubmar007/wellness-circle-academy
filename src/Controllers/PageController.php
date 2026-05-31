<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database\Connection;
use App\View;

final class PageController
{
    /** @param array<string,string> $params */
    public function show(array $params): void
    {
        Auth::requireLogin();

        $stmt = Connection::get()->prepare(
            'SELECT slug, title, body, updated_at FROM pages WHERE slug = :s LIMIT 1'
        );
        $stmt->execute([':s' => 'normas']);
        $page = $stmt->fetch();

        if (!$page) {
            self::redirect404();
            return;
        }

        View::render('pages/show', [
            'page' => $page,
        ]);
    }

    private static function redirect404(): void
    {
        http_response_code(404);
        require dirname(__DIR__, 2) . '/templates/errors/404.php';
    }
}
