<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Database\Connection;
use App\Upload;
use App\View;
use PDO;
use RuntimeException;

final class AdminProgramsController
{
    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireAdmin();

        $programs = Connection::get()->query(
            'SELECT p.id, p.slug, p.title, p.description, p.cover_image, p.display_order, p.is_published,
                    (SELECT COUNT(*) FROM lessons l WHERE l.program_id = p.id) AS lesson_count
               FROM programs p
              ORDER BY p.display_order ASC, p.title ASC'
        )->fetchAll();

        View::render('admin/programs/index', [
            'programs' => $programs,
            'flash'    => self::popFlash(),
        ]);
    }

    /** @param array<string,string> $params */
    public function create(array $params): void
    {
        Auth::requireAdmin();

        View::render('admin/programs/form', [
            'mode'    => 'create',
            'program' => null,
            'errors'  => [],
            'old'     => [
                'slug'          => '',
                'title'         => '',
                'presentation'  => '',
                'description'   => '',
                'display_order' => '0',
                'is_published'  => '',
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function store(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $data   = self::extractInput();
        $errors = self::validate($data, currentId: null);

        $coverPath = '';
        if ($errors === []) {
            try {
                $coverPath = Upload::image($_FILES['cover'] ?? null);
            } catch (RuntimeException $e) {
                $errors['cover'] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            View::render('admin/programs/form', [
                'mode'    => 'create',
                'program' => null,
                'errors'  => $errors,
                'old'     => $data,
            ]);
            return;
        }

        $stmt = Connection::get()->prepare(
            'INSERT INTO programs (slug, title, presentation, description, cover_image, display_order, is_published)
             VALUES (:s, :t, :pres, :d, :c, :o, :p)'
        );
        $stmt->execute([
            ':s'    => $data['slug'],
            ':t'    => $data['title'],
            ':pres' => $data['presentation'] !== '' ? $data['presentation'] : null,
            ':d'    => $data['description'] !== '' ? $data['description'] : null,
            ':c'    => $coverPath !== '' ? $coverPath : null,
            ':o'    => (int) $data['display_order'],
            ':p'    => $data['is_published'] === '1' ? 't' : 'f',
        ]);

        self::setFlash('Programa creado.');
        View::redirect('/admin/programas');
    }

    /** @param array<string,string> $params */
    public function edit(array $params): void
    {
        Auth::requireAdmin();
        $id = self::parseId($params['id'] ?? '');
        $program = self::findProgram($id);
        if (!$program) {
            self::redirect404();
            return;
        }

        View::render('admin/programs/form', [
            'mode'    => 'edit',
            'program' => $program,
            'errors'  => [],
            'old'     => [
                'slug'          => $program['slug'],
                'title'         => $program['title'],
                'presentation'  => (string) ($program['presentation'] ?? ''),
                'description'   => (string) $program['description'],
                'display_order' => (string) $program['display_order'],
                'is_published'  => $program['is_published'] ? '1' : '',
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $id = self::parseId($params['id'] ?? '');
        $program = self::findProgram($id);
        if (!$program) {
            self::redirect404();
            return;
        }

        $data   = self::extractInput();
        $errors = self::validate($data, currentId: $id);

        $coverPath = $program['cover_image'];
        if ($errors === []) {
            try {
                $newCover = Upload::image($_FILES['cover'] ?? null);
                if ($newCover !== '') {
                    Upload::deleteImage($program['cover_image']);
                    $coverPath = $newCover;
                }
            } catch (RuntimeException $e) {
                $errors['cover'] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            View::render('admin/programs/form', [
                'mode'    => 'edit',
                'program' => $program,
                'errors'  => $errors,
                'old'     => $data,
            ]);
            return;
        }

        $stmt = Connection::get()->prepare(
            'UPDATE programs
                SET slug = :s, title = :t, presentation = :pres, description = :d,
                    cover_image = :c, display_order = :o, is_published = :p
              WHERE id = :id'
        );
        $stmt->execute([
            ':s'    => $data['slug'],
            ':t'    => $data['title'],
            ':pres' => $data['presentation'] !== '' ? $data['presentation'] : null,
            ':d'    => $data['description'] !== '' ? $data['description'] : null,
            ':c'    => $coverPath !== '' ? $coverPath : null,
            ':o'    => (int) $data['display_order'],
            ':p'    => $data['is_published'] === '1' ? 't' : 'f',
            ':id'   => $id,
        ]);

        self::setFlash('Programa actualizado.');
        View::redirect('/admin/programas');
    }

    /** @param array<string,string> $params */
    public function confirmDestroy(array $params): void
    {
        Auth::requireAdmin();

        $id = self::parseId($params['id'] ?? '');
        $program = self::findProgram($id);
        if (!$program) {
            self::redirect404();
            return;
        }

        $lessonCount = (int) Connection::get()
            ->query('SELECT COUNT(*) FROM lessons WHERE program_id = ' . (int) $id)
            ->fetchColumn();

        View::render('admin/programs/delete', [
            'program'      => $program,
            'lesson_count' => $lessonCount,
        ]);
    }

    /** @param array<string,string> $params */
    public function destroy(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $id = self::parseId($params['id'] ?? '');
        $program = self::findProgram($id);
        if (!$program) {
            self::redirect404();
            return;
        }

        // Borra portadas de lecciones asociadas antes del DELETE cascade.
        $stmt = Connection::get()->prepare('SELECT image_url FROM lessons WHERE program_id = :pid');
        $stmt->execute([':pid' => $id]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $url) {
            Upload::deleteImage(is_string($url) ? $url : null);
        }
        Upload::deleteImage($program['cover_image']);

        $stmt = Connection::get()->prepare('DELETE FROM programs WHERE id = :id');
        $stmt->execute([':id' => $id]);

        self::setFlash('Programa eliminado.');
        View::redirect('/admin/programas');
    }

    // ----------------------------------------------------------------

    /** @return array{slug:string,title:string,presentation:string,description:string,display_order:string,is_published:string} */
    private static function extractInput(): array
    {
        return [
            'slug'          => mb_strtolower(trim((string) ($_POST['slug']  ?? ''))),
            'title'         => trim((string) ($_POST['title'] ?? '')),
            'presentation'  => trim((string) ($_POST['presentation'] ?? '')),
            'description'   => trim((string) ($_POST['description']  ?? '')),
            'display_order' => trim((string) ($_POST['display_order'] ?? '0')),
            'is_published'  => isset($_POST['is_published']) ? '1' : '',
        ];
    }

    /**
     * @param array{slug:string,title:string,presentation:string,description:string,display_order:string,is_published:string} $data
     * @return array<string,string>
     */
    private static function validate(array $data, ?int $currentId): array
    {
        $errors = [];
        if (preg_match('/^[a-z0-9-]{1,80}$/', $data['slug']) !== 1) {
            $errors['slug'] = 'Slug solo puede contener letras minúsculas, números y guiones (máx. 80).';
        }
        if ($data['title'] === '' || mb_strlen($data['title']) > 160) {
            $errors['title'] = 'Título obligatorio (máx. 160 caracteres).';
        }
        if ($data['presentation'] !== '' && mb_strlen($data['presentation']) > 200) {
            $errors['presentation'] = 'Presentación máxima 200 caracteres.';
        }
        if ($data['description'] !== '' && mb_strlen($data['description']) > 2000) {
            $errors['description'] = 'Descripción máxima 2000 caracteres.';
        }
        if (preg_match('/^-?[0-9]{1,5}$/', $data['display_order']) !== 1) {
            $errors['display_order'] = 'El orden debe ser un número entero.';
        }
        if (!isset($errors['slug']) && self::slugExistsExcept($data['slug'], $currentId)) {
            $errors['slug'] = 'Ese slug ya está usado por otro programa.';
        }
        return $errors;
    }

    private static function slugExistsExcept(string $slug, ?int $excludeId): bool
    {
        $sql  = 'SELECT 1 FROM programs WHERE slug = :s';
        $args = [':s' => $slug];
        if ($excludeId !== null) {
            $sql .= ' AND id <> :id';
            $args[':id'] = $excludeId;
        }
        $stmt = Connection::get()->prepare($sql . ' LIMIT 1');
        $stmt->execute($args);
        return (bool) $stmt->fetchColumn();
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
        $stmt = Connection::get()->prepare('SELECT * FROM programs WHERE id = :id');
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
