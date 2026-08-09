<?php
declare(strict_types=1);
/**
 * @var array<string,mixed>  $notif
 * @var string $csrf
 */
$pageTitle = 'Eliminar notificación';
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/notificaciones">Notificaciones</a> &rsaquo;
        <span>Eliminar</span>
    </p>
    <h1>Eliminar notificación</h1>
</section>

<div class="confirm-box">
    <p>Vas a eliminar por completo la notificación <strong><?= e($notif['title']) ?></strong>.</p>
    <?php if ($notif['status'] === 'pending'): ?>
        <p>Está pendiente de envío — al eliminarla, ya no se mandará.</p>
    <?php endif; ?>
    <p><strong>Esta acción no se puede deshacer.</strong></p>

    <form method="post" action="/admin/notificaciones/<?= e($notif['id']) ?>/eliminar" class="confirm-form">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="form-actions">
            <a class="button button-ghost" href="/admin/notificaciones">Cancelar</a>
            <button type="submit" class="button button-danger">Sí, eliminar</button>
        </div>
    </form>
</div>
