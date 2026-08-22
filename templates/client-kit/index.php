<?php
declare(strict_types=1);

use App\Support\ExperienceKitData;

/**
 * @var array<string,mixed> $kit
 * @var string $kitLabel
 * @var int $dayNumber
 * @var array<int, array<int, array{slug:string,name:string,hours:string}>> $calendar
 * @var array<string,mixed> $log
 * @var int $waterGoal
 * @var int $stepsGoal
 * @var array{badge:string|null,completedDays:int,exerciseDays:int} $badge
 * @var string $notice
 * @var array{type:string,msg:string}|null $flash
 * @var string $csrf
 */
$pageTitle = 'Mi Kit';

$badgeLabels = ExperienceKitData::badgeLabels();
$badgeName   = $badge['badge'] !== null ? ($badgeLabels[$badge['badge']] ?? $badge['badge']) : null;
?>
<section class="page-head">
    <h1>WCA Experience Kit — <?= e($kitLabel) ?></h1>
    <p class="muted">Día <?= e($dayNumber) ?> de 7.</p>
</section>

<?php if ($flash !== null): ?>
    <p class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></p>
<?php endif; ?>

<article class="section-card">
    <h2>Calendario de tu kit</h2>
    <div class="kit-calendar">
        <?php foreach ($calendar as $i => $patches): $d = $i + 1; ?>
            <div class="kit-day <?= $d === $dayNumber ? 'kit-day-current' : '' ?> <?= $d < $dayNumber ? 'kit-day-past' : '' ?>">
                <span class="kit-day-number">Día <?= $d ?></span>
                <span class="kit-day-patches">
                    <?php foreach ($patches as $p): ?>
                        <span class="kit-day-patch"><?= e($p['name']) ?> <small><?= e($p['hours']) ?></small></span>
                    <?php endforeach; ?>
                </span>
                <?php if ($d === $dayNumber): ?><span class="kit-day-tag">HOY</span><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</article>

<article class="section-card">
    <h2>Checklist de hoy</h2>

    <div class="kit-checklist">
        <form method="post" action="/mi-kit/parche" class="kit-checklist-form">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button type="submit" class="kit-toggle <?= $log['patch_applied'] ? 'kit-toggle-done' : '' ?>" aria-pressed="<?= $log['patch_applied'] ? 'true' : 'false' ?>">
                <span class="kit-toggle-box"><?= $log['patch_applied'] ? '✓' : '' ?></span>
                Parche puesto hoy
            </button>
        </form>

        <form method="post" action="/mi-kit/electrolitos" class="kit-checklist-form">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button type="submit" class="kit-toggle <?= $log['electrolytes_taken'] ? 'kit-toggle-done' : '' ?>" aria-pressed="<?= $log['electrolytes_taken'] ? 'true' : 'false' ?>">
                <span class="kit-toggle-box"><?= $log['electrolytes_taken'] ? '✓' : '' ?></span>
                Electrolitos de hoy
            </button>
        </form>
    </div>

    <div class="kit-water">
        <form method="post" action="/mi-kit/peso" class="kit-weight-form">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <label for="weight_kg">Tu peso (kg) — define tu meta de hidratación</label>
            <div class="kit-weight-row">
                <input type="number" id="weight_kg" name="weight_kg" step="0.1" min="1" max="400"
                       value="<?= e($kit['weight_kg'] !== null ? (string) $kit['weight_kg'] : '') ?>"
                       placeholder="Ej. 65">
                <button type="submit" class="button button-ghost button-sm">Guardar peso</button>
            </div>
            <?php if ($kit['weight_kg'] === null): ?>
                <small class="field-hint">Sin peso registrado se usa una meta genérica de 8 vasos. Ingresa tu peso para calcular tu meta real.</small>
            <?php endif; ?>
        </form>

        <p class="kit-water-count">
            💧 <?= e($log['water_count']) ?> / <?= e($waterGoal) ?> vasos de agua hoy
            <?php if ((int) $log['water_count'] >= $waterGoal): ?>
                <span class="badge badge-success">meta cumplida</span>
            <?php endif; ?>
        </p>
        <form method="post" action="/mi-kit/agua" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button type="submit" class="button button-primary button-sm">+ Registrar un vaso de agua</button>
        </form>
        <p class="field-hint">Recordatorio de electrolitos: agua, limón, una pizca de sal y una cucharadita de miel.</p>
    </div>

    <form method="post" action="/mi-kit/movimiento" class="kit-movement-form">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="field-row">
            <div class="field">
                <label for="steps">Pasos de hoy (meta: <?= e($stepsGoal) ?>)</label>
                <input type="number" id="steps" name="steps" min="0" max="200000" value="<?= e((string) ($log['steps'] ?? '')) ?>">
            </div>
            <div class="field">
                <label for="exercise_type">Tipo de ejercicio</label>
                <select id="exercise_type" name="exercise_type">
                    <option value="">— Ninguno —</option>
                    <option value="cardio" <?= ($log['exercise_type'] ?? '') === 'cardio' ? 'selected' : '' ?>>Cardio</option>
                    <option value="fuerza" <?= ($log['exercise_type'] ?? '') === 'fuerza' ? 'selected' : '' ?>>Fuerza</option>
                    <option value="ambos" <?= ($log['exercise_type'] ?? '') === 'ambos' ? 'selected' : '' ?>>Ambos</option>
                </select>
            </div>
        </div>
        <div class="field field-checkbox">
            <label>
                <input type="checkbox" name="exercise_done" value="1" <?= $log['exercise_done'] ? 'checked' : '' ?>>
                Hice ejercicio hoy
            </label>
        </div>
        <button type="submit" class="button button-primary button-sm">Guardar movimiento</button>
    </form>
</article>

<article class="section-card">
    <h2>Diario / encuesta de avance</h2>
    <p class="muted">Un registro corto de cómo te sientes hoy.</p>
    <a class="button button-primary" href="/mi-kit/diario">
        <?= $log['diary'] !== null ? 'Editar diario de hoy' : 'Llenar diario de hoy' ?>
    </a>
</article>

<article class="section-card">
    <h2>Puntos e insignias</h2>
    <p>
        Días cumplidos (parche + hidratación + pasos): <strong><?= e($badge['completedDays']) ?>/7</strong>
        &nbsp;·&nbsp;
        Días con ejercicio: <strong><?= e($badge['exerciseDays']) ?></strong>
    </p>
    <?php if ($badgeName !== null): ?>
        <p><span class="badge badge-gold">Insignia actual: <?= e($badgeName) ?></span></p>
    <?php else: ?>
        <p class="muted">Cumple al menos 4 de los 7 días (parche + hidratación + pasos) para tu primera insignia (Silver).</p>
    <?php endif; ?>
</article>

<article class="section-card kit-notice">
    <h2>Aviso de bienestar</h2>
    <p><?= e_nl2br($notice) ?></p>
</article>
