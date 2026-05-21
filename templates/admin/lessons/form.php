<?php
declare(strict_types=1);
/**
 * @var string $mode
 * @var array<string,mixed> $program
 * @var array<string,mixed>|null $lesson
 * @var array<string,string> $errors
 * @var array<string,string> $old
 * @var string $csrf
 */
$isCreate  = $mode === 'create';
$pageTitle = $isCreate ? 'Nueva lección' : 'Editar lección';
$action    = $isCreate
    ? '/admin/programas/' . (int) $program['id'] . '/lecciones'
    : '/admin/lecciones/'  . (int) $lesson['id'];
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/programas">Programas</a> &rsaquo;
        <a href="/admin/programas/<?= e($program['id']) ?>/lecciones"><?= e($program['title']) ?></a> &rsaquo;
        <span><?= e($pageTitle) ?></span>
    </p>
    <h1><?= e($pageTitle) ?></h1>
</section>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="admin-form" novalidate>
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <div class="field-row">
        <div class="field">
            <label for="day_number">Día</label>
            <input
                type="number"
                id="day_number"
                name="day_number"
                value="<?= e($old['day_number'] ?? '') ?>"
                required
                min="1"
                max="9999"
                step="1">
            <?php if (!empty($errors['day_number'])): ?>
                <small class="field-error"><?= e($errors['day_number']) ?></small>
            <?php endif; ?>
        </div>

        <div class="field field-checkbox">
            <label>
                <input
                    type="checkbox"
                    name="is_published"
                    value="1"
                    <?= ($old['is_published'] ?? '') === '1' ? 'checked' : '' ?>>
                Publicada (visible para miembros)
            </label>
        </div>
    </div>

    <div class="field">
        <label for="title">Título de la lección</label>
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
        <label for="objective">Objetivo del día</label>
        <textarea id="objective" name="objective" rows="2" maxlength="8000"><?= e($old['objective'] ?? '') ?></textarea>
    </div>

    <div class="field">
        <label for="post_text">Publicación para redes</label>
        <textarea id="post_text" name="post_text" rows="6" maxlength="8000"><?= e($old['post_text'] ?? '') ?></textarea>
        <small class="field-hint">Texto principal que el miembro copiará para publicar.</small>
    </div>

    <div class="field">
        <label for="story_text">Story sugerida</label>
        <textarea id="story_text" name="story_text" rows="3" maxlength="8000"><?= e($old['story_text'] ?? '') ?></textarea>
    </div>

    <div class="field">
        <label for="conversation_text">Conversación ejemplo</label>
        <textarea id="conversation_text" name="conversation_text" rows="5" maxlength="8000"><?= e($old['conversation_text'] ?? '') ?></textarea>
    </div>

    <div class="field">
        <label for="action_text">Acción del día</label>
        <textarea id="action_text" name="action_text" rows="3" maxlength="8000"><?= e($old['action_text'] ?? '') ?></textarea>
        <small class="field-hint">Una acción por línea, ej: <code>- Publicar el post</code>.</small>
    </div>

    <div class="field">
        <label for="tip_text">Tip del día</label>
        <textarea id="tip_text" name="tip_text" rows="3" maxlength="8000"><?= e($old['tip_text'] ?? '') ?></textarea>
    </div>

    <div class="field">
        <label for="image">Imagen del día</label>
        <?php if (!$isCreate && !empty($lesson['image_url'])): ?>
            <p class="form-image-current">
                <img src="<?= e($lesson['image_url']) ?>" alt="" loading="lazy">
                <span class="muted small">Actual. Sube otra para reemplazarla.</span>
            </p>
        <?php endif; ?>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
        <small class="field-hint">JPG, PNG o WebP. Máximo 5 MB.</small>
        <?php if (!empty($errors['image'])): ?>
            <small class="field-error"><?= e($errors['image']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="checklist_text">Checklist (un ítem por línea)</label>
        <textarea id="checklist_text" name="checklist_text" rows="5" maxlength="4000" placeholder="Ya publiqué&#10;Ya subí stories&#10;Ya hablé con 3 personas"><?= e($old['checklist_text'] ?? '') ?></textarea>
        <small class="field-hint">Máximo 20 ítems, 200 caracteres por ítem.</small>
        <?php if (!empty($errors['checklist_text'])): ?>
            <small class="field-error"><?= e($errors['checklist_text']) ?></small>
        <?php endif; ?>
    </div>

    <div class="form-actions">
        <a class="button button-ghost" href="/admin/programas/<?= e($program['id']) ?>/lecciones">Cancelar</a>
        <button type="submit" class="button button-primary">
            <?= $isCreate ? 'Crear lección' : 'Guardar cambios' ?>
        </button>
    </div>
</form>
