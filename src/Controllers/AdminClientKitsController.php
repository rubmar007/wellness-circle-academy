<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Database\Connection;
use App\Support\ExperienceKitData;
use App\View;

/**
 * Admin · WCA Experience Kit: asignar un kit a un cliente o promotor
 * (roles cliente/member) y ver el panel de seguimiento (parche hoy, horas
 * desde la última toma de agua, si hizo ejercicio y si contestó el diario)
 * — docs/PLAN_A_1act secciones 2 y 4.5.
 */
final class AdminClientKitsController
{
    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireAdmin();

        $kits = Connection::get()->query(
            "SELECT ck.id, ck.kit_slug, ck.started_at, ck.weight_kg,
                    u.id AS user_id, u.name, u.email, u.role
               FROM client_kits ck
               JOIN users u ON u.id = ck.user_id
              WHERE ck.is_active = TRUE
              ORDER BY u.name ASC"
        )->fetchAll();

        $today = (new \DateTimeImmutable('today'));
        $rows  = [];
        foreach ($kits as $kit) {
            $started   = new \DateTimeImmutable((string) $kit['started_at']);
            $dayNumber = max(1, min(7, 1 + (int) $today->diff($started)->format('%r%a')));

            $stmt = Connection::get()->prepare(
                'SELECT * FROM client_kit_logs WHERE client_kit_id = :k AND day_number = :d LIMIT 1'
            );
            $stmt->execute([':k' => (int) $kit['id'], ':d' => $dayNumber]);
            $log = $stmt->fetch();

            $hoursSinceWater = null;
            if ($log && $log['water_last_at']) {
                $last = new \DateTimeImmutable((string) $log['water_last_at']);
                $hoursSinceWater = (int) floor((time() - $last->getTimestamp()) / 3600);
            }

            $rows[] = [
                'kit'              => $kit,
                'dayNumber'        => $dayNumber,
                'patchApplied'     => $log ? (bool) $log['patch_applied'] : false,
                'hoursSinceWater'  => $hoursSinceWater,
                'needsFollowUp'    => $hoursSinceWater === null || $hoursSinceWater >= 4,
                'exerciseDone'     => $log ? (bool) $log['exercise_done'] : false,
                'diaryAnswered'    => $log && $log['diary'] !== null,
            ];
        }

