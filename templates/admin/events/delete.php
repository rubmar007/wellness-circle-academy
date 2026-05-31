<?php
declare(strict_types=1);
/**
 * @var array<string,mixed> $event
 * @var string $csrf
 */
$pageTitle = 'Eliminar evento';
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/eventos">Eventos</a> &rsaquo;
        <span>Eliminar</span>
    </p>
    <h1>Eliminar evento</h1>
</section>

<div class="confirm-box">
    <p>
        Vas a eliminar el evento <strong>"<?= e($event['title']) ?>"</strong>.
    </p>
    <p><strong>Esta acción no se puede deshacer.</strong></p>

    <form method="post" action="/admin/eventos/<?= e($event['id']) ?>/eliminar" class="confirm-form">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="form-actions">
            <a class="button button-ghost" href="/admin/eventos">Cancelar</a>
            <button type="submit" class="button button-danger">Sí, eliminar</button>
        </div>
    </form>
</div>
