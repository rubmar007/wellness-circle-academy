<?php
declare(strict_types=1);
/**
 * @var string                    $mode   'create' | 'edit'
 * @var array<string,mixed>|null  $promo
 * @var array<string,string>      $errors
 * @var array<string,string>      $old
 * @var string $csrf
 */
$isCreate  = $mode === 'create';
$pageTitle = $isCreate ? 'Nueva promoción' : 'Editar promoción';
$action    = $isCreate ? '/admin/promociones' : '/admin/promociones/' . (int) $promo['id'];
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/promociones">Promociones</a> &rsaquo;
        <span><?= e($pageTitle) ?></span>
    </p>
    <h1><?= e($pageTitle) ?></h1>
</section>

<form method="post" action="<?= e($action) ?>" class="admin-form" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <div class="field">
        <label for="title">Título <span class="req">*</span></label>
        <input type="text" id="title" name="title"
               value="<?= e($old['title'] ?? '') ?>" required maxlength="200">
        <?php if (!empty($errors['title'])): ?>
            <small class="field-error"><?= e($errors['title']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="body">Descripción</label>
        <textarea id="body" name="body" rows="5" maxlength="4000"><?= e($old['body'] ?? '') ?></textarea>
        <small class="field-hint">Opcional. Máximo 4000 caracteres.</small>
        <?php if (!empty($errors['body'])): ?>
            <small class="field-error"><?= e($errors['body']) ?></small>
        <?php endif; ?>
    </div>

    <fieldset class="field-group">
        <legend>Medios (opcional — puedes usar uno o varios)</legend>

        <div class="field">
            <label for="image">Imagen</label>
            <?php if (!empty($promo['image_path'])): ?>
                <div class="current-image-preview">
                    <img src="<?= e($promo['image_path']) ?>" alt="Imagen actual" style="max-height:120px; border-radius:6px;">
                    <label class="field-checkbox-inline">
                        <input type="checkbox" name="remove_image" value="1"> Eliminar imagen actual
                    </label>
                </div>
            <?php endif; ?>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
            <small class="field-hint">JPG, PNG o WebP. Máx. 5 MB.</small>
            <?php if (!empty($errors['image'])): ?>
                <small class="field-error"><?= e($errors['image']) ?></small>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="video_url">URL de video</label>
            <input type="url" id="video_url" name="video_url"
                   value="<?= e($old['video_url'] ?? '') ?>"
                   placeholder="https://www.youtube.com/watch?v=...">
            <small class="field-hint">YouTube o Vimeo. Se mostrará como reproductor integrado.</small>
            <?php if (!empty($errors['video_url'])): ?>
                <small class="field-error"><?= e($errors['video_url']) ?></small>
            <?php endif; ?>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="link_url">URL del enlace (botón)</label>
                <input type="url" id="link_url" name="link_url"
                       value="<?= e($old['link_url'] ?? '') ?>"
                       placeholder="https://...">
                <?php if (!empty($errors['link_url'])): ?>
                    <small class="field-error"><?= e($errors['link_url']) ?></small>
                <?php endif; ?>
            </div>
            <div class="field">
                <label for="link_label">Texto del botón</label>
                <input type="text" id="link_label" name="link_label"
                       value="<?= e($old['link_label'] ?? '') ?>"
                       placeholder="Ver más" maxlength="100">
                <small class="field-hint">Opcional. Default: "Ver más".</small>
                <?php if (!empty($errors['link_label'])): ?>
                    <small class="field-error"><?= e($errors['link_label']) ?></small>
                <?php endif; ?>
            </div>
        </div>
    </fieldset>

    <div class="field field-checkbox">
        <label>
            <input type="checkbox" name="is_published" value="1"
                   <?= ($old['is_published'] ?? '') === '1' ? 'checked' : '' ?>>
            Publicado (visible para miembros)
        </label>
    </div>

    <div class="form-actions">
        <a class="button button-ghost" href="/admin/promociones">Cancelar</a>
        <button type="submit" class="button button-primary">
            <?= $isCreate ? 'Crear promoción' : 'Guardar cambios' ?>
        </button>
    </div>
</form>
