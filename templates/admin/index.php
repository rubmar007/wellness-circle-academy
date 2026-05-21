<?php
declare(strict_types=1);
/**
 * @var array{users:int, programs:int, lessons:int} $totals
 * @var array<int, array<string,mixed>>             $programs
 */
$pageTitle = 'Panel admin';
?>
<section class="page-head">
    <h1>Panel de administración</h1>
    <p class="muted">Resumen y accesos rápidos. La gestión completa se añadirá en la próxima fase.</p>
</section>

<section class="stats-row">
    <div class="stat-card">
        <span class="stat-card-value"><?= e($totals['users']) ?></span>
        <span class="stat-card-label">usuarios</span>
    </div>
    <div class="stat-card">
        <span class="stat-card-value"><?= e($totals['programs']) ?></span>
        <span class="stat-card-label">programas</span>
    </div>
    <div class="stat-card">
        <span class="stat-card-value"><?= e($totals['lessons']) ?></span>
        <span class="stat-card-label">lecciones</span>
    </div>
</section>

<section>
    <h2>Programas</h2>

    <?php if ($programs === []): ?>
        <p class="empty-state">No hay programas creados todavía.</p>
    <?php else: ?>
        <ul class="admin-list">
            <?php foreach ($programs as $program): ?>
                <li class="admin-list-item">
                    <span class="admin-list-title"><?= e($program['title']) ?></span>
                    <span class="admin-list-meta">
                        slug: <code><?= e($program['slug']) ?></code>
                        ·
                        <?= $program['is_published'] ? 'publicado' : 'borrador' ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<p class="muted small">
    La creación/edición de programas y lecciones desde la UI se implementará en la siguiente fase.
    Por ahora, los administradores pueden cargar contenido directamente en la base de datos.
</p>
