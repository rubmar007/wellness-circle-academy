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
    <p class="muted">Resumen y accesos rápidos.</p>
</section>

<section class="stats-row">
    <a class="stat-card stat-card-link" href="/admin/usuarios">
        <span class="stat-card-value"><?= e($totals['users']) ?></span>
        <span class="stat-card-label">usuarios</span>
    </a>
    <a class="stat-card stat-card-link" href="/admin/programas">
        <span class="stat-card-value"><?= e($totals['programs']) ?></span>
        <span class="stat-card-label">programas</span>
    </a>
    <a class="stat-card stat-card-link" href="/admin/programas">
        <span class="stat-card-value"><?= e($totals['lessons']) ?></span>
        <span class="stat-card-label">lecciones</span>
    </a>
</section>

<section class="admin-shortcuts">
    <a class="admin-shortcut" href="/admin/usuarios">
        <h2>Usuarios</h2>
        <p>Crear, editar y activar/desactivar cuentas de miembros y administradores.</p>
    </a>
    <a class="admin-shortcut" href="/admin/programas">
        <h2>Programas y lecciones</h2>
        <p>Crear y editar programas, gestionar sus lecciones, subir imágenes y publicar contenido.</p>
    </a>
</section>

<?php if ($programs !== []): ?>
    <section>
        <h2>Programas existentes</h2>
        <ul class="admin-list">
            <?php foreach ($programs as $program): ?>
                <li class="admin-list-item">
                    <span class="admin-list-title"><?= e($program['title']) ?></span>
                    <span class="admin-list-meta">
                        slug: <code><?= e($program['slug']) ?></code>
                        ·
                        <?= $program['is_published'] ? 'publicado' : 'borrador' ?>
                    </span>
                    <a class="button button-ghost button-sm" href="/admin/programas/<?= e($program['id']) ?>/editar">Editar</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
