<?php
declare(strict_types=1);
/**
 * @var array<string,mixed> $page
 * @var array<string,string> $errors
 * @var array{type:string,msg:string}|null $flash
 * @var array<string,string> $old
 * @var string $csrf
 */
$pageTitle = 'Normas y Reglamentos';
?>
<section class="page-head">
    <p class="breadcrumb"><a href="/admin">Admin</a> &rsaquo; <span>Normas</span></p>
    <h1>Normas y Reglamentos</h1>
</section>

<?php if ($flash !== null): ?>
    <p class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></p>
<?php endif; ?>

<form method="post" action="/admin/normas" class="admin-form" novalidate>
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <div class="field">
        <label for="title">Título</label>
        <input
            type="text"
            id="title"
            name="title"
            value="<?= e($old['title'] ?? '') ?>"
            required
            maxlength="160">
        <?php if (!empty($errors['title'])): ?>
            <small class="field-error"><?= e($errors['title']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="body">Contenido</label>
        <textarea id="body" name="body" rows="20" maxlength="8000"><?= e($old['body'] ?? '') ?></textarea>
        <small class="field-hint">Texto de las normas y reglamentos. Máximo 8000 caracteres.</small>
        <?php if (!empty($errors['body'])): ?>
            <small class="field-error"><?= e($errors['body']) ?></small>
        <?php endif; ?>
    </div>

    <div class="form-actions">
        <a class="button button-ghost" href="/normas">Cancelar</a>
        <button type="submit" class="button button-primary">Guardar cambios</button>
    </div>
</form>
