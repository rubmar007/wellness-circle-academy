<?php
declare(strict_types=1);
/**
 * @var array<int, array<string,mixed>> $programs
 * @var array<string,mixed> $auth
 */
$pageTitle = 'Dashboard';
?>
<div class="dashboard-logo-wrap">
    <img class="dashboard-logo" src="/assets/img/logo.png" alt="Wellness Circle Academy">
</div>

<section class="page-head">
    <h1>Hola, <?= e($auth['name']) ?></h1>
    <p class="muted">Elige un programa para entrar a tu rutina del día.</p>
</section>

<?php if ($programs === []): ?>
    <p class="empty-state">Todavía no hay programas publicados. Vuelve pronto.</p>
<?php else: ?>
    <div class="program-grid">
        <?php foreach ($programs as $program): ?>
            <a class="program-card" href="/programas/<?= e($program['slug']) ?>">
                <?php if (!empty($program['cover_image'])): ?>
                    <img
                        class="program-card-cover"
                        src="<?= e($program['cover_image']) ?>"
                        alt="<?= e($program['title']) ?>"
                        loading="lazy">
                <?php else: ?>
                    <span class="program-card-cover program-card-cover-fallback" aria-hidden="true"></span>
                <?php endif; ?>
                <span class="program-card-body">
                    <span class="program-card-title"><?= e($program['title']) ?></span>
                    <?php if (!empty($program['presentation'])): ?>
                        <span class="program-card-desc"><?= e($program['presentation']) ?></span>
                    <?php endif; ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
