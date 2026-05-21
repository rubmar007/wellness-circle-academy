<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database\Connection;
use App\View;

final class LessonController
{
    /** @param array<string,string> $params */
    public function show(array $params): void
    {
        Auth::requireLogin();

        $slug = (string) ($params['slug'] ?? '');
        $day  = (string) ($params['day']  ?? '');

        if (preg_match('/^[a-z0-9-]{1,80}$/', $slug) !== 1) {
            http_response_code(404);
            require dirname(__DIR__, 2) . '/templates/errors/404.php';
            return;
        }
        if (preg_match('/^[1-9][0-9]{0,3}$/', $day) !== 1) {
            http_response_code(404);
            require dirname(__DIR__, 2) . '/templates/errors/404.php';
            return;
        }

        $pdo = Connection::get();

        $stmt = $pdo->prepare(
            'SELECT p.id   AS program_id,
                    p.slug AS program_slug,
                    p.title AS program_title,
                    l.id   AS lesson_id,
                    l.day_number,
                    l.title,
                    l.objective,
                    l.post_text,
                    l.story_text,
                    l.conversation_text,
                    l.action_text,
                    l.tip_text,
                    l.image_url,
                    l.video_url,
                    l.download_url,
                    l.checklist_items
               FROM lessons l
               JOIN programs p ON p.id = l.program_id
              WHERE p.slug = :slug
                AND p.is_published = TRUE
                AND l.day_number = :day
                AND l.is_published = TRUE
              LIMIT 1'
        );
        $stmt->execute([
            ':slug' => $slug,
            ':day'  => (int) $day,
        ]);
        $lesson = $stmt->fetch();

        if (!$lesson) {
            http_response_code(404);
            require dirname(__DIR__, 2) . '/templates/errors/404.php';
            return;
        }

        // Lecciones adyacentes para navegación.
        $stmt = $pdo->prepare(
            'SELECT day_number FROM lessons
              WHERE program_id = :pid AND is_published = TRUE AND day_number < :day
              ORDER BY day_number DESC LIMIT 1'
        );
        $stmt->execute([':pid' => (int) $lesson['program_id'], ':day' => (int) $day]);
        $prevDay = $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT day_number FROM lessons
              WHERE program_id = :pid AND is_published = TRUE AND day_number > :day
              ORDER BY day_number ASC LIMIT 1'
        );
        $stmt->execute([':pid' => (int) $lesson['program_id'], ':day' => (int) $day]);
        $nextDay = $stmt->fetchColumn();

        // Progreso del usuario en esta lección.
        $userId = (int) Auth::user()['id'];
        $stmt = $pdo->prepare(
            'SELECT item_index FROM user_progress WHERE user_id = :uid AND lesson_id = :lid'
        );
        $stmt->execute([':uid' => $userId, ':lid' => (int) $lesson['lesson_id']]);
        $completed = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));

        $checklist = is_string($lesson['checklist_items'])
            ? (json_decode($lesson['checklist_items'], true) ?: [])
            : (is_array($lesson['checklist_items']) ? $lesson['checklist_items'] : []);

        View::render('lessons/show', [
            'lesson'     => $lesson,
            'checklist'  => $checklist,
            'completed'  => $completed,
            'prev_day'   => $prevDay !== false ? (int) $prevDay : null,
            'next_day'   => $nextDay !== false ? (int) $nextDay : null,
        ]);
    }
}
