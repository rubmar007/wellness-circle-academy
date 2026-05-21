<?php
declare(strict_types=1);
/**
 * @var array<string,mixed> $program
 * @var array<int, array<string,mixed>> $lessons
 * @var array{type:string,msg:string}|null $flash
 * @var string $csrf
 */
$pageTitle = 'Lecciones — ' . (string) $program['title'];
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/programas">Programas</a> &rsaquo;
        <span><?= e($program['title']) ?></span> &rsaquo;
        <span>Lecciones</span>
    </p>
    <div class="page-head-row">
        <h1>Lecciones — <?= e($program['title']) ?></h1>
        <div class="page-head-actions">
            <a class="button button-ghost" href="/admin/programas/<?= e($program['id']) ?>/batch">Cargar batch (XLSX)</a>
            <a class="button button-primary" href="/admin/programas/<?= e($program['id']) ?>/lecciones/nueva">Nueva lección</a>
        </div>
    </div>
</section>

<?php if ($flash !== null): ?>
    <p class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></p>
<?php endif; ?>

<?php if ($lessons === []): ?>
    <p class="empty-state">No hay lecciones en este programa. Crea la primera.</p>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Día</th>
                    <th>Título</th>
                    <th>Estado</th>
                    <th class="ta-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lessons as $l): ?>
                    <tr>
                        <td><?= e($l['day_number']) ?></td>
                        <td><?= e($l['title']) ?></td>
                        <td>
                            <?= $l['is_published']
                                ? '<span class="badge badge-success">publicada</span>'
                                : '<span class="badge badge-muted">borrador</span>' ?>
                        </td>
                        <td class="ta-right">
                            <a class="button button-ghost button-sm" href="/admin/lecciones/<?= e($l['id']) ?>/editar">Editar</a>
                            <a class="button button-ghost button-sm button-danger" href="/admin/lecciones/<?= e($l['id']) ?>/eliminar">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
