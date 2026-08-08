<?php
declare(strict_types=1);
/**
 * @var array<string,mixed> $kit
 * @var string $kitLabel
 * @var array<int, array<int, array{slug:string,name:string,hours:string}>> $calendar
 * @var int $dayNumber
 * @var array<int, array<string,mixed>> $logsByDay
 * @var string $status  'al_dia' | 'seguimiento' | 'completado'
 * @var int $waterGoal
 * @var int $stepsGoal
 * @var array<string,string> $baseFields
 * @var array<string, array{label:string,type:string}> $extraFields
 * @var array{badge:string|null,completedDays:int,exerciseDays:int} $badge
 * @var array{completedDays:int,daysHydration:int,daysSteps:int,daysExercise:int,daysDiary:int,badge:string|null}|null $summary
 */
$pageTitle = 'Mis Experience — ' . $kit['name'];

$statusLabels = [
    'al_dia'      => ['label' => 'Al día', 'class' => 'badge-success'],
    'seguimiento' => ['label' => 'Requiere seguimiento', 'class' => 'badge-error'],
    'completado'  => ['label' => 'Completado', 'class' => 'badge-gold'],
];
$badgeLabels = ['silver' => 'Silver', 'gold' => 'Gold', 'diamond' => 'Diamond'];
$st = $statusLabels[$status];
?>
<section class="page-head">
    <p class="breadcrumb"><a href="/mis-experience">Mis Experience</a> &rsaquo; <span><?= e($kit['name']) ?></span></p>
    <h1><?= e($kit['name']) ?></h1>
    <p class="muted"><?= e($kitLabel) ?> — Día <?= e($dayNumber) ?> de 7 · <span class="badge <?= $st['class'] ?>"><?= e($st['label']) ?></span></p>
</section>

<article class="section-card">
    <h2>Estado de hoy</h2>
    <?php $todayLog = $logsByDay[$dayNumber] ?? null; ?>
    <?php if ($todayLog === null): ?>
        <p class="muted">Todavía no hay registro para hoy.</p>
    <?php else: ?>
        <ul class="mx-facts">
            <li>Parche de hoy: <strong><?= $todayLog['patch_applied'] ? 'Completado' : 'Pendiente' ?></strong></li>
            <li>Hidratación: <strong><?= e($todayLog['water_count']) ?>/<?= e($waterGoal) ?> vasos</strong></li>
            <li>Pasos: <strong><?= $todayLog['steps'] !== null ? e($todayLog['steps']) . '/' . e($stepsGoal) : 'Sin registrar' ?></strong></li>
            <li>Ejercicio: <strong><?= $todayLog['exercise_done'] ? 'Sí (' . e($todayLog['exercise_type'] ?? '—') . ')' : 'No' ?></strong></li>
            <li>Diario: <strong><?= $todayLog['diary'] !== null ? 'Contestado' : 'Pendiente' ?></strong></li>
        </ul>
    <?php endif; ?>
</article>

<?php if ($summary !== null): ?>
    <article class="section-card">
        <h2>Resumen del Experience completado</h2>
        <ul class="mx-facts">
            <li>Días completados: <strong><?= e($summary['completedDays']) ?>/7</strong></li>
            <li>Hidratación cumplida: <strong><?= e($summary['daysHydration']) ?>/7</strong></li>
            <li>Movimiento (meta de pasos): <strong><?= e($summary['daysSteps']) ?>/7</strong></li>
            <li>Ejercicio registrado: <strong><?= e($summary['daysExercise']) ?>/7</strong></li>
            <li>Diario completado: <strong><?= e($summary['daysDiary']) ?>/7</strong></li>
        </ul>
        <p>
            <?php if ($summary['badge'] !== null): ?>
                <span class="badge badge-gold">Insignia obtenida: <?= e($badgeLabels[$summary['badge']] ?? $summary['badge']) ?></span>
            <?php else: ?>
                <span class="muted">No alcanzó ninguna insignia.</span>
            <?php endif; ?>
        </p>
    </article>
<?php else: ?>
    <article class="section-card">
        <h2>Puntos e insignias (en curso)</h2>
        <p>
            Días cumplidos (parche + hidratación + pasos): <strong><?= e($badge['completedDays']) ?>/7</strong>
            &nbsp;·&nbsp;
            Días con ejercicio: <strong><?= e($badge['exerciseDays']) ?></strong>
        </p>
        <?php if ($badge['badge'] !== null): ?>
            <p><span class="badge badge-gold">Insignia actual: <?= e($badgeLabels[$badge['badge']] ?? $badge['badge']) ?></span></p>
        <?php endif; ?>
    </article>
<?php endif; ?>

<article class="section-card">
    <h2>Historial diario</h2>
    <div class="mx-day-list">
        <?php foreach ($calendar as $i => $patches): $d = $i + 1; $log = $logsByDay[$d] ?? null; ?>
            <div class="mx-day <?= $d === $dayNumber ? 'kit-day-current' : '' ?>">
                <div class="mx-day-head">
                    <span class="kit-day-number">Día <?= $d ?></span>
                    <span class="kit-day-patches">
                        <?php foreach ($patches as $p): ?>
                            <span class="kit-day-patch"><?= e($p['name']) ?> <small><?= e($p['hours']) ?></small></span>
                        <?php endforeach; ?>
                    </span>
                </div>
                <?php if ($log === null): ?>
                    <p class="muted small">Sin registro todavía.</p>
                <?php else: ?>
                    <ul class="mx-facts mx-facts-compact">
                        <li>Parche: <strong><?= $log['patch_applied'] ? 'sí' : 'no' ?></strong></li>
                        <li>Agua: <strong><?= e($log['water_count']) ?> vasos</strong></li>
                        <li>Pasos: <strong><?= $log['steps'] !== null ? e($log['steps']) : '—' ?></strong></li>
                        <li>Ejercicio: <strong><?= $log['exercise_done'] ? 'sí' : 'no' ?></strong></li>
                        <li>Diario: <strong><?= $log['diary'] !== null ? 'contestado' : 'pendiente' ?></strong></li>
                    </ul>
                    <?php
                        $diary = is_string($log['diary'] ?? null) ? (json_decode((string) $log['diary'], true) ?: []) : [];
                    ?>
                    <?php if (is_array($diary) && $diary !== []): ?>
                        <details class="mx-diary-details">
                            <summary>Ver respuestas del diario</summary>
                            <ul class="mx-facts mx-facts-compact">
                                <?php foreach ($diary as $field => $value): ?>
                                    <?php $label = $baseFields[$field] ?? ($extraFields[$field]['label'] ?? $field); ?>
                                    <li><?= e($label) ?>: <strong><?= e((string) $value) ?></strong></li>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</article>
