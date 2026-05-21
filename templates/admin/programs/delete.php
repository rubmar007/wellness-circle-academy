<?php
declare(strict_types=1);
/**
 * @var array<string,mixed> $program
 * @var int $lesson_count
 * @var string $csrf
 */
$pageTitle = 'Eliminar programa';
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/programas">Programas</a> &rsaquo;
        <span>Eliminar</span>
    </p>
    <h1>Eliminar programa</h1>
</section>

<div class="confirm-box">
    <p>
        Vas a eliminar el programa <strong>"<?= e($program['title']) ?>"</strong>
        (slug <code><?= e($program['slug']) ?></code>).
    </p>
    <?php if ($lesson_count > 0): ?>
        <p class="alert alert-error">
            Esto también eliminará sus <?= e($lesson_count) ?>
            <?= $lesson_count === 1 ? 'lección' : 'lecciones' ?>
            y todos los registros de progreso de los miembros sobre ellas.
        </p>
    <?php else: ?>
        <p class="muted">Este programa no tiene lecciones todavía.</p>
    <?php endif; ?>
    <p><strong>Esta acción no se puede deshacer.</strong></p>

    <form method="post" action="/admin/programas/<?= e($program['id']) ?>/eliminar" class="confirm-form">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="form-actions">
            <a class="button button-ghost" href="/admin/programas">Cancelar</a>
            <button type="submit" class="button button-danger">Sí, eliminar</button>
        </div>
    </form>
</div>
