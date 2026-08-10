<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database\Connection;
use App\Support\ExperienceKitData;
use App\View;

/**
 * "Mis Experience" — vista de solo lectura para el Promotor responsable
 * (role=member) sobre los Experience Kit que tiene asignados como
 * seguimiento (docs/SCOPE OF WORK.md). No hay ningún CREATE/UPDATE/DELETE
 * en este controlador a propósito (RULE-07: 100% read-only).
 *
 * Seguridad (sección 8 y 20 del scope): todo filtra por
 * client_kits.promoter_id = usuario autenticado, resuelto desde la sesión
 * — nunca desde un parámetro de la URL o del request.
 */
final class MyExperiencesController
{
    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        $promoterId = self::requirePromoter();

        $tab = ($_GET['tab'] ?? 'activos') === 'completados' ? 'completados' : 'activos';

        $stmt = Connection::get()->prepare(
            "SELECT ck.id, ck.kit_slug, ck.started_at, ck.weight_kg, ck.is_active,
                    u.id AS user_id, u.name, u.email
               FROM client_kits ck
               JOIN users u ON u.id = ck.user_id
              WHERE ck.promoter_id = :p AND ck.is_active = :active
              ORDER BY u.name ASC"
        );
        $stmt->execute([':p' => $promoterId, ':active' => $tab === 'activos' ? 't' : 'f']);
        $kits = $stmt->fetchAll();

        $today = new \DateTimeImmutable('today');
        $rows  = [];
        foreach ($kits as $kit) {
            $started   = new \DateTimeImmutable((string) $kit['started_at']);
            $dayNumber = max(1, min(7, 1 + (int) $today->diff($started)->format('%r%a')));

            $log = self::dayLog((int) $kit['id'], $dayNumber);
            $hydration = ExperienceKitData::hydrationStatus($log);

            $rows[] = [
                'kit'           => $kit,
                'dayNumber'     => $dayNumber,
                'patchApplied'  => $log ? (bool) $log['patch_applied'] : false,
                'exerciseDone'  => $log ? (bool) $log['exercise_done'] : false,
                'diaryAnswered' => $log !== null && $log['diary'] !== null,
                'status'        => !$kit['is_active'] ? 'completado' : ($hydration['needsFollowUp'] ? 'seguimiento' : 'al_dia'),
            ];
        }

        // Métricas (sección 15): sobre TODO lo asignado a este promotor, sin importar el tab actual.
        $stats = Connection::get()->prepare(
            "SELECT
                (SELECT COUNT(*) FROM client_kits WHERE promoter_id = :p1 AND is_active = TRUE)  AS activos,
                (SELECT COUNT(*) FROM client_kits WHERE promoter_id = :p2 AND is_active = FALSE) AS completados,
                (SELECT COUNT(DISTINCT user_id) FROM client_kits WHERE promoter_id = :p3)        AS personas"
        );
        $stats->execute([':p1' => $promoterId, ':p2' => $promoterId, ':p3' => $promoterId]);
        $totals = $stats->fetch();

