<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Catálogo estático del WCA Experience Kit (Feature 2 del plan de Marta,
 * docs/PLAN_A_1act sección 4). Calendario de 7 días, metas de pasos y
 * campos del diario/encuesta por kit — todo verificado contra el documento
 * original, sección 5.1 (calendario), 5.3 (campos del diario) y la tabla de
 * metas de pasos/insignias de la sección 4.4/4.6.
 *
 * Actualización docs/agregado.docx: el kit Balance ahora también usa Ice
 * Wave 24h (junto con X39 + Aeon 24h) los días 1, 3 y 5. Ese mismo documento
 * menciona kits "Pain Relief", "Vitality" y "Longevity" y un campo de
 * diario "Nivel de dolor", pero no trae el calendario día a día de esos
 * kits — no se agregaron aquí porque calendar() sin ese dato rompería
 * /mi-kit al seleccionarlos. Pendiente de que Rub confirme esos calendarios.
 */
final class ExperienceKitData
{
    private const WATER_GLASS_ML = 250;
    private const HYDRATION_ML_PER_KG = 33;
    private const DEFAULT_WATER_GOAL_GLASSES = 8;

    /** @return array<string, array{label: string, patches: array<int, array{slug: string, name: string, hours: string}>}> */
    public static function calendar(): array
    {
        $x39 = ['slug' => 'x39', 'name' => 'X39', 'hours' => '12h'];
        $x49 = ['slug' => 'x49', 'name' => 'X49', 'hours' => '12h'];
        $sp6 = ['slug' => 'sp6', 'name' => 'SP6', 'hours' => '12h'];
        $alavida = ['slug' => 'alavida', 'name' => 'Alavida', 'hours' => '12h (noche)'];
        $silentnights = ['slug' => 'silentnights', 'name' => 'Silent Nights', 'hours' => 'noche'];
        $aeon24 = ['slug' => 'aeon', 'name' => 'Aeon', 'hours' => '24h'];
        $icewave24 = ['slug' => 'icewave', 'name' => 'Ice Wave', 'hours' => '24h'];

        return [
            'performance' => [
                'label' => 'Performance',
                'days' => [[$x39], [$x49], [$x39], [$x49], [$x39], [$x49], [$x39]],
            ],
            'menopause' => [
                'label' => 'Menopause',
                'days' => array_fill(0, 7, [$x39]),
            ],
            'menopause-premium' => [
                'label' => 'Menopause Premium',
                'days' => [[$x39, $alavida], [$x39, $sp6], [$x39, $alavida], [$x39, $sp6], [$x39, $alavida], [$x39, $sp6], [$x39, $alavida]],
            ],
            'sleep' => [
                'label' => 'Sleep',
                'days' => array_fill(0, 7, [$silentnights]),
            ],
            'heart-wellness' => [
                'label' => 'Heart & Wellness',
                'days' => array_fill(0, 7, [$x39]),
            ],
            'balance' => [
                'label' => 'Balance',
                'days' => [
                    [$x39, $aeon24, $icewave24], [$x39],
                    [$x39, $aeon24, $icewave24], [$x39],
                    [$x39, $aeon24, $icewave24], [$x39],
                    [$x39],
                ],
            ],
            'senior' => [
                'label' => 'Senior',
                'days' => array_fill(0, 7, [$x39]),
            ],
        ];
    }

    /** @return array<string, string> */
    public static function kitLabels(): array
    {
        $out = [];
        foreach (self::calendar() as $slug => $kit) {
            $out[$slug] = $kit['label'];
        }
        return $out;
    }

    /** @return array<int, array{slug: string, name: string, hours: string}> */
    public static function patchesForDay(string $kitSlug, int $dayNumber): array
    {
        $calendar = self::calendar();
        if (!isset($calendar[$kitSlug]) || $dayNumber < 1 || $dayNumber > 7) {
            return [];
        }
        return $calendar[$kitSlug]['days'][$dayNumber - 1];
    }

    public static function stepsGoal(string $kitSlug): int
    {
        return $kitSlug === 'performance' ? 10000 : 2500;
    }

    /**
     * Meta de vasos de agua (250ml) según el peso del cliente. Guía general
     * de bienestar (33ml por kg de peso corporal), no indicación médica —
     * ver aviso de bienestar. Si no hay peso registrado, usa una meta
     * genérica de 8 vasos.
     */
    public static function waterGoalGlasses(?float $weightKg): int
    {
        if ($weightKg === null || $weightKg <= 0) {
            return self::DEFAULT_WATER_GOAL_GLASSES;
        }
        $ml = $weightKg * self::HYDRATION_ML_PER_KG;
        return (int) ceil($ml / self::WATER_GLASS_ML);
    }

    /**
     * Campos base del diario/encuesta (todos los kits), escala 1-10.
     *
     * @return array<string, string>
     */
    public static function diaryBaseFields(): array
    {
        return [
            'sleep_quality'  => 'Calidad del sueño',
            'energy'         => 'Energía y vitalidad',
            'resistance'     => 'Resistencia',
            'recovery_time'  => 'Tiempo de recuperación después del ejercicio',
            'muscle_tone'    => 'Fuerza y tono muscular',
            'mobility'       => 'Movilidad',
            'wellbeing'      => 'Sensación de bienestar',
        ];
    }

