<?php
declare(strict_types=1);
/**
 * @var array<string,mixed> $material
 * @var string $csrf
 */
use App\Controllers\AdminMaterialsController;

$pageTitle = 'Eliminar material';
$types     = AdminMaterialsController::TYPES;
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/materiales">Materiales</a> &rsaquo;
        <span>Eliminar</span>
    </p>
    <h1>Eliminar material</h1>
</section>

<div class="confirm-box">
    <p>
        Vas a eliminar el material <strong>"<?= e($material['title']) ?>"</strong>
        (tipo <?= e($types[$material['type']] ?? $material['type']) ?>).
    </p>
    <?php if ($material['type'] === 'image'): ?>
        <p class="muted">También se borrará la imagen subida asociada.</p>
    <?php endif; ?>
    <p><strong>Esta acción no se puede deshacer.</strong></p>

    <form method="post" action="/admin/materiales/<?= e($material['id']) ?>/eliminar" class="confirm-form">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="form-actions">
            <a class="button button-ghost" href="/admin/materiales">Cancelar</a>
            <button type="submit" class="button button-danger">Sí, eliminar</button>
        </div>
    </form>
</div>
