<?php
declare(strict_types=1);
/**
 * @var array<int, array<string,mixed>>      $trainings
 * @var array<string,string>                 $categories
 * @var array{type:string,msg:string}|null   $flash
 * @var string $csrf
 */
$pageTitle = 'Entrenamiento';
?>
<section class="page-head">
    <p class="breadcrumb"><a href="/admin">Admin</a> &rsaquo; <span>Entrenamiento</span></p>
    <div class="page-head-row">
        <h1>Entrenamiento</h1>
        <a class="button button-primary" href="/admin/entrenamiento/nuevo">Nuevo video</a>
    </div>
</section>

<?php if ($flash !== null): ?>
    <p class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></p>
<?php endif; ?>

<?php if ($trainings === []): ?>
    <p class="empty-state">No hay videos todavía. Crea el primero con "Nuevo video".</p>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Tema</th>
                    <th>Título</th>
                    <th>Orden</th>
                    <th>Estado</th>
                    <th class="ta-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trainings as $t): ?>
                    <tr>
                        <td><?= e($categories[$t['category']] ?? $t['category']) ?></td>
                        <td><?= e($t['title']) ?></td>
                        <td><?= e($t['display_order']) ?></td>
                        <td>
                            <?= $t['is_published']
                                ? '<span class="badge badge-success">publicado</span>'
                                : '<span class="badge badge-muted">borrador</span>' ?>
                        </td>
                        <td class="ta-right">
                            <div class="table-actions">
                                <a class="button button-ghost button-sm" href="/admin/entrenamiento/<?= e($t['id']) ?>/editar">Editar</a>
                                <a class="button button-ghost button-sm button-danger" href="/admin/entrenamiento/<?= e($t['id']) ?>/eliminar">Eliminar</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
