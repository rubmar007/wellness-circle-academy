<?php
declare(strict_types=1);
/**
 * @var array<string,mixed> $program
 * @var array<string,mixed> $lesson
 * @var string $csrf
 */
$pageTitle = 'Eliminar lección';
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/programas">Programas</a> &rsaquo;
        <a href="/admin/programas/<?= e($program['id']) ?>/lecciones"><?= e($program['title']) ?></a> &rsaquo;
        <span>Eliminar</span>
    </p>
    <h1>Eliminar lección</h1>
</section>

<div class="confirm-box">
    <p>
        Vas a eliminar la lección
        <strong>"Día <?= e($lesson['day_number']) ?> — <?= e($lesson['title']) ?>"</strong>
        del programa <strong><?= e($program['title']) ?></strong>.
    </p>
    <p class="alert alert-error">
        Esto también elimina el progreso registrado por los miembros sobre esta lección.
    </p>
    <p><strong>Esta acción no se puede deshacer.</strong></p>

    <form method="post" action="/admin/lecciones/<?= e($lesson['id']) ?>/eliminar" class="confirm-form">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="form-actions">
            <a class="button button-ghost" href="/admin/programas/<?= e($program['id']) ?>/lecciones">Cancelar</a>
            <button type="submit" class="button button-danger">Sí, eliminar</button>
        </div>
    </form>
</div>
