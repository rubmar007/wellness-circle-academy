<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Database\Connection;
use App\Upload;
use App\View;

final class AdminNoticiasController
{
    private const TABLE = 'noticias';
    private const BASE  = '/admin/noticias';

    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireAdmin();

        $rows = Connection::get()->query(
            'SELECT id, title, image_path, video_url, link_url, is_published, created_at
               FROM ' . self::TABLE . '
              ORDER BY created_at DESC'
        )->fetchAll();

        View::render('admin/noticias/index', [
            'noticias' => $rows,
            'flash'    => self::popFlash(),
        ]);
    }

    /** @param array<string,string> $params */
    public function create(array $params): void
    {
        Auth::requireAdmin();
        View::render('admin/noticias/form', [
            'mode'    => 'create',
            'noticia' => null,
            'errors'  => [],
            'old'     => self::emptyOld(),
        ]);
    }

    /** @param array<string,string> $params */
    public function store(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $data   = self::extractInput();
        $errors = self::validate($data);

        $imagePath = '';
        if ($errors === []) {
            try {
                $imagePath = Upload::image($_FILES['image'] ?? null);
            } catch (\RuntimeException $e) {
                $errors['image'] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            View::render('admin/noticias/form', [
                'mode'    => 'create',
                'noticia' => null,
                'errors'  => $errors,
                'old'     => $data,
            ]);
            return;
        }

        $stmt = Connection::get()->prepare(
            'INSERT INTO ' . self::TABLE . '
                (title, body, image_path, video_url, link_url, link_label, is_published)
             VALUES (:t, :b, :img, :vid, :lu, :ll, :p)'
        );
        $stmt->execute([
            ':t'   => $data['title'],
            ':b'   => $data['body'] !== '' ? $data['body'] : null,
            ':img' => $imagePath !== '' ? $imagePath : null,
            ':vid' => $data['video_url'] !== '' ? $data['video_url'] : null,
            ':lu'  => $data['link_url'] !== '' ? $data['link_url'] : null,
            ':ll'  => $data['link_label'] !== '' ? $data['link_label'] : null,
            ':p'   => $data['is_published'] === '1' ? 't' : 'f',
        ]);

        self::setFlash('Noticia creada.');
        View::redirect(self::BASE);
    }

    /** @param array<string,string> $params */
    public function edit(array $params): void
    {
        Auth::requireAdmin();
        $noticia = self::findRow(self::parseId($params['id'] ?? ''));
        if (!$noticia) { self::render404(); return; }

        View::render('admin/noticias/form', [
            'mode'    => 'edit',
            'noticia' => $noticia,
            'errors'  => [],
            'old'     => [
                'title'        => (string) $noticia['title'],
                'body'         => (string) ($noticia['body'] ?? ''),
                'video_url'    => (string) ($noticia['video_url'] ?? ''),
                'link_url'     => (string) ($noticia['link_url'] ?? ''),
                'link_label'   => (string) ($noticia['link_label'] ?? ''),
                'is_published' => $noticia['is_published'] ? '1' : '',
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $id      = self::parseId($params['id'] ?? '');
        $noticia = self::findRow($id);
        if (!$noticia) { self::render404(); return; }

        $data   = self::extractInput();
        $errors = self::validate($data);

        $newImagePath = '';
        if ($errors === []) {
            try {
                $newImagePath = Upload::image($_FILES['image'] ?? null);
            } catch (\RuntimeException $e) {
                $errors['image'] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            View::render('admin/noticias/form', [
                'mode'    => 'edit',
                'noticia' => $noticia,
                'errors'  => $errors,
                'old'     => $data,
            ]);
            return;
        }

        $finalImage = $newImagePath !== '' ? $newImagePath : (string) ($noticia['image_path'] ?? '');

        if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
            Upload::deleteImage((string) ($noticia['image_path'] ?? ''));
            $finalImage = '';
        } elseif ($newImagePath !== '') {
            Upload::deleteImage((string) ($noticia['image_path'] ?? ''));
        }

        $stmt = Connection::get()->prepare(
            'UPDATE ' . self::TABLE . '
                SET title = :t, body = :b, image_path = :img,
                    video_url = :vid, link_url = :lu, link_label = :ll, is_published = :p
              WHERE id = :id'
        );
        $stmt->execute([
            ':t'   => $data['title'],
            ':b'   => $data['body'] !== '' ? $data['body'] : null,
            ':img' => $finalImage !== '' ? $finalImage : null,
            ':vid' => $data['video_url'] !== '' ? $data['video_url'] : null,
            ':lu'  => $data['link_url'] !== '' ? $data['link_url'] : null,
            ':ll'  => $data['link_label'] !== '' ? $data['link_label'] : null,
            ':p'   => $data['is_published'] === '1' ? 't' : 'f',
            ':id'  => $id,
        ]);

        self::setFlash('Noticia actualizada.');
        View::redirect(self::BASE);
    }

    /** @param array<string,string> $params */
    public function confirmDestroy(array $params): void
    {
        Auth::requireAdmin();
        $noticia = self::findRow(self::parseId($params['id'] ?? ''));
        if (!$noticia) { self::render404(); return; }

        View::render('admin/noticias/delete', ['noticia' => $noticia]);
    }

    /** @param array<string,string> $params */
    public function destroy(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $id      = self::parseId($params['id'] ?? '');
        $noticia = self::findRow($id);
        if (!$noticia) { self::render404(); return; }

        Upload::deleteImage((string) ($noticia['image_path'] ?? ''));

        $stmt = Connection::get()->prepare('DELETE FROM ' . self::TABLE . ' WHERE id = :id');
        $stmt->execute([':id' => $id]);

        self::setFlash('Noticia eliminada.');
        View::redirect(self::BASE);
    }

    // ----------------------------------------------------------------

    /** @return array<string,string> */
    private static function emptyOld(): array
    {
        return [
            'title'        => '',
            'body'         => '',
            'video_url'    => '',
            'link_url'     => '',
            'link_label'   => '',
            'is_published' => '1',
        ];
    }

    /** @return array<string,string> */
    private static function extractInput(): array
    {
        return [
            'title'        => trim((string) ($_POST['title']      ?? '')),
            'body'         => trim((string) ($_POST['body']       ?? '')),
            'video_url'    => trim((string) ($_POST['video_url']  ?? '')),
            'link_url'     => trim((string) ($_POST['link_url']   ?? '')),
            'link_label'   => trim((string) ($_POST['link_label'] ?? '')),
            'is_published' => isset($_POST['is_published']) ? '1' : '',
        ];
    }

    /**
     * @param array<string,string> $data
     * @return array<string,string>
     */
    private static function validate(array $data): array
    {
        $errors = [];
        if ($data['title'] === '' || mb_strlen($data['title']) > 200) {
            $errors['title'] = 'Título obligatorio (máx. 200 caracteres).';
        }
        if ($data['body'] !== '' && mb_strlen($data['body']) > 4000) {
            $errors['body'] = 'El cuerpo no puede exceder 4000 caracteres.';
        }
        if ($data['video_url'] !== '' && !filter_var($data['video_url'], FILTER_VALIDATE_URL)) {
            $errors['video_url'] = 'URL de video inválida.';
        }
        if ($data['link_url'] !== '' && !filter_var($data['link_url'], FILTER_VALIDATE_URL)) {
            $errors['link_url'] = 'URL del enlace inválida.';
        }
        if ($data['link_label'] !== '' && mb_strlen($data['link_label']) > 100) {
            $errors['link_label'] = 'Texto del botón máx. 100 caracteres.';
        }
        return $errors;
    }

    private static function parseId(string $raw): int
    {
        return (preg_match('/^[1-9][0-9]{0,18}$/', $raw) === 1) ? (int) $raw : 0;
    }

    /** @return array<string,mixed>|null */
    private static function findRow(int $id): ?array
    {
        if ($id <= 0) return null;
        $stmt = Connection::get()->prepare('SELECT * FROM ' . self::TABLE . ' WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static function render404(): void
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
        if (!isset($_SESSION['_flash'])) return null;
        $flash = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return is_array($flash) ? $flash : null;
    }
}