        View::render('admin/experience-kit/index', [
            'rows'      => $rows,
            'kitLabels' => ExperienceKitData::kitLabels(),
            'flash'     => self::popFlash(),
        ]);
    }

    /** @param array<string,string> $params */
    public function create(array $params): void
    {
        Auth::requireAdmin();

        $clientes = Connection::get()->query(
            "SELECT u.id, u.name, u.email, u.role
               FROM users u
              WHERE u.role IN ('cliente', 'member') AND u.is_active = TRUE
                AND u.id NOT IN (SELECT user_id FROM client_kits WHERE is_active = TRUE)
              ORDER BY u.name ASC"
        )->fetchAll();

        View::render('admin/experience-kit/form', [
            'mode'      => 'create',
            'kit'       => null,
            'clientes'  => $clientes,
            'kitLabels' => ExperienceKitData::kitLabels(),
            'errors'    => [],
            'old'       => ['user_id' => '', 'kit_slug' => '', 'weight_kg' => '', 'started_at' => date('Y-m-d')],
        ]);
    }

    /** @param array<string,string> $params */
    public function store(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $data = [
            'user_id'    => trim((string) ($_POST['user_id'] ?? '')),
            'kit_slug'   => trim((string) ($_POST['kit_slug'] ?? '')),
            'weight_kg'  => trim((string) ($_POST['weight_kg'] ?? '')),
            'started_at' => trim((string) ($_POST['started_at'] ?? '')),
        ];

        $errors = [];
        $userId = filter_var($data['user_id'], FILTER_VALIDATE_INT);
        if ($userId === false || $userId <= 0) {
            $errors['user_id'] = 'Selecciona un cliente o promotor.';
        }
        if (!isset(ExperienceKitData::kitLabels()[$data['kit_slug']])) {
            $errors['kit_slug'] = 'Selecciona un kit válido.';
        }
        $weightKg = null;
        if ($data['weight_kg'] !== '') {
            $weightKg = filter_var($data['weight_kg'], FILTER_VALIDATE_FLOAT);
            if ($weightKg === false || $weightKg <= 0 || $weightKg > 400) {
                $errors['weight_kg'] = 'Peso inválido.';
            }
        }
        $startedAt = \DateTimeImmutable::createFromFormat('Y-m-d', $data['started_at']);
        if (!$startedAt) {
            $errors['started_at'] = 'Fecha inválida.';
        }

        if ($errors === [] && $userId !== false) {
            $stmt = Connection::get()->prepare("SELECT role FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $role = $stmt->fetchColumn();
            if (!in_array($role, ['cliente', 'member'], true)) {
                $errors['user_id'] = 'El usuario seleccionado debe tener rol cliente o member (promotor).';
            } else {
                $stmt = Connection::get()->prepare(
                    'SELECT 1 FROM client_kits WHERE user_id = :id AND is_active = TRUE'
                );
                $stmt->execute([':id' => $userId]);
                if ($stmt->fetchColumn()) {
                    $errors['user_id'] = 'Este usuario ya tiene un kit activo. Finalízalo antes de asignar uno nuevo.';
                }
            }
        }

        if ($errors !== []) {
            $clientes = Connection::get()->query(
                "SELECT u.id, u.name, u.email, u.role
                   FROM users u
                  WHERE u.role IN ('cliente', 'member') AND u.is_active = TRUE
                    AND u.id NOT IN (SELECT user_id FROM client_kits WHERE is_active = TRUE)
                  ORDER BY u.name ASC"
            )->fetchAll();

            View::render('admin/experience-kit/form', [
                'mode'      => 'create',
                'kit'       => null,
                'clientes'  => $clientes,
                'kitLabels' => ExperienceKitData::kitLabels(),
                'errors'    => $errors,
                'old'       => $data,
            ]);
            return;
        }

        $stmt = Connection::get()->prepare(
            'INSERT INTO client_kits (user_id, kit_slug, weight_kg, started_at)
             VALUES (:u, :k, :w, :s)'
        );
        $stmt->execute([
            ':u' => $userId,
            ':k' => $data['kit_slug'],
            ':w' => $weightKg !== null ? $weightKg : null,
            ':s' => $data['started_at'],
        ]);

        self::setFlash('Kit asignado.');
        View::redirect('/admin/experience-kit');
    }

    /** @param array<string,string> $params */
    public function edit(array $params): void
    {
        Auth::requireAdmin();

        $kit = self::findKit($params['id'] ?? '');
        if ($kit === null) {
            self::redirect404();
            return;
        }

        View::render('admin/experience-kit/form', [
            'mode'      => 'edit',
            'kit'       => $kit,
            'clientes'  => [],
            'kitLabels' => ExperienceKitData::kitLabels(),
            'errors'    => [],
            'old'       => [
                'kit_slug'   => (string) $kit['kit_slug'],
                'weight_kg'  => $kit['weight_kg'] !== null ? (string) $kit['weight_kg'] : '',
                'started_at' => (string) $kit['started_at'],
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $kit = self::findKit($params['id'] ?? '');
        if ($kit === null) {
            self::redirect404();
            return;
        }

        $data = [
            'kit_slug'   => trim((string) ($_POST['kit_slug'] ?? '')),
            'weight_kg'  => trim((string) ($_POST['weight_kg'] ?? '')),
            'started_at' => trim((string) ($_POST['started_at'] ?? '')),
        ];

        $errors = [];
        if (!isset(ExperienceKitData::kitLabels()[$data['kit_slug']])) {
            $errors['kit_slug'] = 'Selecciona un kit válido.';
        }
        $weightKg = null;
        if ($data['weight_kg'] !== '') {
            $weightKg = filter_var($data['weight_kg'], FILTER_VALIDATE_FLOAT);
            if ($weightKg === false || $weightKg <= 0 || $weightKg > 400) {
                $errors['weight_kg'] = 'Peso inválido.';
            }
        }
        $startedAt = \DateTimeImmutable::createFromFormat('Y-m-d', $data['started_at']);
        if (!$startedAt) {
            $errors['started_at'] = 'Fecha inválida.';
        }

        if ($errors !== []) {
            View::render('admin/experience-kit/form', [
                'mode'      => 'edit',
                'kit'       => $kit,
                'clientes'  => [],
                'kitLabels' => ExperienceKitData::kitLabels(),
                'errors'    => $errors,
                'old'       => $data,
            ]);
            return;
        }

        $stmt = Connection::get()->prepare(
            'UPDATE client_kits SET kit_slug = :k, weight_kg = :w, started_at = :s WHERE id = :id'
        );
        $stmt->execute([
            ':k'  => $data['kit_slug'],
            ':w'  => $weightKg !== null ? $weightKg : null,
            ':s'  => $data['started_at'],
            ':id' => (int) $kit['id'],
        ]);

        self::setFlash('Kit actualizado.');
        View::redirect('/admin/experience-kit');
    }

    /** @param array<string,string> $params */
    public function confirmDestroy(array $params): void
    {
        Auth::requireAdmin();

        $kit = self::findKit($params['id'] ?? '');
        if ($kit === null) {
            self::redirect404();
            return;
        }

        View::render('admin/experience-kit/delete', [
            'kit'      => $kit,
            'kitLabel' => ExperienceKitData::kitLabels()[$kit['kit_slug']] ?? $kit['kit_slug'],
        ]);
    }

    /** @param array<string,string> $params */
    public function destroy(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $kit = self::findKit($params['id'] ?? '');
        if ($kit === null) {
            self::redirect404();
            return;
        }

        // client_kit_logs se borra en cascada (FK ON DELETE CASCADE).
        $stmt = Connection::get()->prepare('DELETE FROM client_kits WHERE id = :id');
        $stmt->execute([':id' => (int) $kit['id']]);

        self::setFlash('Kit eliminado.');
        View::redirect('/admin/experience-kit');
    }

    /** @param array<string,string> $params */
    public function finish(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $id = (preg_match('/^[1-9][0-9]{0,9}$/', $params['id'] ?? '') === 1) ? (int) $params['id'] : 0;
        if ($id > 0) {
            $stmt = Connection::get()->prepare('UPDATE client_kits SET is_active = FALSE WHERE id = :id');
            $stmt->execute([':id' => $id]);
            self::setFlash('Kit finalizado.');
        }

        View::redirect('/admin/experience-kit');
    }

    /** @return array<string,mixed>|null */
    private static function findKit(string $rawId): ?array
    {
        $id = (preg_match('/^[1-9][0-9]{0,9}$/', $rawId) === 1) ? (int) $rawId : 0;
        if ($id <= 0) {
            return null;
        }

        $stmt = Connection::get()->prepare(
            "SELECT ck.id, ck.kit_slug, ck.started_at, ck.weight_kg,
                    u.id AS user_id, u.name, u.email, u.role
               FROM client_kits ck
               JOIN users u ON u.id = ck.user_id
              WHERE ck.id = :id"
        );
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
