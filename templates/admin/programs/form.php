<?php
declare(strict_types=1);
/**
 * @var string $mode
 * @var array|null $program
 * @var array<string,string> $errors
 * @var array<string,string> $old
 * @var string $csrf
 */
$isCreate  = $mode === 'create';
$pageTitle = $isCreate ? 'Nuevo programa' : 'Editar programa';
$action    = $isCreate ? '/admin/programas' : '/admin/programas/' . (int) $program['id'];
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/programas">Programas</a> &rsaquo;
        <span><?= e($pageTitle) ?></span>
    </p>
    <h1><?= e($pageTitle) ?></h1>
</section>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="admin-form" novalidate>
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
        <label for="slug">Slug (URL)</label>
        <input
            type="text"
            id="slug"
            name="slug"
            value="<?= e($old['slug'] ?? '') ?>"
            required
            maxlength="80"
            pattern="[a-z0-9\-]+"
            autocapitalize="none"
            spellcheck="false">
        <small class="field-hint">Solo minúsculas, números y guiones. Ej: <code>arranque</code>, <code>x39</code>.</small>
        <?php if (!empty($errors['slug'])): ?>
            <small class="field-error"><?= e($errors['slug']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="description">Descripción</label>
        <textarea id="description" name="description" rows="3" maxlength="2000"><?= e($old['description'] ?? '') ?></textarea>
        <?php if (!empty($errors['description'])): ?>
            <small class="field-error"><?= e($errors['description']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="cover">Imagen de portada</label>
        <?php if (!$isCreate && !empty($program['cover_image'])): ?>
            <p class="form-image-current">
                <img src="<?= e($program['cover_image']) ?>" alt="" loading="lazy">
                <span class="muted small">Actual. Sube otra para reemplazarla.</span>
            </p>
        <?php endif; ?>
        <input type="file" id="cover" name="cover" accept="image/jpeg,image/png,image/webp">
        <small class="field-hint">JPG, PNG o WebP. Máximo 5 MB.</small>
        <?php if (!empty($errors['cover'])): ?>
            <small class="field-error"><?= e($errors['cover']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field-row">
        <div class="field">
            <label for="display_order">Orden</label>
            <input
                type="number"
                id="display_order"
                name="display_order"
                value="<?= e($old['display_order'] ?? '0') ?>"
                step="1"
                min="-99999"
                max="99999">
            <small class="field-hint">Menor número = aparece primero.</small>
            <?php if (!empty($errors['display_order'])): ?>
                <small class="field-error"><?= e($errors['display_order']) ?></small>
            <?php endif; ?>
        </div>

        <div class="field field-checkbox">
            <label>
                <input
                    type="checkbox"
                    name="is_published"
                    value="1"
                    <?= ($old['is_published'] ?? '') === '1' ? 'checked' : '' ?>>
                Publicado (visible para miembros)
            </label>
        </div>
    </div>

    <div class="form-actions">
        <a class="button button-ghost" href="/admin/programas">Cancelar</a>
        <button type="submit" class="button button-primary">
            <?= $isCreate ? 'Crear programa' : 'Guardar cambios' ?>
        </button>
    </div>
</form>
