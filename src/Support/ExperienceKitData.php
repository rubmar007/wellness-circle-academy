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
 * Actualización 2026-08-10 (tabla oficial "WCA Experience Kits – 7 días"):
 * composición corregida contra la tabla de referencia de Marta. Cambios vs.
 * el calendario anterior:
 * - Menopause Premium se separó en dos kits: 'menopause-premium-intl'
 *   (X39+Alavida+SP6) y 'menopause-premium-mx' (X39+Aeon+SP6). El slug
 *   viejo 'menopause-premium' ya NO aparece en el selector (ver
 *   database/migrations/2026-08-10-fix-kit-composition.sql) pero se dejó
 *   permitido en el CHECK de la BD porque hay 1 cliente ya asignado con ese
 *   slug (Marta, client_kits.id=15) — hay que reasignarlo manualmente desde
 *   el admin a uno de los dos kits nuevos.
 * - Balance: se quitó Ice Wave (la tabla oficial solo marca Aeon x3, no Ice
 *   Wave). La actualización docs/agregado.docx que había agregado Ice Wave
 *   a Balance quedó revertida por esta tabla más reciente.
 * - Pain Relief: Ice Wave baja de 3 a 2 usos (Aeon sigue en 3).
 * - Senior: se agrega Carnosine x3 (antes no tenía nada además de X39).
 * - Longevity: se agrega Glutation x3 (antes no tenía nada además de X39).
 * - Heart & Wellness: pasa de X39 los 7 días a X39 x5 + X49 x2.
 * Patrón de días usado para "parche extra en N de 7 días": mismo patrón ya
 * usado en Balance/Pain Relief (días 1, 3 y 5), salvo Menopause Premium que
 * alterna todos los días excepto el 7 (ver detalle abajo).
 */
final class ExperienceKitData
{
    private const WATER_GLASS_ML = 250;
    private const HYDRATION_ML_PER_KG = 33;
    private const DEFAULT_WATER_GOAL_GLASSES = 8;
    private const BADGE_HYDRATION_RATIO = 0.6;
    private const DIAMOND_STEPS_GOAL    = 2000;

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
        $carnosine24 = ['slug' => 'carnosine', 'name' => 'Carnosine', 'hours' => '24h'];
        $glutation24 = ['slug' => 'glutation', 'name' => 'Glutation', 'hours' => '24h'];

