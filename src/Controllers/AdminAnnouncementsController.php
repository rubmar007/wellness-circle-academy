<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Database\Connection;
use App\View;

final class AdminAnnouncementsController
{
    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireAdmin();

        $announcements = Connection::get()->query(
            'SELECT id, kind, title, body, is_published, created_at
               FROM announcements
              ORDER BY created_at DESC'
        )->fetchAll();

        View::render('admin/announcements/index', [
            'announcements' => $announcements,
            'kinds'         => AnnouncementController::KINDS,
            'flash'         => self::popFlash(),
        ]);
    }

    /** @param array<string,string> $params */
    public function create(array $params): void
    {
        Auth::requireAdmin();

        View::render('admin/announcements/form', [
            'mode'         => 'create',
            'announcement' => null,
            'kinds'        => AnnouncementController::KINDS,
            'errors'       => [],
            'old'          => [
                'kind'         => 'reconocimiento',
                'title'        => '',
                'body'         => '',
                'is_published' => '1',
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function store(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $data   = self::extractInput();
        $errors = self::validate($data);

        if ($errors !== []) {
            View::render('admin/announcements/form', [
                'mode'         => 'create',
                'announcement' => null,
                'kinds'        => AnnouncementController::KINDS,
                'errors'       => $errors,
                'old'          => $data,
            ]);
            return;
        }

        $stmt = Connection::get()->prepare(
            'INSERT INTO announcements (kind, title, body, is_published)
             VALUES (:k, :t, :b, :p)'
        );
        $stmt->execute([
            ':k' => $data['kind'],
            ':t' => $data['title'],
            ':b' => $data['body'] !== '' ? $data['body'] : null,
            ':p' => $data['is_published'] === '1' ? 't' : 'f',
        ]);

        self::setFlash('Notificación creada.');
        View::redirect('/admin/notificaciones');
    }

    /** @param array<string,string> $params */
    public function edit(array $params): void
    {
        Auth::requireAdmin();
        $id = self::parseId($params['id'] ?? '');
        $announcement = self::findAnnouncement($id);
        if (!$announcement) {
            self::redirect404();
            return;
        }

        View::render('admin/announcements/form', [
            'mode'         => 'edit',
            'announcement' => $announcement,
            'kinds'        => AnnouncementController::KINDS,
            'errors'       => [],
            'old'          => [
                'kind'         => (string) $announcement['kind'],
                'title'        => (string) $announcement['title'],
                'body'         => (string) ($announcement['body'] ?? ''),
                'is_published' => $announcement['is_published'] ? '1' : '',
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $id = self::parseId($params['id'] ?? '');
        $announcement = self::findAnnouncement($id);
        if (!$announcement) {
            self::redirect404();
            return;
        }

        $data   = self::extractInput();
        $errors = self::validate($data);

        if ($errors !== []) {
            View::render('admin/announcements/form', [
                'mode'         => 'edit',
                'announcement' => $announcement,
                'kinds'        => AnnouncementController::KINDS,
                'errors'       => $errors,
                'old'          => $data,
            ]);
            return;
        }

        $stmt = Connection::get()->prepare(
            'UPDATE announcements
                SET kind = :k, title = :t, body = :b, is_published = :p
              WHERE id = :id'
        );
        $stmt->execute([
            ':k'  => $data['kind'],
            ':t'  => $data['title'],
            ':b'  => $data['body'] !== '' ? $data['body'] : null,
            ':p'  => $data['is_published'] === '1' ? 't' : 'f',
            ':id' => $id,
        ]);

        self::setFlash('Notificación actualizada.');
        View::redirect('/admin/notificaciones');
    }

    /** @param array<string,string> $params */
    public function confirmDestroy(array $params): void
    {
        Auth::requireAdmin();

        $id = self::parseId($params['id'] ?? '');
        $announcement = self::findAnnouncement($id);
        if (!$announcement) {
            self::redirect404();
            return;
        }

        View::render('admin/announcements/delete', [
            'announcement' => $announcement,
        ]);
    }

    /** @param array<string,string> $params */
    public function destroy(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $id = self::parseId($params['id'] ?? '');
        $announcement = self::findAnnouncement($id);
        if (!$announcement) {
            self::redirect404();
            return;
        }

        $stmt = Connection::get()->prepare('DELETE FROM announcements WHERE id = :id');
        $stmt->execute([':id' => $id]);

        self::setFlash('Notificación eliminada.');
        View::redirect('/admin/notificaciones');
    }

    // ----------------------------------------------------------------

    /** @return array{kind:string,title:string,body:string,is_published:string} */
    private static function extractInput(): array
    {
        return [
            'kind'         => trim((string) ($_POST['kind']  ?? '')),
            'title'        => trim((string) ($_POST['title'] ?? '')),
            'body'         => trim((string) ($_POST['body']  ?? '')),
            'is_published' => isset($_POST['is_published']) ? '1' : '',
        ];
    }

    /**
     * @param array{kind:string,title:string,body:string,is_published:string} $data
     * @return array<string,string>
     */
    private static function validate(array $data): array
    {
        $errors = [];
        if (!isset(AnnouncementController::KINDS[$data['kind']])) {
            $errors['kind'] = 'Selecciona un tipo válido.';
        }
        if ($data['title'] === '' || mb_strlen($data['title']) > 200) {
            $errors['title'] = 'Título obligatorio (máx. 200 caracteres).';
        }
        if ($data['body'] !== '' && mb_strlen($data['body']) > 4000) {
            $errors['body'] = 'El cuerpo no puede exceder 4000 caracteres.';
        }
        return $errors;
    }

    private static function parseId(string $raw): int
    {
        return (preg_match('/^[1-9][0-9]{0,18}$/', $raw) === 1) ? (int) $raw : 0;
    }

    /** @return array<string,mixed>|null */
    private static function findAnnouncement(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = Connection::get()->prepare('SELECT * FROM announcements WHERE id = :id');
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
