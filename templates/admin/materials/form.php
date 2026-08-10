<?php
declare(strict_types=1);
/**
 * @var string $mode
 * @var array|null $material
 * @var array<string,string> $errors
 * @var array<string,string> $old
 * @var string $csrf
 */
use App\Controllers\AdminMaterialsController;

$isCreate  = $mode === 'create';
$pageTitle = $isCreate ? 'Nuevo material' : 'Editar material';
$action    = $isCreate ? '/admin/materiales' : '/admin/materiales/' . (int) $material['id'];
$types     = AdminMaterialsController::TYPES;
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/materiales">Materiales</a> &rsaquo;
        <span><?= e($pageTitle) ?></span>
    </p>
    <h1><?= e($pageTitle) ?></h1>
</section>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="admin-form" novalidate>
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <div class="field">
        <label for="type">Tipo</label>
        <select id="type" name="type" required>
            <?php foreach ($types as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= ($old['type'] ?? '') === $value ? 'selected' : '' ?>>
                    <?= e($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['type'])): ?>
            <small class="field-error"><?= e($errors['type']) ?></small>
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
        <label for="url">URL</label>
        <input
            type="url"
            id="url"
            name="url"
            value="<?= e($old['url'] ?? '') ?>"
            maxlength="500"
            autocapitalize="none"
            spellcheck="false">
        <small class="field-hint">Para PDF: link de Google Drive. Para Enlace: cualquier URL https. Para Imagen: deja vacío y sube archivo.</small>
        <?php if (!empty($errors['url'])): ?>
            <small class="field-error"><?= e($errors['url']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="image">Imagen</label>
        <?php if (!$isCreate && !empty($material['image_url'])): ?>
            <p class="form-image-current">
                <img src="<?= e($material['image_url']) ?>" alt="" loading="lazy">
                <span class="muted small">Actual. Sube otra para reemplazarla.</span>
            </p>
        <?php endif; ?>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
        <small class="field-hint">Solo para tipo Imagen. JPG, PNG o WebP. Máximo 5 MB.</small>
        <?php if (!empty($errors['image'])): ?>
            <small class="field-error"><?= e($errors['image']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="description">Descripción breve</label>
        <textarea
            id="description"
            name="description"
            rows="4"
            maxlength="500"><?= e($old['description'] ?? '') ?></textarea>
        <small class="field-hint">Máximo 500 caracteres. Solo se muestra (con botón de copiar) debajo de materiales tipo Imagen en la vista de Materiales.</small>
        <?php if (!empty($errors['description'])): ?>
            <small class="field-error"><?= e($errors['description']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="folder">Carpeta</label>
        <input
            type="text"
            id="folder"
            name="folder"
            value="<?= e($old['folder'] ?? '') ?>"
            maxlength="100"
            placeholder="Ej: X39, Arranque, Para clientes…">
        <small class="field-hint">Opcional. Agrupa este material bajo una carpeta en la vista de miembros.</small>
        <?php if (!empty($errors['folder'])): ?>
            <small class="field-error"><?= e($errors['folder']) ?></small>
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
        <a class="button button-ghost" href="/admin/materiales">Cancelar</a>
        <button type="submit" class="button button-primary">
            <?= $isCreate ? 'Crear material' : 'Guardar cambios' ?>
        </button>
    </div>
</form>