        View::render('my-experiences/index', [
            'tab'       => $tab,
            'rows'      => $rows,
            'kitLabels' => ExperienceKitData::kitLabels(),
            'totals'    => $totals,
        ]);
    }

    /** @param array<string,string> $params */
    public function show(array $params): void
    {
        $promoterId = self::requirePromoter();

        $id = (preg_match('/^[1-9][0-9]{0,9}$/', $params['id'] ?? '') === 1) ? (int) $params['id'] : 0;
        if ($id <= 0) {
            self::redirect404();
            return;
        }

        // La propiedad se valida SIEMPRE contra la sesión, nunca contra el id de la URL.
        $stmt = Connection::get()->prepare(
            "SELECT ck.id, ck.kit_slug, ck.started_at, ck.weight_kg, ck.is_active,
                    u.id AS user_id, u.name, u.email
               FROM client_kits ck
               JOIN users u ON u.id = ck.user_id
              WHERE ck.id = :id AND ck.promoter_id = :p"
        );
        $stmt->execute([':id' => $id, ':p' => $promoterId]);
        $kit = $stmt->fetch();
        if (!$kit) {
            self::redirect404();
            return;
        }

        $logsStmt = Connection::get()->prepare(
            'SELECT * FROM client_kit_logs WHERE client_kit_id = :k ORDER BY day_number ASC'
        );
        $logsStmt->execute([':k' => (int) $kit['id']]);
        $logs = $logsStmt->fetchAll();
        $logsByDay = [];
        foreach ($logs as $log) {
            $logsByDay[(int) $log['day_number']] = $log;
        }

        $kitSlug  = (string) $kit['kit_slug'];
        $weightKg = $kit['weight_kg'] !== null ? (float) $kit['weight_kg'] : null;

        $today     = new \DateTimeImmutable('today');
        $started   = new \DateTimeImmutable((string) $kit['started_at']);
        $dayNumber = max(1, min(7, 1 + (int) $today->diff($started)->format('%r%a')));
        $todayLog  = $logsByDay[$dayNumber] ?? null;
        $hydration = ExperienceKitData::hydrationStatus($todayLog);

        $status = !$kit['is_active']
            ? 'completado'
            : ($hydration['needsFollowUp'] ? 'seguimiento' : 'al_dia');

        $badge = ExperienceKitData::badgeProgress($logs, $kitSlug, $weightKg);

        // Resumen final (sección 16), solo relevante si el Experience ya terminó.
        $summary = null;
        if (!$kit['is_active']) {
            $daysHydration = 0;
            $daysSteps     = 0;
            $daysExercise  = 0;
            $daysDiary     = 0;
            foreach ($logs as $log) {
                if (ExperienceKitData::waterGoalMet($log, $weightKg)) {
                    $daysHydration++;
                }
                if (ExperienceKitData::stepsGoalMet($log, $kitSlug)) {
                    $daysSteps++;
                }
                if ($log['exercise_done']) {
                    $daysExercise++;
                }
                if ($log['diary'] !== null) {
                    $daysDiary++;
                }
            }
            $summary = [
                'completedDays' => $badge['completedDays'],
                'daysHydration' => $daysHydration,
                'daysSteps'     => $daysSteps,
                'daysExercise'  => $daysExercise,
                'daysDiary'     => $daysDiary,
                'badge'         => $badge['badge'],
            ];
        }

        View::render('my-experiences/show', [
            'kit'        => $kit,
            'kitLabel'   => ExperienceKitData::kitLabels()[$kitSlug] ?? $kitSlug,
            'calendar'   => ExperienceKitData::calendar()[$kitSlug]['days'] ?? [],
            'dayNumber'  => $dayNumber,
            'logsByDay'  => $logsByDay,
            'status'     => $status,
            'waterGoal'  => ExperienceKitData::waterGoalGlasses($weightKg),
            'stepsGoal'  => ExperienceKitData::stepsGoal($kitSlug),
            'baseFields' => ExperienceKitData::diaryBaseFields(),
            'extraFields'=> ExperienceKitData::diaryExtraFields($kitSlug),
            'badge'      => $badge,
            'summary'    => $summary,
        ]);
    }

    // ----------------------------------------------------------------

    /**
     * Exige sesión + role=member o admin. Devuelve el id del promotor
     * autenticado. Admin también puede ser "Promotor responsable" — Rub
     * confirmó que Marta, además de dueña/admin de la app, es promotora
     * y tiene su propia red de promotores y clientes.
     */
    private static function requirePromoter(): int
    {
        Auth::requireLogin();
        $user = Auth::user();
        if ($user === null || !in_array($user['role'], ['member', 'admin'], true)) {
            http_response_code(403);
            require dirname(__DIR__, 2) . '/templates/errors/403.php';
            exit;
        }
        return (int) $user['id'];
    }

    /** @return array<string,mixed>|null */
    private static function dayLog(int $clientKitId, int $dayNumber): ?array
    {
        $stmt = Connection::get()->prepare(
            'SELECT * FROM client_kit_logs WHERE client_kit_id = :k AND day_number = :d LIMIT 1'
        );
        $stmt->execute([':k' => $clientKitId, ':d' => $dayNumber]);
        return $stmt->fetch() ?: null;
    }

    private static function redirect404(): void
    {
        http_response_code(404);
        require dirname(__DIR__, 2) . '/templates/errors/404.php';
    }
}
