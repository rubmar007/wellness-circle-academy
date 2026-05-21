<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database\Connection;
use App\View;

final class ProgramController
{
    /** @param array<string,string> $params */
    public function show(array $params): void
    {
        Auth::requireLogin();

        $slug = (string) ($params['slug'] ?? '');
        if (preg_match('/^[a-z0-9-]{1,80}$/', $slug) !== 1) {
            http_response_code(404);
            require dirname(__DIR__, 2) . '/templates/errors/404.php';
            return;
        }

        $pdo = Connection::get();

        $stmt = $pdo->prepare(
            'SELECT id, slug, title, description, cover_image
               FROM programs
              WHERE slug = :slug AND is_published = TRUE
              LIMIT 1'
        );
        $stmt->execute([':slug' => $slug]);
        $program = $stmt->fetch();

        if (!$program) {
            http_response_code(404);
            require dirname(__DIR__, 2) . '/templates/errors/404.php';
            return;
        }

        $stmt = $pdo->prepare(
            'SELECT id, day_number, title, objective
               FROM lessons
              WHERE program_id = :pid AND is_published = TRUE
              ORDER BY day_number ASC'
        );
        $stmt->execute([':pid' => (int) $program['id']]);
        $lessons = $stmt->fetchAll();

        View::render('programs/show', [
            'program' => $program,
            'lessons' => $lessons,
        ]);
    }
}
