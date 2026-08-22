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
        <img class="kit-badge-img" src="<?= e(ExperienceKitData::badgeImagePath($badge['badge'])) ?>" alt="Insignia <?= e($badgeName) ?>">
    <?php endif; ?>

    <p><?= e(ExperienceKitData::completionMessage($badge['badge'])) ?></p>

    <p class="muted">
        Días cumplidos (parche + hidratación + diario): <strong><?= e($badge['completedDays']) ?>/7</strong>
        &nbsp;·&nbsp;
        Días con ejercicio: <strong><?= e($badge['exerciseDays']) ?></strong>
    </p>
</article>
