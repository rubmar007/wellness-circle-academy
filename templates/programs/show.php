<?php
declare(strict_types=1);
/**
 * @var array<string,mixed> $program
 * @var array<int, array<string,mixed>> $lessons
 */
$pageTitle = (string) $program['title'];
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/dashboard">Dashboard</a> &rsaquo; <span><?= e($program['title']) ?></span>
    </p>
    <h1><?= e($program['title']) ?></h1>
    <?php if (!empty($program['description'])): ?>
        <p class="muted"><?= e($program['description']) ?></p>
    <?php endif; ?>
</section>

<?php if ($lessons === []): ?>
    <p class="empty-state">Este programa aún no tiene días publicados.</p>
<?php else: ?>
    <ol class="lesson-list">
        <?php foreach ($lessons as $lesson): ?>
            <li class="lesson-list-item">
                <a class="lesson-link" href="/programas/<?= e($program['slug']) ?>/dia/<?= e($lesson['day_number']) ?>">
                    <span class="lesson-day">Día <?= e($lesson['day_number']) ?></span>
                    <span class="lesson-title"><?= e($lesson['title']) ?></span>
                    <?php if (!empty($lesson['objective'])): ?>
                        <span class="lesson-objective"><?= e($lesson['objective']) ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>
