<?php
declare(strict_types=1);
/**
 * @var string $mode
 * @var array|null $training
 * @var array<string,string> $categories
 * @var array<string,string> $errors
 * @var array<string,string> $old
 * @var string $csrf
 */
$isCreate  = $mode === 'create';
$pageTitle = $isCreate ? 'Nuevo video' : 'Editar video';
$action    = $isCreate ? '/admin/entrenamiento' : '/admin/entrenamiento/' . (int) $training['id'];
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/entrenamiento">Entrenamiento</a> &rsaquo;
        <span><?= e($pageTitle) ?></span>
    </p>
    <h1><?= e($pageTitle) ?></h1>
</section>

<form method="post" action="<?= e($action) ?>" class="admin-form" novalidate>
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <div class="field">
        <label for="category">Tema</label>
        <select id="category" name="category" required>
            <option value="">— Selecciona un tema —</option>
            <?php foreach ($categories as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= ($old['category'] ?? '') === $key ? 'selected' : '' ?>>
                    <?= e($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['category'])): ?>
            <small class="field-error"><?= e($errors['category']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="title">Título</label>
        <input
            type="text"
            id="title"
            name="title"
            value="<?= e($old['title'] ?? '') ?>"
            required
            maxlength="200">
        <?php if (!empty($errors['title'])): ?>
            <small class="field-error"><?= e($errors['title']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="video_url">URL del video</label>
        <input
            type="url"
            id="video_url"
            name="video_url"
            value="<?= e($old['video_url'] ?? '') ?>"
            required
            maxlength="500"
            spellcheck="false">
        <small class="field-hint">YouTube o Vimeo.</small>
        <?php if (!empty($errors['video_url'])): ?>
            <small class="field-error"><?= e($errors['video_url']) ?></small>
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
        <a class="button button-ghost" href="/admin/entrenamiento">Cancelar</a>
        <button type="submit" class="button button-primary">
            <?= $isCreate ? 'Crear video' : 'Guardar cambios' ?>
        </button>
    </div>
</form>
