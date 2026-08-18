<?php
declare(strict_types=1);

use App\Controllers\AdminEventsController;

/**
 * @var string $mode
 * @var array<string,mixed>|null $event
 * @var array<string,string> $errors
 * @var array<string,string> $old
 * @var string $csrf
 * @var string|null $duplicateImageUrl
 */
$duplicateImageUrl ??= null;
$isCreate  = $mode === 'create';
$pageTitle = $isCreate ? 'Nuevo evento' : 'Editar evento';
$action    = $isCreate ? '/admin/eventos' : '/admin/eventos/' . (int) $event['id'];
$types     = AdminEventsController::TYPES;
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/eventos">Eventos</a> &rsaquo;
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
            maxlength="200">
        <?php if (!empty($errors['title'])): ?>
            <small class="field-error"><?= e($errors['title']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="event_type">Tipo</label>
        <select id="event_type" name="event_type" required>
            <option value="">— Selecciona —</option>
            <?php foreach ($types as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= ($old['event_type'] ?? '') === $value ? 'selected' : '' ?>>
                    <?= e($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['event_type'])): ?>
            <small class="field-error"><?= e($errors['event_type']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="starts_at">Fecha y hora</label>
        <input
            type="datetime-local"
            id="starts_at"
            name="starts_at"
            value="<?= e($old['starts_at'] ?? '') ?>"
            required>
        <?php if (!empty($errors['starts_at'])): ?>
            <small class="field-error"><?= e($errors['starts_at']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="join_url">Enlace para entrar</label>
        <input
            type="url"
            id="join_url"
            name="join_url"
            value="<?= e($old['join_url'] ?? '') ?>"
            maxlength="500">
        <small class="field-hint">Enlace de Zoom/Meet. Opcional. Debe empezar con https://.</small>
        <?php if (!empty($errors['join_url'])): ?>
            <small class="field-error"><?= e($errors['join_url']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="description">Descripción</label>
        <textarea id="description" name="description" rows="4" maxlength="4000"><?= e($old['description'] ?? '') ?></textarea>
        <?php if (!empty($errors['description'])): ?>
            <small class="field-error"><?= e($errors['description']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="image">Imagen del evento</label>
        <?php if (!$isCreate && !empty($event['image_url'])): ?>
            <p class="form-image-current">
                <img src="<?= e($event['image_url']) ?>" alt="" loading="lazy">
                <span class="muted small">Actual. Sube otra para reemplazarla.</span>
            </p>
        <?php elseif ($isCreate && $duplicateImageUrl !== null): ?>
            <p class="form-image-current">
                <img src="<?= e($duplicateImageUrl) ?>" alt="" loading="lazy">
                <span class="muted small">Copiada del evento original. Sube otra para reemplazarla.</span>
            </p>
            <input type="hidden" name="duplicate_image_url" value="<?= e($duplicateImageUrl) ?>">
        <?php endif; ?>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
        <small class="field-hint">Opcional. Flyer o banner del evento. JPG, PNG o WebP. Máximo 5 MB.</small>
        <?php if (!empty($errors['image'])): ?>
            <small class="field-error"><?= e($errors['image']) ?></small>
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

    <div class="form-actions">
        <a class="button button-ghost" href="/admin/eventos">Cancelar</a>
        <button type="submit" class="button button-primary">
            <?= $isCreate ? 'Crear evento' : 'Guardar cambios' ?>
        </button>
    </div>
</form>
