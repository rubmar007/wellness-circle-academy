<?php
declare(strict_types=1);
/**
 * @var array<string,mixed> $noticia
 * @var string $csrf
 */
$pageTitle = 'Eliminar noticia';
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/noticias">Noticias</a> &rsaquo;
        <span>Eliminar</span>
    </p>
    <h1>Eliminar noticia</h1>
</section>

<div class="confirm-box">
    <p>Vas a eliminar la noticia <strong>"<?= e($noticia['title']) ?>"</strong>.</p>
    <?php if (!empty($noticia['image_path'])): ?>
        <p class="muted small">La imagen adjunta también será eliminada.</p>
    <?php endif; ?>
    <p><strong>Esta acción no se puede deshacer.</strong></p>

    <form method="post" action="/admin/noticias/<?= e($noticia['id']) ?>/eliminar" class="confirm-form">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="form-actions">
            <a class="button button-ghost" href="/admin/noticias">Cancelar</a>
            <button type="submit" class="button button-danger">Sí, eliminar</button>
        </div>
    </form>
</div>
