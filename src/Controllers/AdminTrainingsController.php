<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Database\Connection;
use App\Embed;
use App\View;

final class AdminTrainingsController
{
    /** @var array<string,string> */
    public const CATEGORIES = [
        'plan_compensacion' => 'Plan de Compensación',
        'clientes'          => 'Clientes',
        'autoenvio'         => 'Autoenvío',
        'oficina_virtual'   => 'Oficina virtual',
        'doctores'          => 'Doctores',
    ];

    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireAdmin();

        $trainings = Connection::get()->query(
            'SELECT id, category, title, video_url, display_order, is_published
               FROM trainings
              ORDER BY category ASC, display_order ASC, id ASC'
        )->fetchAll();

        View::render('admin/trainings/index', [
            'trainings'  => $trainings,
            'categories' => self::CATEGORIES,
            'flash'      => self::popFlash(),
        ]);
    }

    /** @param array<string,string> $params */
    public function create(array $params): void
    {
        Auth::requireAdmin();

        View::render('admin/trainings/form', [
            'mode'       => 'create',
            'training'   => null,
            'categories' => self::CATEGORIES,
            'errors'     => [],
            'old'        => [
                'category'      => '',
                'title'         => '',
                'video_url'     => '',
                'display_order' => '0',
                'is_published'  => '1',
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
            View::render('admin/trainings/form', [
                'mode'       => 'create',
                'training'   => null,
                'categories' => self::CATEGORIES,
                'errors'     => $errors,
                'old'        => $data,
            ]);
            return;
        }

        $stmt = Connection::get()->prepare(
            'INSERT INTO trainings (category, title, video_url, display_order, is_published)
             VALUES (:cat, :t, :v, :o, :p)'
        );
        $stmt->execute([
            ':cat' => $data['category'],
            ':t'   => $data['title'],
            ':v'   => $data['video_url'],
            ':o'   => (int) $data['display_order'],
            ':p'   => $data['is_published'] === '1' ? 't' : 'f',
        ]);

        self::setFlash('Video creado.');
        View::redirect('/admin/entrenamiento');
    }

    /** @param array<string,string> $params */
    public function edit(array $params): void
    {
        Auth::requireAdmin();

        $id = self::parseId($params['id'] ?? '');
        $training = self::findTraining($id);
        if (!$training) {
            self::redirect404();
            return;
        }

        View::render('admin/trainings/form', [
            'mode'       => 'edit',
            'training'   => $training,
            'categories' => self::CATEGORIES,
            'errors'     => [],
            'old'        => [
                'category'      => (string) $training['category'],
                'title'         => (string) $training['title'],
                'video_url'     => (string) $training['video_url'],
                'display_order' => (string) $training['display_order'],
                'is_published'  => $training['is_published'] ? '1' : '',
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $id = self::parseId($params['id'] ?? '');
        $training = self::findTraining($id);
        if (!$training) {
            self::redirect404();
            return;
        }

        $data   = self::extractInput();
        $errors = self::validate($data);

        if ($errors !== []) {
            View::render('admin/trainings/form', [
                'mode'       => 'edit',
                'training'   => $training,
                'categories' => self::CATEGORIES,
                'errors'     => $errors,
                'old'        => $data,
            ]);
            return;
        }

        $stmt = Connection::get()->prepare(
            'UPDATE trainings
                SET category = :cat, title = :t, video_url = :v,
                    display_order = :o, is_published = :p
              WHERE id = :id'
        );
        $stmt->execute([
            ':cat' => $data['category'],
            ':t'   => $data['title'],
            ':v'   => $data['video_url'],
            ':o'   => (int) $data['display_order'],
            ':p'   => $data['is_published'] === '1' ? 't' : 'f',
            ':id'  => $id,
        ]);

        self::setFlash('Video actualizado.');
        View::redirect('/admin/entrenamiento');
    }

    /** @param array<string,string> $params */
    public function confirmDestroy(array $params): void
    {
        Auth::requireAdmin();

        $id = self::parseId($params['id'] ?? '');
        $training = self::findTraining($id);
        if (!$training) {
            self::redirect404();
            return;
        }

        View::render('admin/trainings/delete', [
            'training'   => $training,
            'categories' => self::CATEGORIES,
        ]);
    }

    /** @param array<string,string> $params */
    public function destroy(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $id = self::parseId($params['id'] ?? '');
        $training = self::findTraining($id);
        if (!$training) {
            self::redirect404();
            return;
        }

        $stmt = Connection::get()->prepare('DELETE FROM trainings WHERE id = :id');
        $stmt->execute([':id' => $id]);

        self::setFlash('Video eliminado.');
        View::redirect('/admin/entrenamiento');
    }

    // ----------------------------------------------------------------

    /** @return array{category:string,title:string,video_url:string,display_order:string,is_published:string} */
    private static function extractInput(): array
    {
        return [
            'category'      => trim((string) ($_POST['category'] ?? '')),
            'title'         => trim((string) ($_POST['title'] ?? '')),
            'video_url'     => trim((string) ($_POST['video_url'] ?? '')),
            'display_order' => trim((string) ($_POST['display_order'] ?? '0')),
            'is_published'  => isset($_POST['is_published']) ? '1' : '',
        ];
    }

    /**
     * @param array{category:string,title:string,video_url:string,display_order:string,is_published:string} $data
     * @return array<string,string>
     */
    private static function validate(array $data): array
    {
        $errors = [];
        if (!isset(self::CATEGORIES[$data['category']])) {
            $errors['category'] = 'Selecciona un tema válido.';
        }
        if ($data['title'] === '' || mb_strlen($data['title']) > 200) {
            $errors['title'] = 'Título obligatorio (máx. 200 caracteres).';
        }
        if ($data['video_url'] === '' || mb_strlen($data['video_url']) > 500) {
            $errors['video_url'] = 'URL obligatoria (máx. 500 caracteres).';
        } elseif (Embed::parseVideo($data['video_url']) === null) {
            $errors['video_url'] = 'URL inválida. Solo YouTube y Vimeo.';
        }
        if (preg_match('/^-?[0-9]{1,5}$/', $data['display_order']) !== 1) {
            $errors['display_order'] = 'El orden debe ser un número entero.';
        }
        return $errors;
    }

    private static function parseId(string $raw): int
    {
        return (preg_match('/^[1-9][0-9]{0,9}$/', $raw) === 1) ? (int) $raw : 0;
    }

    /** @return array<string,mixed>|null */
    private static function findTraining(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = Connection::get()->prepare('SELECT * FROM trainings WHERE id = :id');
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
