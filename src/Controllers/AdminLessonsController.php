<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Database\Connection;
use App\Upload;
use App\View;
use RuntimeException;

final class AdminLessonsController
{
    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireAdmin();

        $programId = self::parseId($params['programId'] ?? '');
        $program   = self::findProgram($programId);
        if (!$program) {
            self::redirect404();
            return;
        }

        $stmt = Connection::get()->prepare(
            'SELECT id, day_number, title, is_published
               FROM lessons
              WHERE program_id = :pid
              ORDER BY day_number ASC'
        );
        $stmt->execute([':pid' => $programId]);
        $lessons = $stmt->fetchAll();

        View::render('admin/lessons/index', [
            'program' => $program,
            'lessons' => $lessons,
            'flash'   => self::popFlash(),
        ]);
    }

    /** @param array<string,string> $params */
    public function create(array $params): void
    {
        Auth::requireAdmin();

        $programId = self::parseId($params['programId'] ?? '');
        $program   = self::findProgram($programId);
        if (!$program) {
            self::redirect404();
            return;
        }

        $nextDay = (int) Connection::get()
            ->query('SELECT COALESCE(MAX(day_number), 0) + 1 FROM lessons WHERE program_id = ' . (int) $programId)
            ->fetchColumn();

        View::render('admin/lessons/form', [
            'mode'    => 'create',
            'program' => $program,
            'lesson'  => null,
            'errors'  => [],
            'old'     => [
                'day_number'        => (string) $nextDay,
                'title'             => '',
                'objective'         => '',
                'post_text'         => '',
                'story_text'        => '',
                'conversation_text' => '',
                'action_text'       => '',
                'tip_text'          => '',
                'checklist_text'    => '',
                'is_published'      => '',
                'video_url'         => '',
                'download_url'      => '',
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function store(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $programId = self::parseId($params['programId'] ?? '');
        $program   = self::findProgram($programId);
        if (!$program) {
            self::redirect404();
            return;
        }

        $data     = self::extractInput();
        $errors   = self::validate($data, $programId, currentId: null);
        $imageUrl = '';

        if ($errors === []) {
            try {
                $imageUrl = Upload::image($_FILES['image'] ?? null);
            } catch (RuntimeException $e) {
                $errors['image'] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            View::render('admin/lessons/form', [
                'mode'    => 'create',
                'program' => $program,
                'lesson'  => null,
                'errors'  => $errors,
                'old'     => $data,
            ]);
            return;
        }

        $checklist = self::checklistFromText($data['checklist_text']);

        $stmt = Connection::get()->prepare(
            'INSERT INTO lessons (program_id, day_number, title, objective,
                post_text, story_text, conversation_text, action_text, tip_text,
                image_url, video_url, download_url, checklist_items, is_published)
             VALUES (:pid, :day, :t, :obj, :post, :story, :conv, :act, :tip,
                :img, :video, :download, :chk::jsonb, :pub)'
        );
        $stmt->execute([
            ':pid'      => $programId,
            ':day'      => (int) $data['day_number'],
            ':t'        => $data['title'],
            ':obj'      => self::nullable($data['objective']),
            ':post'     => self::nullable($data['post_text']),
            ':story'    => self::nullable($data['story_text']),
            ':conv'     => self::nullable($data['conversation_text']),
            ':act'      => self::nullable($data['action_text']),
            ':tip'      => self::nullable($data['tip_text']),
            ':img'      => $imageUrl !== '' ? $imageUrl : null,
            ':video'    => self::nullable($data['video_url']),
            ':download' => self::nullable($data['download_url']),
            ':chk'      => json_encode($checklist, JSON_UNESCAPED_UNICODE),
            ':pub'      => $data['is_published'] === '1' ? 't' : 'f',
        ]);

        self::setFlash('Lección creada.');
        View::redirect('/admin/programas/' . $programId . '/lecciones');
    }

    /** @param array<string,string> $params */
    public function edit(array $params): void
    {
        Auth::requireAdmin();

        $id = self::parseId($params['id'] ?? '');
        $lesson = self::findLesson($id);
        if (!$lesson) {
            self::redirect404();
            return;
        }
        $program = self::findProgram((int) $lesson['program_id']);
        if (!$program) {
            self::redirect404();
            return;
        }

        View::render('admin/lessons/form', [
            'mode'    => 'edit',
            'program' => $program,
            'lesson'  => $lesson,
            'errors'  => [],
            'old'     => [
                'day_number'        => (string) $lesson['day_number'],
                'title'             => (string) $lesson['title'],
                'objective'         => (string) $lesson['objective'],
                'post_text'         => (string) $lesson['post_text'],
                'story_text'        => (string) $lesson['story_text'],
                'conversation_text' => (string) $lesson['conversation_text'],
                'action_text'       => (string) $lesson['action_text'],
                'tip_text'          => (string) $lesson['tip_text'],
                'checklist_text'    => self::checklistToText($lesson['checklist_items']),
                'is_published'      => $lesson['is_published'] ? '1' : '',
                'video_url'         => (string) ($lesson['video_url']    ?? ''),
                'download_url'      => (string) ($lesson['download_url'] ?? ''),
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $id = self::parseId($params['id'] ?? '');
        $lesson = self::findLesson($id);
        if (!$lesson) {
            self::redirect404();
            return;
        }
        $programId = (int) $lesson['program_id'];
        $program   = self::findProgram($programId);
        if (!$program) {
            self::redirect404();
            return;
        }

        $data   = self::extractInput();
        $errors = self::validate($data, $programId, currentId: $id);

        $imageUrl = $lesson['image_url'];
        if ($errors === []) {
            try {
                $newImage = Upload::image($_FILES['image'] ?? null);
                if ($newImage !== '') {
                    Upload::deleteImage($lesson['image_url']);
                    $imageUrl = $newImage;
                }
            } catch (RuntimeException $e) {
                $errors['image'] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            View::render('admin/lessons/form', [
                'mode'    => 'edit',
                'program' => $program,
                'lesson'  => $lesson,
                'errors'  => $errors,
                'old'     => $data,
            ]);
            return;
        }

        $checklist = self::checklistFromText($data['checklist_text']);

        $stmt = Connection::get()->prepare(
            'UPDATE lessons
                SET day_number = :day,
                    title = :t,
                    objective = :obj,
                    post_text = :post,
                    story_text = :story,
                    conversation_text = :conv,
                    action_text = :act,
                    tip_text = :tip,
                    image_url = :img,
                    video_url = :video,
                    download_url = :download,
                    checklist_items = :chk::jsonb,
                    is_published = :pub
              WHERE id = :id'
        );
        $stmt->execute([
            ':day'      => (int) $data['day_number'],
            ':t'        => $data['title'],
            ':obj'      => self::nullable($data['objective']),
            ':post'     => self::nullable($data['post_text']),
            ':story'    => self::nullable($data['story_text']),
            ':conv'     => self::nullable($data['conversation_text']),
            ':act'      => self::nullable($data['action_text']),
            ':tip'      => self::nullable($data['tip_text']),
            ':img'      => $imageUrl !== '' && $imageUrl !== null ? $imageUrl : null,
            ':video'    => self::nullable($data['video_url']),
            ':download' => self::nullable($data['download_url']),
            ':chk'      => json_encode($checklist, JSON_UNESCAPED_UNICODE),
            ':pub'      => $data['is_published'] === '1' ? 't' : 'f',
            ':id'       => $id,
        ]);

        self::setFlash('Lección actualizada.');
        View::redirect('/admin/programas/' . $programId . '/lecciones');
    }

    /** @param array<string,string> $params */
    public function confirmDestroy(array $params): void
    {
        Auth::requireAdmin();

        $id = self::parseId($params['id'] ?? '');
        $lesson = self::findLesson($id);
        if (!$lesson) {
            self::redirect404();
            return;
        }
        $program = self::findProgram((int) $lesson['program_id']);
        if (!$program) {
            self::redirect404();
            return;
        }

        View::render('admin/lessons/delete', [
            'program' => $program,
            'lesson'  => $lesson,
        ]);
    }

    /** @param array<string,string> $params */
    public function destroy(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $id = self::parseId($params['id'] ?? '');
        $lesson = self::findLesson($id);
        if (!$lesson) {
            self::redirect404();
            return;
        }
        $programId = (int) $lesson['program_id'];

        Upload::deleteImage(is_string($lesson['image_url']) ? $lesson['image_url'] : null);

        $stmt = Connection::get()->prepare('DELETE FROM lessons WHERE id = :id');
        $stmt->execute([':id' => $id]);

        self::setFlash('Lección eliminada.');
        View::redirect('/admin/programas/' . $programId . '/lecciones');
    }

    // ----------------------------------------------------------------

    /**
     * @return array{
     *   day_number:string, title:string, objective:string,
     *   post_text:string, story_text:string, conversation_text:string,
     *   action_text:string, tip_text:string, checklist_text:string,
     *   is_published:string, video_url:string, download_url:string
     * }
     */
    private static function extractInput(): array
    {
        return [
            'day_number'        => trim((string) ($_POST['day_number'] ?? '')),
            'title'             => trim((string) ($_POST['title'] ?? '')),
            'objective'         => trim((string) ($_POST['objective'] ?? '')),
            'post_text'         => (string) ($_POST['post_text'] ?? ''),
            'story_text'        => (string) ($_POST['story_text'] ?? ''),
            'conversation_text' => (string) ($_POST['conversation_text'] ?? ''),
            'action_text'       => (string) ($_POST['action_text'] ?? ''),
            'tip_text'          => (string) ($_POST['tip_text'] ?? ''),
            'checklist_text'    => (string) ($_POST['checklist_text'] ?? ''),
            'is_published'      => isset($_POST['is_published']) ? '1' : '',
            'video_url'         => trim((string) ($_POST['video_url'] ?? '')),
            'download_url'      => trim((string) ($_POST['download_url'] ?? '')),
        ];
    }

    /**
     * @param array<string,string> $data
     * @return array<string,string>
     */
    private static function validate(array $data, int $programId, ?int $currentId): array
    {
        $errors = [];

        if (preg_match('/^[1-9][0-9]{0,3}$/', $data['day_number']) !== 1) {
            $errors['day_number'] = 'Día inválido (1 a 9999).';
        }
        if ($data['title'] === '' || mb_strlen($data['title']) > 200) {
            $errors['title'] = 'Título obligatorio (máx. 200 caracteres).';
        }
        foreach (['objective', 'post_text', 'story_text', 'conversation_text', 'action_text', 'tip_text'] as $f) {
            if (mb_strlen($data[$f]) > 8000) {
                $errors[$f] = 'Texto demasiado largo (máx. 8000 caracteres).';
            }
        }
        if (mb_strlen($data['checklist_text']) > 4000) {
            $errors['checklist_text'] = 'Checklist demasiado larga (máx. 4000 caracteres).';
        }

        if ($data['video_url'] !== '') {
            if (mb_strlen($data['video_url']) > 500) {
                $errors['video_url'] = 'URL de video demasiado larga (máx. 500 caracteres).';
            } elseif (\App\Embed::parseVideo($data['video_url']) === null) {
                $errors['video_url'] = 'URL inválida. Solo se aceptan YouTube y Vimeo.';
            }
        }

        if ($data['download_url'] !== '') {
            if (mb_strlen($data['download_url']) > 500) {
                $errors['download_url'] = 'URL de descarga demasiado larga (máx. 500 caracteres).';
            } elseif (\App\Embed::sanitizeDownloadUrl($data['download_url']) === null) {
                $errors['download_url'] = 'URL inválida. Solo se aceptan links de Google Drive.';
            }
        }

        if (!isset($errors['day_number'])) {
            if (self::dayExistsExcept($programId, (int) $data['day_number'], $currentId)) {
                $errors['day_number'] = 'Ya existe una lección con ese día en este programa.';
            }
        }

        return $errors;
    }

    private static function dayExistsExcept(int $programId, int $day, ?int $excludeId): bool
    {
        $sql  = 'SELECT 1 FROM lessons WHERE program_id = :pid AND day_number = :day';
        $args = [':pid' => $programId, ':day' => $day];
        if ($excludeId !== null) {
            $sql .= ' AND id <> :id';
            $args[':id'] = $excludeId;
        }
        $stmt = Connection::get()->prepare($sql . ' LIMIT 1');
        $stmt->execute($args);
        return (bool) $stmt->fetchColumn();
    }

    /** @return array<int,string> */
    private static function checklistFromText(string $text): array
    {
        $items = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '') {
                $items[] = mb_substr($line, 0, 200);
            }
            if (count($items) >= 20) {
                break;
            }
        }
        return $items;
    }

    private static function checklistToText(mixed $jsonb): string
    {
        if (is_string($jsonb)) {
            $arr = json_decode($jsonb, true);
        } else {
            $arr = is_array($jsonb) ? $jsonb : [];
        }
        if (!is_array($arr)) {
            return '';
        }
        $lines = [];
        foreach ($arr as $item) {
            if (is_string($item) && $item !== '') {
                $lines[] = $item;
            }
        }
        return implode("\n", $lines);
    }

    private static function nullable(string $v): ?string
    {
        return $v === '' ? null : $v;
    }

    private static function parseId(string $raw): int
    {
        return (preg_match('/^[1-9][0-9]{0,9}$/', $raw) === 1) ? (int) $raw : 0;
    }

    /** @return array<string,mixed>|null */
    private static function findProgram(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = Connection::get()->prepare('SELECT id, slug, title FROM programs WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    private static function findLesson(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = Connection::get()->prepare('SELECT * FROM lessons WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static function redirect404(): void
    {
        http_response_code(404);
        require dirname(__DIR__, 2) . '/templates/errors/404.php';
    }

    private static function setFlash(string $msg, string $type = 'success'): void
    {
        $_SESSION['_flash'] = ['type' => $type, 'msg' => $msg];
    }

    /** @return array{type:string,msg:string}|null */
    private static function popFlash(): ?array
    {
        if (!isset($_SESSION['_flash'])) {
            return null;
        }
        $flash = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return is_array($flash) ? $flash : null;
    }
}
