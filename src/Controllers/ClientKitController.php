<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Database\Connection;
use App\Support\ExperienceKitData;
use App\View;
use PDO;

/**
 * WCA Experience Kit — área del cliente: calendario, checklist diario
 * (parche, hidratación, electrolitos, movimiento) y diario/encuesta.
 * Visible para cliente y member (promotor/distribuidor) — Auth::requireLogin.
 */
final class ClientKitController
{
    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireLogin();

        $kit = self::activeKit();
        if ($kit === null) {
            View::render('client-kit/empty', []);
            return;
        }

        $dayNumber = self::currentDayNumber($kit);
        $log       = self::todayLog($kit, $dayNumber);
        $logs      = self::allLogs((int) $kit['id']);
        $weightKg  = $kit['weight_kg'] !== null ? (float) $kit['weight_kg'] : null;

        View::render('client-kit/index', [
            'kit'         => $kit,
            'kitLabel'    => ExperienceKitData::kitLabels()[$kit['kit_slug']] ?? $kit['kit_slug'],
            'dayNumber'   => $dayNumber,
            'calendar'    => ExperienceKitData::calendar()[$kit['kit_slug']]['days'],
            'log'         => $log,
            'waterGoal'   => ExperienceKitData::waterGoalGlasses($weightKg),
            'stepsGoal'   => ExperienceKitData::stepsGoal($kit['kit_slug']),
            'badge'       => ExperienceKitData::badgeProgress($logs, $kit['kit_slug'], $weightKg),
            'notice'      => ExperienceKitData::wellnessNotice(),
            'flash'       => self::popFlash(),
        ]);
    }

    /** @param array<string,string> $params */
    public function diary(array $params): void
    {
        Auth::requireLogin();

        $kit = self::activeKit();
        if ($kit === null) {
            View::redirect('/mi-kit');
            return;
        }

        $dayNumber = self::currentDayNumber($kit);
        $log       = self::todayLog($kit, $dayNumber);
        $diary     = is_string($log['diary'] ?? null) ? (json_decode((string) $log['diary'], true) ?: []) : ($log['diary'] ?? []);

        View::render('client-kit/diary', [
            'kit'         => $kit,
            'kitLabel'    => ExperienceKitData::kitLabels()[$kit['kit_slug']] ?? $kit['kit_slug'],
            'dayNumber'   => $dayNumber,
            'baseFields'  => ExperienceKitData::diaryBaseFields(),
            'extraFields' => ExperienceKitData::diaryExtraFields($kit['kit_slug']),
            'diary'       => is_array($diary) ? $diary : [],
            'notes'       => (string) ($log['notes'] ?? ''),
            'flash'       => self::popFlash(),
        ]);
    }

    /** @param array<string,string> $params */
    public function saveDiary(array $params): void
    {
        Auth::requireLogin();
        Csrf::requireValid();

        $kit = self::activeKit();
        if ($kit === null) {
            View::redirect('/mi-kit');
            return;
        }

        $dayNumber = self::currentDayNumber($kit);
        $allFields = array_merge(
            array_keys(ExperienceKitData::diaryBaseFields()),
            array_keys(ExperienceKitData::diaryExtraFields($kit['kit_slug']))
        );
        $extraSpec = ExperienceKitData::diaryExtraFields($kit['kit_slug']);

        $diary = [];
        foreach ($allFields as $field) {
            $raw = $_POST['diary'][$field] ?? null;
            if ($raw === null || $raw === '') {
                continue;
            }
            $type = $extraSpec[$field]['type'] ?? 'scale10';
            if ($type === 'number') {
                $val = filter_var($raw, FILTER_VALIDATE_FLOAT);
                if ($val !== false) {
                    $diary[$field] = $val;
                }
            } else {
                $val = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10]]);
                if ($val !== false) {
                    $diary[$field] = $val;
                }
            }
        }
        $notes = trim((string) ($_POST['notes'] ?? ''));
        if (mb_strlen($notes) > 2000) {
            $notes = mb_substr($notes, 0, 2000);
        }

        self::upsertLog($kit, $dayNumber, [
            'diary' => json_encode($diary, JSON_UNESCAPED_UNICODE),
            'notes' => $notes !== '' ? $notes : null,
        ]);

        self::setFlash('Diario guardado.');
        View::redirect('/mi-kit');
    }

    /** @param array<string,string> $params */
    public function togglePatch(array $params): void
    {
        Auth::requireLogin();
        Csrf::requireValid();

        $kit = self::activeKit();
        if ($kit === null) {
            View::redirect('/mi-kit');
            return;
        }

        $dayNumber = self::currentDayNumber($kit);
        $log       = self::todayLog($kit, $dayNumber);

        self::upsertLog($kit, $dayNumber, [
            'patch_applied' => !$log['patch_applied'] ? 't' : 'f',
        ]);

        View::redirect('/mi-kit');
    }

    /** @param array<string,string> $params */
    public function toggleElectrolytes(array $params): void
    {
        Auth::requireLogin();
        Csrf::requireValid();

        $kit = self::activeKit();
        if ($kit === null) {
            View::redirect('/mi-kit');
            return;
        }

        $dayNumber = self::currentDayNumber($kit);
        $log       = self::todayLog($kit, $dayNumber);

        self::upsertLog($kit, $dayNumber, [
            'electrolytes_taken' => !$log['electrolytes_taken'] ? 't' : 'f',
        ]);

        View::redirect('/mi-kit');
    }

    /** @param array<string,string> $params */
    public function logWater(array $params): void
    {
        Auth::requireLogin();
        Csrf::requireValid();

        $kit = self::activeKit();
        if ($kit === null) {
            View::redirect('/mi-kit');
            return;
        }

        $dayNumber = self::currentDayNumber($kit);
        $log       = self::todayLog($kit, $dayNumber);

        self::upsertLog($kit, $dayNumber, [
            'water_count'   => (int) $log['water_count'] + 1,
            'water_last_at' => date('Y-m-d H:i:sP'),
        ]);

        View::redirect('/mi-kit');
    }

    /** @param array<string,string> $params */
    public function saveMovement(array $params): void
    {
        Auth::requireLogin();
        Csrf::requireValid();

        $kit = self::activeKit();
        if ($kit === null) {
            View::redirect('/mi-kit');
            return;
        }

        $dayNumber = self::currentDayNumber($kit);

        $steps = filter_input(INPUT_POST, 'steps', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 200000]]);
        $exerciseDone = isset($_POST['exercise_done']);
        $exerciseType = (string) ($_POST['exercise_type'] ?? '');
        if (!in_array($exerciseType, ['cardio', 'fuerza', 'ambos'], true)) {
            $exerciseType = null;
        }

        self::upsertLog($kit, $dayNumber, [
            'steps'         => $steps !== false && $steps !== null ? $steps : null,
            'exercise_done' => $exerciseDone ? 't' : 'f',
            'exercise_type' => $exerciseDone ? $exerciseType : null,
        ]);

        self::setFlash('Movimiento del día guardado.');
        View::redirect('/mi-kit');
    }

    /** @param array<string,string> $params */
    public function saveWeight(array $params): void
    {
        Auth::requireLogin();
        Csrf::requireValid();

        $kit = self::activeKit();
        if ($kit === null) {
            View::redirect('/mi-kit');
            return;
        }

        $weightKg = filter_input(INPUT_POST, 'weight_kg', FILTER_VALIDATE_FLOAT);
        if ($weightKg === false || $weightKg === null || $weightKg <= 0 || $weightKg > 400) {
            self::setFlash('Peso inválido. Ingresa un número entre 1 y 400 kg.', 'error');
            View::redirect('/mi-kit');
            return;
        }

        $stmt = Connection::get()->prepare('UPDATE client_kits SET weight_kg = :w WHERE id = :id');
        $stmt->execute([':w' => $weightKg, ':id' => (int) $kit['id']]);

        self::setFlash('Peso actualizado. Tu meta de hidratación ya se recalculó.');
        View::redirect('/mi-kit');
    }

    // ----------------------------------------------------------------

    /** @return array<string,mixed>|null */
    private static function activeKit(): ?array
    {
        $userId = (int) Auth::user()['id'];
        $stmt = Connection::get()->prepare(
            'SELECT * FROM client_kits WHERE user_id = :u AND is_active = TRUE LIMIT 1'
        );
        $stmt->execute([':u' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @param array<string,mixed> $kit */
    private static function currentDayNumber(array $kit): int
    {
        return ExperienceKitData::dayNumberForStartDate((string) $kit['started_at']);
    }

    /**
     * @param array<string,mixed> $kit
     * @return array<string,mixed>
     */
    private static function todayLog(array $kit, int $dayNumber): array
    {
        $stmt = Connection::get()->prepare(
            'SELECT * FROM client_kit_logs WHERE client_kit_id = :k AND day_number = :d LIMIT 1'
        );
        $stmt->execute([':k' => (int) $kit['id'], ':d' => $dayNumber]);
        $row = $stmt->fetch();

        if ($row) {
            return $row;
        }

        $stmt = Connection::get()->prepare(
            'INSERT INTO client_kit_logs (client_kit_id, day_number, log_date)
             VALUES (:k, :d, CURRENT_DATE)
             ON CONFLICT (client_kit_id, day_number) DO UPDATE SET client_kit_id = EXCLUDED.client_kit_id
             RETURNING *'
        );
        $stmt->execute([':k' => (int) $kit['id'], ':d' => $dayNumber]);
        return $stmt->fetch();
    }

    /** @return array<int, array<string,mixed>> */
    private static function allLogs(int $clientKitId): array
    {
        $stmt = Connection::get()->prepare(
            'SELECT * FROM client_kit_logs WHERE client_kit_id = :k ORDER BY day_number ASC'
        );
        $stmt->execute([':k' => $clientKitId]);
        return $stmt->fetchAll();
    }

    /**
     * @param array<string,mixed> $kit
     * @param array<string,mixed> $fields
     */
    private static function upsertLog(array $kit, int $dayNumber, array $fields): void
    {
        // Asegura que exista la fila del día antes de actualizar campos sueltos.
        self::todayLog($kit, $dayNumber);

        $sets   = [];
        $values = [':k' => (int) $kit['id'], ':d' => $dayNumber];
        foreach ($fields as $col => $val) {
            $sets[]        = "{$col} = :{$col}";
            $values[":{$col}"] = $val;
        }

        $stmt = Connection::get()->prepare(
            'UPDATE client_kit_logs SET ' . implode(', ', $sets) . '
              WHERE client_kit_id = :k AND day_number = :d'
        );
        $stmt->execute($values);
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
