<?php
declare(strict_types=1);
/**
 * @var array<int, array<string,mixed>> $programs
 * @var array<string,mixed> $auth
 */
$pageTitle = 'Dashboard';
?>
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
                    <span
                        class="program-card-cover"
                        role="img"
                        aria-label="<?= e($program['title']) ?>"
                        style="background-image:url('<?= e($program['cover_image']) ?>')"></span>
                <?php else: ?>
                    <span class="program-card-cover program-card-cover-fallback" aria-hidden="true"></span>
                <?php endif; ?>
                <span class="program-card-body">
                    <span class="program-card-title"><?= e($program['title']) ?></span>
                    <?php if (!empty($program['description'])): ?>
                        <span class="program-card-desc"><?= e($program['description']) ?></span>
                    <?php endif; ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