    /**
     * Campos adicionales del diario según el kit. type: scale10 | number.
     *
     * @return array<string, array{label: string, type: string}>
     */
    public static function diaryExtraFields(string $kitSlug): array
    {
        return match ($kitSlug) {
            'menopause', 'menopause-premium' => [
                'hot_flashes'       => ['label' => 'Bochornos', 'type' => 'scale10'],
                'skin_hydration'    => ['label' => 'Hidratación de la piel', 'type' => 'scale10'],
                'weight'            => ['label' => 'Peso (opcional, kg)', 'type' => 'number'],
                'expression_lines'  => ['label' => 'Líneas de expresión', 'type' => 'scale10'],
                'cravings'          => ['label' => 'Hambre/antojos', 'type' => 'scale10'],
            ],
            'sleep' => [
                'wake_count'    => ['label' => 'Veces que despertó en la noche', 'type' => 'number'],
                'wake_feeling'  => ['label' => 'Sensación al despertar', 'type' => 'scale10'],
            ],
            'balance' => [
                'stress_level'    => ['label' => 'Nivel de estrés', 'type' => 'scale10'],
                'anxiety_level'   => ['label' => 'Nivel de ansiedad', 'type' => 'scale10'],
                'emotional_state' => ['label' => 'Estado emocional', 'type' => 'scale10'],
            ],
            default => [],
        };
    }

    /** @param array<string,mixed> $log fila de client_kit_logs */
    public static function waterGoalMet(array $log, ?float $weightKg): bool
    {
        return (int) $log['water_count'] >= self::waterGoalGlasses($weightKg);
    }

    /** @param array<string,mixed> $log fila de client_kit_logs */
    public static function stepsGoalMet(array $log, string $kitSlug): bool
    {
        return $log['steps'] !== null && (int) $log['steps'] >= self::stepsGoal($kitSlug);
    }

    /**
     * Día "cumplido" para efectos de insignias: parche puesto, meta de
     * hidratación alcanzada y meta de pasos alcanzada.
     *
     * @param array<string,mixed> $log fila de client_kit_logs
     */
    public static function isDayComplete(array $log, string $kitSlug, ?float $weightKg): bool
    {
        return (bool) $log['patch_applied']
            && self::waterGoalMet($log, $weightKg)
            && self::stepsGoalMet($log, $kitSlug);
    }

    /**
     * Estado de hidratación reusado tanto por el panel admin como por
     * "Mis Experience" del promotor (docs/SCOPE OF WORK.md sección 10.3:
     * no duplicar esta lógica). "Falta seguimiento" = sin registro de agua
     * o pasaron 4 horas o más desde el último.
     *
     * @param array<string,mixed>|null $log fila de client_kit_logs del día actual, o null si aún no existe
     * @return array{hoursSinceWater: int|null, needsFollowUp: bool}
     */
    public static function hydrationStatus(?array $log): array
    {
        $hoursSinceWater = null;
        if ($log !== null && $log['water_last_at']) {
            $last = new \DateTimeImmutable((string) $log['water_last_at']);
            $hoursSinceWater = (int) floor((time() - $last->getTimestamp()) / 3600);
        }

        return [
            'hoursSinceWater' => $hoursSinceWater,
            'needsFollowUp'   => $hoursSinceWater === null || $hoursSinceWater >= 4,
        ];
    }

    /**
     * Insignia alcanzada según los 7 registros del kit (uno por día, puede
     * haber menos de 7 si el kit está en curso).
     *
     * @param array<int, array<string,mixed>> $logs
     * @return array{badge: string|null, completedDays: int, exerciseDays: int}
     */
    public static function badgeProgress(array $logs, string $kitSlug, ?float $weightKg): array
    {
        $completedDays = 0;
        $exerciseDays = 0;
        foreach ($logs as $log) {
            if (self::isDayComplete($log, $kitSlug, $weightKg)) {
                $completedDays++;
            }
            if ($log['exercise_done']) {
                $exerciseDays++;
            }
        }

        $badge = null;
        if ($completedDays >= 4) {
            $badge = 'silver';
        }
        if ($completedDays >= 7) {
            $badge = 'gold';
        }
        if ($badge === 'gold' && $exerciseDays >= 3) {
            $badge = 'diamond';
        }

        return ['badge' => $badge, 'completedDays' => $completedDays, 'exerciseDays' => $exerciseDays];
    }

    /** Texto oficial del aviso de bienestar (docs/PLAN_A_1act sección 5.4). */
    public static function wellnessNotice(): string
    {
        return "Este programa apoya tu bienestar general. No sustituye diagnóstico ni tratamiento médico.\n"
            . "Retire el parche de inmediato en caso de malestar o irritación cutánea.\n"
            . "No reutilice el parche una vez que lo haya retirado de la piel.\n"
            . "Solo para uso externo. No ingerir.\n"
            . "No aplicar en heridas o piel dañada.\n"
            . "Consulte a su médico antes del uso si tiene alguna enfermedad o en caso de preguntas o dudas con relación a su salud.\n"
            . "No destinado al uso por niños.\n"
            . "Aplique/utilice los parches únicamente de la manera indicada.\n"
            . "Conserve el producto a temperatura ambiente.";
    }
}
