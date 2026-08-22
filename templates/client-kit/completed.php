<?php
declare(strict_types=1);

use App\Support\ExperienceKitData;

/**
 * @var string $kitLabel
 * @var array{badge:string|null,completedDays:int,exerciseDays:int} $badge
 */
$pageTitle = 'Mi Kit';

$badgeLabels = ExperienceKitData::badgeLabels();
$badgeName   = $badge['badge'] !== null ? ($badgeLabels[$badge['badge']] ?? $badge['badge']) : null;
?>
<section class="page-head">
    <h1>WCA Experience Kit — <?= e($kitLabel) ?></h1>
</section>

<article class="section-card kit-completed">
    <h2>¡Felicidades, completaste tus 7 días!</h2>

    <?php if ($badgeName !== null): ?>
        <p><span class="badge badge-gold">Insignia obtenida: <?= e($badgeName) ?></span></p>
    <?php endif; ?>

    <p><?= e(ExperienceKitData::completionMessage($badge['badge'])) ?></p>

    <p class="muted">
        Días cumplidos (parche + hidratación + pasos): <strong><?= e($badge['completedDays']) ?>/7</strong>
        &nbsp;·&nbsp;
        Días con ejercicio: <strong><?= e($badge['exerciseDays']) ?></strong>
    </p>
</article>
