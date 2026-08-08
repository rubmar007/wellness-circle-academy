<?php
declare(strict_types=1);
/**
 * @var array<string,mixed>  $kit
 * @var string               $kitLabel
 * @var string $csrf
 */
$pageTitle = 'Eliminar kit';
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/experience-kit">WCA Experience Kit</a> &rsaquo;
        <span>Eliminar</span>
    </p>
    <h1>Eliminar kit</h1>
</section>

<div class="confirm-box">
    <p>
        Vas a eliminar por completo el kit <strong><?= e($kitLabel) ?></strong> de
        <strong><?= e($kit['name']) ?></strong> (<?= e($kit['email']) ?>).
    </p>
    <p>Esto borra también toda su bitácora diaria (checklist, agua, movimiento, diario/encuesta) de esos días.</p>
    <p><strong>Esta acción no se puede deshacer.</strong> Si solo terminó su kit normalmente, usa «Finalizar» en vez de esto — conserva el historial.</p>

    <form method="post" action="/admin/experience-kit/<?= e($kit['id']) ?>/eliminar" class="confirm-form">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="form-actions">
            <a class="button button-ghost" href="/admin/experience-kit">Cancelar</a>
            <button type="submit" class="button button-danger">Sí, eliminar todo</button>
        </div>
    </form>
</div>
