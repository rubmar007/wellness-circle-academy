<?php
declare(strict_types=1);
/**
 * @var array<int, array<string,mixed>>      $programs
 * @var array{type:string,msg:string}|null   $flash
 * @var string $csrf
 */
$pageTitle = 'Programas';
?>
<section class="page-head">
    <p class="breadcrumb"><a href="/admin">Admin</a> &rsaquo; <span>Programas</span></p>
    <div class="page-head-row">
        <h1>Programas</h1>
        <a class="button button-primary" href="/admin/programas/nuevo">Nuevo programa</a>
    </div>
</section>

<?php if ($flash !== null): ?>
    <p class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></p>
<?php endif; ?>

<?php if ($programs === []): ?>
    <p class="empty-state">No hay programas todavía. Crea el primero con "Nuevo programa".</p>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Título</th>
                    <th>Slug</th>
                    <th>Lecciones</th>
                    <th>Estado</th>
                    <th class="ta-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($programs as $p): ?>
                    <tr>
                        <td><?= e($p['display_order']) ?></td>
                        <td><?= e($p['title']) ?></td>
                        <td><code><?= e($p['slug']) ?></code></td>
                        <td><?= e($p['lesson_count']) ?></td>
                        <td>
                            <?= $p['is_published']
                                ? '<span class="badge badge-success">publicado</span>'
                                : '<span class="badge badge-muted">borrador</span>' ?>
                        </td>
                        <td class="ta-right">
                            <a class="button button-ghost button-sm" href="/admin/programas/<?= e($p['id']) ?>/lecciones">Lecciones</a>
                            <a class="button button-ghost button-sm" href="/admin/programas/<?= e($p['id']) ?>/editar">Editar</a>
                            <a class="button button-ghost button-sm button-danger" href="/admin/programas/<?= e($p['id']) ?>/eliminar">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
