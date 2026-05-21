<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Database\Connection;
use App\View;

final class ProgressController
{
    /** @param array<string,string> $params */
    public function toggle(array $params): void
    {
        Auth::requireLogin();
        Csrf::requireValid();

        $lessonId  = filter_input(INPUT_POST, 'lesson_id',  FILTER_VALIDATE_INT);
        $itemIndex = filter_input(INPUT_POST, 'item_index', FILTER_VALIDATE_INT);
        $action    = (string) ($_POST['action'] ?? '');
        $back      = (string) ($_POST['back']   ?? '/dashboard');

        if ($lessonId === false || $lessonId === null || $lessonId <= 0) {
            View::redirect($this->sanitizeBack($back));
        }
        if ($itemIndex === false || $itemIndex === null || $itemIndex < 0) {
            View::redirect($this->sanitizeBack($back));
        }
        if (!in_array($action, ['check', 'uncheck'], true)) {
            View::redirect($this->sanitizeBack($back));
        }

        $userId = (int) Auth::user()['id'];
        $pdo    = Connection::get();

        // Confirmar que la lección existe, está publicada, y que el ítem es válido.
        $stmt = $pdo->prepare(
            'SELECT jsonb_array_length(checklist_items) AS n
               FROM lessons
              WHERE id = :id AND is_published = TRUE
              LIMIT 1'
        );
        $stmt->execute([':id' => $lessonId]);
        $row = $stmt->fetch();

        if (!$row) {
            View::redirect($this->sanitizeBack($back));
        }

        $n = (int) $row['n'];
        if ($itemIndex >= $n) {
            View::redirect($this->sanitizeBack($back));
        }

        if ($action === 'check') {
            $stmt = $pdo->prepare(
                'INSERT INTO user_progress (user_id, lesson_id, item_index)
                 VALUES (:u, :l, :i)
                 ON CONFLICT (user_id, lesson_id, item_index) DO NOTHING'
            );
        } else {
            $stmt = $pdo->prepare(
                'DELETE FROM user_progress
                   WHERE user_id = :u AND lesson_id = :l AND item_index = :i'
            );
        }
        $stmt->execute([
            ':u' => $userId,
            ':l' => $lessonId,
            ':i' => $itemIndex,
        ]);

        View::redirect($this->sanitizeBack($back));
    }

    private function sanitizeBack(string $back): string
    {
        // Solo permitir redirecciones a rutas internas absolutas con un
        // conjunto restringido de caracteres. Bloquea // (open redirect),
        // protocol-relative URLs y cualquier carácter fuera del whitelist.
        if ($back === '' || $back[0] !== '/' || str_starts_with($back, '//')) {
            return '/dashboard';
        }
        if (preg_match('#^/[A-Za-z0-9_\-./#]*$#', $back) !== 1) {
            return '/dashboard';
        }
        return $back;
    }
}