        return [
            'performance' => [
                'label' => 'Performance',
                'days' => [[$x39], [$x49], [$x39], [$x49], [$x39], [$x49], [$x39]],
            ],
            'menopause' => [
                'label' => 'Menopause',
                'days' => array_fill(0, 7, [$x39]),
            ],
            'menopause-premium-intl' => [
                'label' => 'Menopause Premium – Internacional',
                'days' => [[$x39, $alavida], [$x39, $sp6], [$x39, $alavida], [$x39, $sp6], [$x39, $alavida], [$x39, $sp6], [$x39]],
            ],
            'menopause-premium-mx' => [
                'label' => 'Menopause Premium – México',
                'days' => [[$x39, $aeon24], [$x39, $sp6], [$x39, $aeon24], [$x39, $sp6], [$x39, $aeon24], [$x39, $sp6], [$x39]],
            ],
            'sleep' => [
                'label' => 'Sleep',
                'days' => array_fill(0, 7, [$silentnights]),
            ],
            'heart-wellness' => [
                'label' => 'Heart & Wellness',
                'days' => [[$x39], [$x39], [$x49], [$x39], [$x39], [$x49], [$x39]],
            ],
            'balance' => [
                'label' => 'Balance',
                'days' => [
                    [$x39, $aeon24], [$x39],
                    [$x39, $aeon24], [$x39],
                    [$x39, $aeon24], [$x39],
                    [$x39],
                ],
            ],
            'senior' => [
                'label' => 'Senior',
                'days' => [
                    [$x39, $carnosine24], [$x39],
                    [$x39, $carnosine24], [$x39],
                    [$x39, $carnosine24], [$x39],
                    [$x39],
                ],
            ],
            'pain-relief' => [
                'label' => 'Pain Relief',
                'days' => [
                    [$x39, $aeon24, $icewave24], [$x39],
                    [$x39, $aeon24, $icewave24], [$x39],
                    [$x39, $aeon24], [$x39],
                    [$x39],
                ],
            ],
            'vitality' => [
                'label' => 'Vitality',
                'days' => array_fill(0, 7, [$x39]),
            ],
            'longevity' => [
                'label' => 'Longevity',
                'days' => [
                    [$x39, $glutation24], [$x39],
                    [$x39, $glutation24], [$x39],
                    [$x39, $glutation24], [$x39],
                    [$x39],
                ],
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

    /**
     * Día transcurrido desde el inicio del kit (1 = día de inicio), SIN
     * límite superior — puede ser mayor a 7 si ya se cumplió el kit. Única
     * fuente de verdad para este cálculo: antes existían copias sueltas en
     * MyExperiencesController y AdminClientKitsController con el signo
     * invertido (`1 + diff` en vez de `1 - diff`), lo que las dejaba
     * pegadas en "Día 1" para siempre sin importar cuántos días hubieran
     * pasado. No lo vuelvas a duplicar inline — usa este método.
     */
    public static function rawDayNumberForStartDate(string $startedAt): int
    {
        $started = new \DateTimeImmutable($startedAt);
        $today   = new \DateTimeImmutable('today');
        $diff    = (int) $today->diff($started)->format('%r%a');
        return max(1, 1 - $diff); // días transcurridos + 1
    }

    /**
     * Día del calendario de 7 días (1-7) en el que va un kit según su fecha
     * de inicio, comparado contra hoy. Compartido por ClientKitController
     * (área del cliente) y PushService (recordatorios push personalizados).
     */
    public static function dayNumberForStartDate(string $startedAt): int
    {
        return min(7, self::rawDayNumberForStartDate($startedAt));
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
            'menopause', 'menopause-premium-intl', 'menopause-premium-mx' => [
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
            'pain-relief' => [
                'stress_level'    => ['label' => 'Nivel de estrés', 'type' => 'scale10'],
                'anxiety_level'   => ['label' => 'Nivel de ansiedad', 'type' => 'scale10'],
                'pain_level'      => ['label' => 'Nivel de dolor', 'type' => 'scale10'],
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
     * Umbral de hidratación para efectos de insignias (Silver/Gold): 60% de
     * la meta normal del día (peso × 33ml/kg), NO el 100% que se usa para
     * el indicador "meta cumplida" del checklist diario ni para el stat
     * informativo "Hidratación cumplida" del resumen — esos siguen usando
     * waterGoalMet() sin cambios.
     *
     * @param array<string,mixed> $log fila de client_kit_logs
     */
    public static function badgeHydrationMet(array $log, ?float $weightKg): bool
    {
        $required = (int) ceil(self::waterGoalGlasses($weightKg) * self::BADGE_HYDRATION_RATIO);
        return (int) $log['water_count'] >= $required;
    }

    /**
     * Día "cumplido" para Silver/Gold: parche puesto, al menos 60% de la
     * meta de hidratación del día, y el diario guardado ese día (con que
     * exista el registro basta, no se exige que estén todas las preguntas
     * contestadas). Los pasos NO cuentan aquí — solo importan para Diamond.
     *
     * @param array<string,mixed> $log fila de client_kit_logs
     */
    public static function isDayComplete(array $log, ?float $weightKg): bool
    {
        return (bool) $log['patch_applied']
            && self::badgeHydrationMet($log, $weightKg)
            && $log['diary'] !== null;
    }

    /**
     * Día "cumplido" para el requisito extra de Diamond: mínimo 2000 pasos
     * y algún tipo de ejercicio marcado ese día. Mismo umbral de pasos para
     * cualquier Experience Kit (no usa stepsGoal(), que varía por kit).
     *
     * @param array<string,mixed> $log fila de client_kit_logs
     */
    public static function diamondDayMet(array $log): bool
    {
        return $log['steps'] !== null
            && (int) $log['steps'] >= self::DIAMOND_STEPS_GOAL
            && (bool) $log['exercise_done'];
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
     * haber menos de 7 si el kit está en curso). Reglas confirmadas por Rub
     * 2026-08-22:
     * - Silver: 4 de los 7 días "cumplidos" (parche + hidratación ≥60% +
     *   diario guardado). Los pasos no cuentan.
     * - Gold: los 7 días "cumplidos" con el mismo criterio que Silver.
     * - Diamond: Gold + los 7 días con ≥2000 pasos y ejercicio marcado
     *   (aplica igual a cualquier Experience Kit, no solo Performance).
     *
     * $kitSlug ya no participa en el cálculo de insignias (el paso de la
     * meta de pasos por kit se usaba antes; ahora Diamond usa un umbral
     * fijo de 2000 pasos para todos) — se deja el parámetro por
     * compatibilidad con los llamadores existentes.
     *
     * @param array<int, array<string,mixed>> $logs
     * @return array{badge: string|null, completedDays: int, exerciseDays: int}
     */
    public static function badgeProgress(array $logs, string $kitSlug, ?float $weightKg): array
    {
        $completedDays = 0;
        $exerciseDays  = 0;
        $diamondDays   = 0;
        foreach ($logs as $log) {
            if (self::isDayComplete($log, $weightKg)) {
                $completedDays++;
            }
            if ($log['exercise_done']) {
                $exerciseDays++;
            }
            if (self::diamondDayMet($log)) {
                $diamondDays++;
            }
        }

        $badge = null;
        if ($completedDays >= 4) {
            $badge = 'silver';
        }
        if ($completedDays >= 7) {
            $badge = 'gold';
        }
        if ($badge === 'gold' && $diamondDays >= 7) {
            $badge = 'diamond';
        }

        return ['badge' => $badge, 'completedDays' => $completedDays, 'exerciseDays' => $exerciseDays];
    }

    /** @return array<string, string> */
    public static function badgeLabels(): array
    {
        return ['silver' => 'Silver', 'gold' => 'Gold', 'diamond' => 'Diamond'];
    }

    /** Imagen del diploma/insignia (Silver/Gold/Diamond) para mostrar al ganarla. */
    public static function badgeImagePath(?string $badge): ?string
    {
        if (!isset(self::badgeLabels()[$badge ?? ''])) {
            return null;
        }
        return '/assets/img/badges/' . $badge . '.png';
    }

    /**
     * Mensaje de felicitación al terminar los 7 días (pantalla "Mi Kit" y
     * push de finalización). Mismo texto en ambos canales a propósito.
     */
    public static function completionMessage(?string $badge): string
    {
        if ($badge !== null) {
            $badgeName = self::badgeLabels()[$badge] ?? $badge;
            return "¡Felicidades, completaste tus 7 días! Lograste tu insignia \"{$badgeName}\". "
                . "Ponte en contacto con tu promotor para pedir tu siguiente kit o empezar con un reto de 30 o 90 días.";
        }
        return "¡Completaste tus 7 días! Ponte en contacto con tu promotor para pedir tu siguiente kit "
            . "o empezar con un reto de 30 o 90 días.";
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
