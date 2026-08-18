<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Database\Connection;
use App\Upload;
use App\View;
use DateTime;
use RuntimeException;

final class AdminEventsController
{
    /** Whitelist de tipos de evento (clave => etiqueta). */
    public const TYPES = [
        'taller'         => 'Taller',
        'entrenamiento'  => 'Entrenamiento',
        'oportunidad'    => 'Presentación de oportunidad',
    ];

    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireAdmin();

        $events = Connection::get()->query(
            'SELECT id, title, event_type, starts_at, join_url, is_published
               FROM events
              ORDER BY starts_at DESC'
        )->fetchAll();

        View::render('admin/events/index', [
            'events' => $events,
            'flash'  => self::popFlash(),
        ]);
    }

    /** @param array<string,string> $params */
    public function create(array $params): void
    {
        Auth::requireAdmin();

        // Al crear desde un día del calendario (?fecha=YYYY-MM-DD) se
        // precarga esa fecha con una hora por defecto, para no obligar a
        // capturarla desde cero.
        $fecha    = trim((string) ($_GET['fecha'] ?? ''));
        $startsAt = '';
        if (\DateTime::createFromFormat('Y-m-d', $fecha) !== false) {
            $startsAt = $fecha . 'T18:00';
        }

        View::render('admin/events/form', [
            'mode'   => 'create',
            'event'  => null,
            'errors' => [],
            'old'    => [
                'title'        => '',
                'event_type'   => '',
                'starts_at'    => $startsAt,
                'join_url'     => '',
                'description'  => '',
                'is_published' => '1',
            ],
            'duplicateImageUrl' => null,
        ]);
    }

    /** @param array<string,string> $params */
    public function duplicate(array $params): void
    {
        Auth::requireAdmin();

        $id     = self::parseId($params['id'] ?? '');
        $source = self::findEvent($id);
        if (!$source) {
            self::redirect404();
            return;
        }

        $imageUrl = '';
        if (!empty($source['image_url'])) {
            $imageUrl = Upload::copyImage((string) $source['image_url']);
        }

        View::render('admin/events/form', [
            'mode'   => 'create',
            'event'  => null,
            'errors' => [],
            'old'    => [
                'title'        => (string) $source['title'] . ' (copia)',
                'event_type'   => (string) $source['event_type'],
                'starts_at'    => (new DateTime((string) $source['starts_at']))->format('Y-m-d\TH:i'),
                'join_url'     => (string) ($source['join_url'] ?? ''),
                'description'  => (string) ($source['description'] ?? ''),
                'is_published' => $source['is_published'] ? '1' : '',
            ],
            'duplicateImageUrl' => $imageUrl !== '' ? $imageUrl : null,
        ]);
    }

    /** @param array<string,string> $params */
    public function calendar(array $params): void
    {
        Auth::requireAdmin();

        $monthParam = trim((string) ($_GET['mes'] ?? ''));
        $monthStart = DateTime::createFromFormat('Y-m-d', $monthParam . '-01');
        if ($monthStart === false) {
            $monthStart = new DateTime('first day of this month');
        }
        $monthStart->setTime(0, 0);

        $gridStart = clone $monthStart;
        $gridStart->modify('-' . $gridStart->format('w') . ' days');
        $gridEnd = (clone $gridStart)->modify('+42 days');

        $stmt = Connection::get()->prepare(
            'SELECT id, title, event_type, starts_at, is_published
               FROM events
              WHERE starts_at >= :start AND starts_at < :end
              ORDER BY starts_at ASC'
        );
        $stmt->execute([
            ':start' => $gridStart->format('Y-m-d H:i:s'),
            ':end'   => $gridEnd->format('Y-m-d H:i:s'),
        ]);
        $events = $stmt->fetchAll();

        $byDay = [];
        foreach ($events as $ev) {
            $key = (new DateTime((string) $ev['starts_at']))->format('Y-m-d');
            $byDay[$key][] = $ev;
        }

        $cells  = [];
        $cursor = clone $gridStart;
        for ($i = 0; $i < 42; $i++) {
            $key      = $cursor->format('Y-m-d');
            $cells[]  = [
                'date'    => clone $cursor,
                'inMonth' => $cursor->format('Y-m') === $monthStart->format('Y-m'),
                'events'  => $byDay[$key] ?? [],
            ];
            $cursor->modify('+1 day');
        }

        View::render('admin/events/calendar', [
            'monthStart' => $monthStart,
            'cells'      => $cells,
            'prevMonth'  => (clone $monthStart)->modify('-1 month')->format('Y-m'),
            'nextMonth'  => (clone $monthStart)->modify('+1 month')->format('Y-m'),
        ]);
    }

    /** @param array<string,string> $params */
    public function store(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $data        = self::extractInput();
        $hasFile     = self::hasFileUpload();
        $imageUrl    = '';
        $uploadError = null;

        if ($hasFile) {
            try {
                $imageUrl = Upload::image($_FILES['image'] ?? null);
            } catch (RuntimeException $e) {
                $uploadError = $e->getMessage();
            }
            if ($data['duplicate_image_url'] !== '') {
                // Se subió un archivo nuevo en vez de conservar la imagen
                // duplicada: la copia física que ya no se usará se descarta.
                Upload::deleteImage($data['duplicate_image_url']);
                $data['duplicate_image_url'] = '';
            }
        } elseif ($data['duplicate_image_url'] !== '' && self::isValidUploadedImage($data['duplicate_image_url'])) {
            // "Duplicar evento": si no se subió un archivo nuevo, se conserva
            // la copia que ya se hizo físicamente en duplicate() (ver form.php).
            $imageUrl = $data['duplicate_image_url'];
        }

        $errors = self::validate($data, $uploadError);

        if ($errors !== []) {
            if ($hasFile && $imageUrl !== '') {
                Upload::deleteImage($imageUrl);
            }
            View::render('admin/events/form', [
                'mode'   => 'create',
                'event'  => null,
                'errors' => $errors,
                'old'    => $data,
                'duplicateImageUrl' => $data['duplicate_image_url'] !== '' ? $data['duplicate_image_url'] : null,
            ]);
            return;
        }

        $stmt = Connection::get()->prepare(
            'INSERT INTO events (title, event_type, starts_at, join_url, description, image_url, is_published)
             VALUES (:t, :ty, :s, :j, :d, :im, :p)'
        );
        $stmt->execute([
            ':t'  => $data['title'],
            ':ty' => $data['event_type'],
            ':s'  => $data['starts_at'],
            ':j'  => $data['join_url'] !== '' ? $data['join_url'] : null,
            ':d'  => $data['description'] !== '' ? $data['description'] : null,
            ':im' => $imageUrl !== '' ? $imageUrl : null,
            ':p'  => $data['is_published'] === '1' ? 't' : 'f',
        ]);

        self::setFlash('Evento creado.');
        View::redirect('/admin/eventos');
    }

    /** @param array<string,string> $params */
    public function edit(array $params): void
    {
        Auth::requireAdmin();

        $id    = self::parseId($params['id'] ?? '');
        $event = self::findEvent($id);
        if (!$event) {
            self::redirect404();
            return;
        }

        View::render('admin/events/form', [
            'mode'   => 'edit',
            'event'  => $event,
            'errors' => [],
            'old'    => [
                'title'        => (string) $event['title'],
                'event_type'   => (string) $event['event_type'],
                'starts_at'    => (new DateTime((string) $event['starts_at']))->format('Y-m-d\TH:i'),
                'join_url'     => (string) ($event['join_url'] ?? ''),
                'description'  => (string) ($event['description'] ?? ''),
                'is_published' => $event['is_published'] ? '1' : '',
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $id    = self::parseId($params['id'] ?? '');
        $event = self::findEvent($id);
        if (!$event) {
            self::redirect404();
            return;
        }

        $data          = self::extractInput();
        $hasFile       = self::hasFileUpload();
        $existingImage = (string) ($event['image_url'] ?? '');
        $imageUrl      = $existingImage;
        $newUpload     = '';
        $uploadError   = null;

        if ($hasFile) {
            try {
                $newUpload = Upload::image($_FILES['image'] ?? null);
                if ($newUpload !== '') {
                    $imageUrl = $newUpload;
                }
            } catch (RuntimeException $e) {
                $uploadError = $e->getMessage();
            }
        }

        $errors = self::validate($data, $uploadError);

        if ($errors !== []) {
            if ($newUpload !== '') {
                Upload::deleteImage($newUpload);
            }
            View::render('admin/events/form', [
                'mode'   => 'edit',
                'event'  => $event,
                'errors' => $errors,
                'old'    => $data,
            ]);
            return;
        }

        if ($newUpload !== '' && $existingImage !== '' && $existingImage !== $newUpload) {
            Upload::deleteImage($existingImage);
        }

        $stmt = Connection::get()->prepare(
            'UPDATE events
                SET title = :t, event_type = :ty, starts_at = :s,
                    join_url = :j, description = :d, image_url = :im, is_published = :p
              WHERE id = :id'
        );
        $stmt->execute([
            ':t'  => $data['title'],
            ':ty' => $data['event_type'],
            ':s'  => $data['starts_at'],
            ':j'  => $data['join_url'] !== '' ? $data['join_url'] : null,
            ':d'  => $data['description'] !== '' ? $data['description'] : null,
            ':im' => $imageUrl !== '' ? $imageUrl : null,
            ':p'  => $data['is_published'] === '1' ? 't' : 'f',
            ':id' => $id,
        ]);

        self::setFlash('Evento actualizado.');
        View::redirect('/admin/eventos');
    }

    /** @param array<string,string> $params */
    public function confirmDestroy(array $params): void
    {
        Auth::requireAdmin();

        $id    = self::parseId($params['id'] ?? '');
        $event = self::findEvent($id);
        if (!$event) {
            self::redirect404();
            return;
        }

        View::render('admin/events/delete', [
            'event' => $event,
        ]);
    }

    /** @param array<string,string> $params */
    public function destroy(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $id    = self::parseId($params['id'] ?? '');
        $event = self::findEvent($id);
        if (!$event) {
            self::redirect404();
            return;
        }

        if (!empty($event['image_url'])) {
            Upload::deleteImage((string) $event['image_url']);
        }

        $stmt = Connection::get()->prepare('DELETE FROM events WHERE id = :id');
        $stmt->execute([':id' => $id]);

        self::setFlash('Evento eliminado.');
        View::redirect('/admin/eventos');
    }

    // ----------------------------------------------------------------

    private static function hasFileUpload(): bool
    {
        return isset($_FILES['image']) && is_array($_FILES['image'])
            && (int) ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /** @return array{title:string,event_type:string,starts_at:string,join_url:string,description:string,is_published:string,duplicate_image_url:string} */
    private static function extractInput(): array
    {
        return [
            'title'        => trim((string) ($_POST['title'] ?? '')),
            'event_type'   => trim((string) ($_POST['event_type'] ?? '')),
            'starts_at'    => trim((string) ($_POST['starts_at'] ?? '')),
            'join_url'     => trim((string) ($_POST['join_url'] ?? '')),
            'description'  => trim((string) ($_POST['description'] ?? '')),
            'is_published' => isset($_POST['is_published']) ? '1' : '',
            'duplicate_image_url' => trim((string) ($_POST['duplicate_image_url'] ?? '')),
        ];
    }

    /** Valida que la ruta venga realmente de una copia hecha por duplicate() y que el archivo exista. */
    private static function isValidUploadedImage(string $path): bool
    {
        if (!str_starts_with($path, '/assets/uploads/')) {
            return false;
        }
        $name = basename($path);
        if (preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name) !== 1) {
            return false;
        }
        return is_file(dirname(__DIR__, 2) . '/public/assets/uploads/' . $name);
    }

    /**
     * @param array{title:string,event_type:string,starts_at:string,join_url:string,description:string,is_published:string,duplicate_image_url:string} $data
     * @return array<string,string>
     */
    private static function validate(array $data, ?string $uploadError): array
    {
        $errors = [];

        if ($data['title'] === '' || mb_strlen($data['title']) > 200) {
            $errors['title'] = 'Título obligatorio (máx. 200 caracteres).';
        }

        if (!array_key_exists($data['event_type'], self::TYPES)) {
            $errors['event_type'] = 'Selecciona un tipo de evento válido.';
        }

        if (DateTime::createFromFormat('Y-m-d\TH:i', $data['starts_at']) === false) {
            $errors['starts_at'] = 'Fecha y hora inválidas.';
        }

        if ($data['join_url'] !== '') {
            if (mb_strlen($data['join_url']) > 500 || parse_url($data['join_url'], PHP_URL_SCHEME) !== 'https') {
                $errors['join_url'] = 'El enlace debe empezar con https:// (máx. 500 caracteres).';
            }
        }

        if ($data['description'] !== '' && mb_strlen($data['description']) > 4000) {
            $errors['description'] = 'Descripción máxima 4000 caracteres.';
        }

        if ($uploadError !== null) {
            $errors['image'] = $uploadError;
        }

        return $errors;
    }

    private static function parseId(string $raw): int
    {
        return (preg_match('/^[1-9][0-9]{0,9}$/', $raw) === 1) ? (int) $raw : 0;
    }

    /** @return array<string,mixed>|null */
    private static function findEvent(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = Connection::get()->prepare('SELECT * FROM events WHERE id = :id');
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
