<?php
declare(strict_types=1);
/**
 * @var string $mode
 * @var array<string,mixed>|null $announcement
 * @var array<string, array{label:string, emoji:string}> $kinds
 * @var array<string,string> $errors
 * @var array<string,string> $old
 * @var string $csrf
 */
$isCreate  = $mode === 'create';
$pageTitle = $isCreate ? 'Nueva notificación' : 'Editar notificación';
$action    = $isCreate ? '/admin/notificaciones' : '/admin/notificaciones/' . (int) $announcement['id'];
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/notificaciones">Notificaciones</a> &rsaquo;
        <span><?= e($pageTitle) ?></span>
    </p>
    <h1><?= e($pageTitle) ?></h1>
</section>

<form method="post" action="<?= e($action) ?>" class="admin-form" novalidate>
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <div class="field">
        <label for="kind">Tipo</label>
        <select id="kind" name="kind" required>
            <?php foreach ($kinds as $key => $meta): ?>
                <option value="<?= e($key) ?>" <?= ($old['kind'] ?? '') === $key ? 'selected' : '' ?>>
                    <?= e($meta['emoji'] . ' ' . $meta['label']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['kind'])): ?>
            <small class="field-error"><?= e($errors['kind']) ?></small>
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
        <label for="body">Cuerpo</label>
        <textarea id="body" name="body" rows="6" maxlength="4000"><?= e($old['body'] ?? '') ?></textarea>
        <small class="field-hint">Opcional. Máximo 4000 caracteres.</small>
        <?php if (!empty($errors['body'])): ?>
            <small class="field-error"><?= e($errors['body']) ?></small>
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
        <a class="button button-ghost" href="/admin/notificaciones">Cancelar</a>
        <button type="submit" class="button button-primary">
            <?= $isCreate ? 'Crear notificación' : 'Guardar cambios' ?>
        </button>
    </div>
</form>
