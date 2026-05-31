<?php
declare(strict_types=1);
/**
 * @var array<string,mixed>   $training
 * @var array<string,string>  $categories
 * @var string $csrf
 */
$pageTitle = 'Eliminar video';
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/entrenamiento">Entrenamiento</a> &rsaquo;
        <span>Eliminar</span>
    </p>
    <h1>Eliminar video</h1>
</section>

<div class="confirm-box">
    <p>
        Vas a eliminar el video <strong>"<?= e($training['title']) ?>"</strong>
        del tema <strong><?= e($categories[$training['category']] ?? $training['category']) ?></strong>.
    </p>
    <p><strong>Esta acción no se puede deshacer.</strong></p>

    <form method="post" action="/admin/entrenamiento/<?= e($training['id']) ?>/eliminar" class="confirm-form">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="form-actions">
            <a class="button button-ghost" href="/admin/entrenamiento">Cancelar</a>
            <button type="submit" class="button button-danger">Sí, eliminar</button>
        </div>
    </form>
</div>
