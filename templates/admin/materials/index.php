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

// Orden de los botones de filtro: Todos, Imágenes, PDFs, Enlaces (pedido por Rub).
$filterOrder = ['image' => 'Imágenes', 'pdf' => 'PDFs', 'link' => 'Enlaces'];
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
    <div class="mat-filter-wrap">
        <input type="radio" name="amf" id="amf-todos" checked hidden>
        <?php foreach ($filterOrder as $value => $label): ?>
            <input type="radio" name="amf" id="amf-<?= e($value) ?>" hidden>
        <?php endforeach; ?>

        <div class="mat-filters">
            <label class="mat-filter-label" for="amf-todos">Todos</label>
            <?php foreach ($filterOrder as $value => $label): ?>
                <label class="mat-filter-label" for="amf-<?= e($value) ?>"><?= e($label) ?></label>
            <?php endforeach; ?>
        </div>

        <div class="table-wrap admin-mat-table-wrap">
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
                        <tr class="admin-mat-row admin-mat-row-<?= e($m['type']) ?>">
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
    </div>
<?php endif; ?>
