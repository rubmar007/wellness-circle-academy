<?php
declare(strict_types=1);
/**
 * @var array<string,mixed> $promo
 * @var string $csrf
 */
$pageTitle = 'Eliminar promoción';
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/promociones">Promociones</a> &rsaquo;
        <span>Eliminar</span>
    </p>
    <h1>Eliminar promoción</h1>
</section>

<div class="confirm-box">
    <p>Vas a eliminar la promoción <strong>"<?= e($promo['title']) ?>"</strong>.</p>
    <?php if (!empty($promo['image_path'])): ?>
        <p class="muted small">La imagen adjunta también será eliminada.</p>
    <?php endif; ?>
    <p><strong>Esta acción no se puede deshacer.</strong></p>

    <form method="post" action="/admin/promociones/<?= e($promo['id']) ?>/eliminar" class="confirm-form">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="form-actions">
            <a class="button button-ghost" href="/admin/promociones">Cancelar</a>
            <button type="submit" class="button button-danger">Sí, eliminar</button>
        </div>
    </form>
</div>
