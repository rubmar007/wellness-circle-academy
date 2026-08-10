<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Database\Connection;
use App\Support\ExperienceKitData;
use App\View;

/**
 * Admin · Notificaciones push: programar, editar y eliminar notificaciones
 * dirigidas a todos los usuarios, por rol, por kit activo o a una persona
 * específica. El envío real (incluida la recurrencia diaria/semanal) lo hace
 * bin/send-scheduled-notifications.php desde un Cron Job de Railway — este
 * controlador solo administra las filas de push_notifications.
 */
final class AdminNotificationsController
{
    private const ROLES = ['admin' => 'Admin', 'member' => 'Promotor (member)', 'cliente' => 'Cliente'];

    /** Zona horaria en la que el admin captura/ve la fecha de envío. Se guarda en UTC en la BD. */
    private const DISPLAY_TZ = 'America/Mexico_City';

    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireAdmin();

        $rows = Connection::get()->query(
            "SELECT n.*, u.name AS target_user_name
               FROM push_notifications n
               LEFT JOIN users u ON u.id = n.audience_user_id
              ORDER BY n.status ASC, n.scheduled_at ASC"
        )->fetchAll();

        View::render('admin/notifications/index', [
            'rows'      => $rows,
            'kitLabels' => ExperienceKitData::kitLabels(),
            'roleLabels' => self::ROLES,
            'flash'     => self::popFlash(),
        ]);
    }

    /** @param array<string,string> $params */
    public function create(array $params): void
    {
        Auth::requireAdmin();

        View::render('admin/notifications/form', [
            'mode'       => 'create',
            'notif'      => null,
            'users'      => self::activeUsers(),
            'kitLabels'  => ExperienceKitData::kitLabels(),
            'roleLabels' => self::ROLES,
            'errors'     => [],
            'old'        => [
                'title' => '', 'body' => '', 'url' => '',
                'audience_type' => 'all', 'audience_role' => '', 'audience_kit_slug' => '', 'audience_user_id' => '',
                'scheduled_at' => '', 'is_recurring' => '', 'recurrence_freq' => 'daily',
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function store(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $data = self::readInput();
        [$errors, $clean] = self::validate($data, isEdit: false);

        if ($errors !== []) {
            View::render('admin/notifications/form', [
                'mode' => 'create', 'notif' => null,
                'users' => self::activeUsers(), 'kitLabels' => ExperienceKitData::kitLabels(), 'roleLabels' => self::ROLES,
                'errors' => $errors, 'old' => $data,
            ]);
            return;
        }

        $admin = Auth::user();
        $stmt = Connection::get()->prepare(
            'INSERT INTO push_notifications
                (title, body, url, audience_type, audience_role, audience_kit_slug, audience_user_id,
                 scheduled_at, is_recurring, recurrence_freq, created_by)
             VALUES
                (:title, :body, :url, :atype, :arole, :akit, :auser,
                 :sched, :recurring, :freq, :created_by)'
        );
        $stmt->execute([
            ':title'      => $clean['title'],
            ':body'       => $clean['body'],
            ':url'        => $clean['url'],
            ':atype'      => $clean['audience_type'],
            ':arole'      => $clean['audience_role'],
            ':akit'       => $clean['audience_kit_slug'],
            ':auser'      => $clean['audience_user_id'],
            ':sched'      => $clean['scheduled_at']->format('Y-m-d H:i:s'),
            ':recurring'  => $clean['is_recurring'] ? 't' : 'f',
            ':freq'       => $clean['recurrence_freq'],
            ':created_by' => (int) $admin['id'],
        ]);

        self::setFlash('Notificación programada.');
        View::redirect('/admin/notificaciones');
    }

    /** @param array<string,string> $params */
    public function edit(array $params): void
    {
        Auth::requireAdmin();

        $notif = self::findNotification($params['id'] ?? '');
        if ($notif === null) {
            self::redirect404();
            return;
        }

        $sched = (new \DateTimeImmutable((string) $notif['scheduled_at']))
            ->setTimezone(new \DateTimeZone(self::DISPLAY_TZ));

        View::render('admin/notifications/form', [
            'mode'       => 'edit',
            'notif'      => $notif,
            'users'      => self::activeUsers(),
            'kitLabels'  => ExperienceKitData::kitLabels(),
            'roleLabels' => self::ROLES,
            'errors'     => [],
            'old'        => [
                'title' => (string) $notif['title'],
                'body'  => (string) $notif['body'],
                'url'   => (string) ($notif['url'] ?? ''),
                'audience_type'     => (string) $notif['audience_type'],
                'audience_role'     => (string) ($notif['audience_role'] ?? ''),
                'audience_kit_slug' => (string) ($notif['audience_kit_slug'] ?? ''),
                'audience_user_id'  => $notif['audience_user_id'] !== null ? (string) $notif['audience_user_id'] : '',
                'scheduled_at'      => $sched->format('Y-m-d\TH:i'),
                'is_recurring'      => $notif['is_recurring'] ? '1' : '',
                'recurrence_freq'   => (string) ($notif['recurrence_freq'] ?? 'daily'),
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $notif = self::findNotification($params['id'] ?? '');
        if ($notif === null) {
            self::redirect404();
            return;
        }

        $data = self::readInput();
        [$errors, $clean] = self::validate($data, isEdit: true);

        if ($errors !== []) {
            View::render('admin/notifications/form', [
                'mode' => 'edit', 'notif' => $notif,
                'users' => self::activeUsers(), 'kitLabels' => ExperienceKitData::kitLabels(), 'roleLabels' => self::ROLES,
                'errors' => $errors, 'old' => $data,
            ]);
            return;
        }

        $stmt = Connection::get()->prepare(
            'UPDATE push_notifications SET
                title = :title, body = :body, url = :url,
                audience_type = :atype, audience_role = :arole, audience_kit_slug = :akit, audience_user_id = :auser,
                scheduled_at = :sched, is_recurring = :recurring, recurrence_freq = :freq,
                status = :status, updated_at = now()
             WHERE id = :id'
        );
        $stmt->execute([
            ':title'     => $clean['title'],
            ':body'      => $clean['body'],
            ':url'       => $clean['url'],
            ':atype'     => $clean['audience_type'],
            ':arole'     => $clean['audience_role'],
            ':akit'      => $clean['audience_kit_slug'],
            ':auser'     => $clean['audience_user_id'],
            ':sched'     => $clean['scheduled_at']->format('Y-m-d H:i:s'),
            ':recurring' => $clean['is_recurring'] ? 't' : 'f',
            ':freq'      => $clean['recurrence_freq'],
            // Reeditar una notificación ya enviada/cancelada la vuelve a poner en cola.
            ':status'    => 'pending',
            ':id'        => (int) $notif['id'],
        ]);

        self::setFlash('Notificación actualizada.');
        View::redirect('/admin/notificaciones');
    }

    /** @param array<string,string> $params */
    public function confirmDestroy(array $params): void
    {
        Auth::requireAdmin();

        $notif = self::findNotification($params['id'] ?? '');
        if ($notif === null) {
            self::redirect404();
            return;
        }

        View::render('admin/notifications/delete', ['notif' => $notif]);
    }

    /** @param array<string,string> $params */
    public function destroy(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $notif = self::findNotification($params['id'] ?? '');
        if ($notif === null) {
            self::redirect404();
            return;
        }

        $stmt = Connection::get()->prepare('DELETE FROM push_notifications WHERE id = :id');
        $stmt->execute([':id' => (int) $notif['id']]);

        self::setFlash('Notificación eliminada.');
        View::redirect('/admin/notificaciones');
    }

    // ----------------------------------------------------------------

    /** @return array<string,string> */
    private static function readInput(): array
    {
        return [
            'title'             => trim((string) ($_POST['title'] ?? '')),
            'body'              => trim((string) ($_POST['body'] ?? '')),
            'url'               => trim((string) ($_POST['url'] ?? '')),
            'audience_type'     => trim((string) ($_POST['audience_type'] ?? '')),
            'audience_role'     => trim((string) ($_POST['audience_role'] ?? '')),
            'audience_kit_slug' => trim((string) ($_POST['audience_kit_slug'] ?? '')),
            'audience_user_id'  => trim((string) ($_POST['audience_user_id'] ?? '')),
            'scheduled_at'      => trim((string) ($_POST['scheduled_at'] ?? '')),
            'is_recurring'      => trim((string) ($_POST['is_recurring'] ?? '')),
            'recurrence_freq'   => trim((string) ($_POST['recurrence_freq'] ?? '')),
        ];
    }

    /**
     * @param array<string,string> $data
     * @return array{0: array<string,string>, 1: array<string,mixed>}
     */
    private static function validate(array $data, bool $isEdit): array
    {
        $errors = [];
        $clean  = [];

        $clean['title'] = mb_substr($data['title'], 0, 150);
        if ($clean['title'] === '') {
            $errors['title'] = 'El título es obligatorio.';
        }

        $clean['body'] = $data['body'];
        if ($clean['body'] === '') {
            $errors['body'] = 'El mensaje es obligatorio.';
        }

        $clean['url'] = $data['url'] !== '' ? mb_substr($data['url'], 0, 255) : null;
        if ($clean['url'] !== null && filter_var($clean['url'], FILTER_VALIDATE_URL) === false && !str_starts_with($clean['url'], '/')) {
            $errors['url'] = 'La URL debe ser absoluta (https://...) o una ruta interna que empiece con /.';
        }

        $clean['audience_type'] = $data['audience_type'];
        if (!in_array($clean['audience_type'], ['all', 'role', 'kit', 'user'], true)) {
            $errors['audience_type'] = 'Selecciona a quién se dirige.';
        }

        $clean['audience_role'] = null;
        if ($clean['audience_type'] === 'role') {
            if (!array_key_exists($data['audience_role'], self::ROLES)) {
                $errors['audience_role'] = 'Selecciona un rol válido.';
            } else {
                $clean['audience_role'] = $data['audience_role'];
            }
        }

        $clean['audience_kit_slug'] = null;
        if ($clean['audience_type'] === 'kit') {
            if (!isset(ExperienceKitData::kitLabels()[$data['audience_kit_slug']])) {
                $errors['audience_kit_slug'] = 'Selecciona un kit válido.';
            } else {
                $clean['audience_kit_slug'] = $data['audience_kit_slug'];
            }
        }

        $clean['audience_user_id'] = null;
        if ($clean['audience_type'] === 'user') {
            $id = filter_var($data['audience_user_id'], FILTER_VALIDATE_INT);
            if ($id === false || $id <= 0) {
                $errors['audience_user_id'] = 'Selecciona una persona.';
            } else {
                $clean['audience_user_id'] = $id;
            }
        }

        $sched = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $data['scheduled_at'], new \DateTimeZone(self::DISPLAY_TZ));
        if ($sched === false) {
            $errors['scheduled_at'] = 'Fecha y hora inválidas.';
        } elseif (!$isEdit && $sched < new \DateTimeImmutable('now')) {
            $errors['scheduled_at'] = 'La fecha/hora debe ser en el futuro.';
        }
        // Se captura en hora CDMX pero se guarda en UTC (columna TIMESTAMPTZ).
        $clean['scheduled_at'] = ($sched !== false ? $sched : new \DateTimeImmutable('now'))
            ->setTimezone(new \DateTimeZone('UTC'));

        $clean['is_recurring'] = $data['is_recurring'] === '1';
        $clean['recurrence_freq'] = null;
        if ($clean['is_recurring']) {
            if (!in_array($data['recurrence_freq'], ['daily', 'weekly'], true)) {
                $errors['recurrence_freq'] = 'Selecciona la frecuencia.';
            } else {
                $clean['recurrence_freq'] = $data['recurrence_freq'];
            }
        }

        return [$errors, $clean];
    }

    /** @return array<int, array<string,mixed>> */
    private static function activeUsers(): array
    {
        return Connection::get()->query(
            "SELECT id, name, email, role FROM users WHERE is_active = TRUE ORDER BY name ASC"
        )->fetchAll();
    }

    /** @return array<string,mixed>|null */
    private static function findNotification(string $rawId): ?array
    {
        $id = (preg_match('/^[1-9][0-9]{0,9}$/', $rawId) === 1) ? (int) $rawId : 0;
        if ($id <= 0) {
            return null;
        }

        $stmt = Connection::get()->prepare('SELECT * FROM push_notifications WHERE id = :id');
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
