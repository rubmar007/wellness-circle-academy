<?php
declare(strict_types=1);
/**
 * @var array<int, array<string,mixed>>    $materials
 * @var array{type:string,msg:string}|null $flash
 * @var string $csrf
 */
use App\Controllers\AdminMaterialsController;

$pageTitle = 'Materiales';
$types     = AdminMaterialsController::TYPES;
?>
<section class="page-head">
    <p class="breadcrumb"><a href="/admin">Admin</a> &rsaquo; <span>Materiales</span></p>
    <div class="page-head-row">
        <h1>Materiales</h1>
        <a class="button button-primary" href="/admin/materiales/nuevo">Nuevo material</a>
    </div>
</section>

<?php if ($flash !== null): ?>
    <p class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></p>
<?php endif; ?>

<?php if ($materials === []): ?>
    <p class="empty-state">No hay materiales todavía. Crea el primero con "Nuevo material".</p>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Título</th>
                    <th>Orden</th>
                    <th>Estado</th>
                    <th class="ta-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materials as $m): ?>
                    <tr>
                        <td><?= e($types[$m['type']] ?? $m['type']) ?></td>
                        <td><?= e($m['title']) ?></td>
                        <td><?= e($m['display_order']) ?></td>
                        <td>
                            <?= $m['is_published']
                                ? '<span class="badge badge-success">publicado</span>'
                                : '<span class="badge badge-muted">borrador</span>' ?>
                        </td>
                        <td class="ta-right">
                            <div class="table-actions">
                                <a class="button button-ghost button-sm" href="/admin/materiales/<?= e($m['id']) ?>/editar">Editar</a>
                                <a class="button button-ghost button-sm button-danger" href="/admin/materiales/<?= e($m['id']) ?>/eliminar">Eliminar</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
