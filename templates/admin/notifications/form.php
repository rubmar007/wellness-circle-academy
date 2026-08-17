<?php
declare(strict_types=1);
/**
 * @var string                          $mode  'create' | 'edit'
 * @var array<string,mixed>|null        $notif
 * @var array<int, array<string,mixed>> $users
 * @var array<string,string>            $kitLabels
 * @var array<string,string>            $roleLabels
 * @var array<string,string>            $errors
 * @var array<string,string>            $old
 * @var string $csrf
 *
 * Selector de audiencia y el toggle de recurrencia se resuelven con el
 * mismo "radio hack" CSS que /mapa-parches (input oculto + label ~
 * checked). Cero JavaScript — ver public/assets/css/notifications.css.
 */
$isCreate  = $mode === 'create';
$pageTitle = $isCreate ? 'Programar notificación' : 'Editar notificación';
$action    = $isCreate ? '/admin/notificaciones' : '/admin/notificaciones/' . (int) $notif['id'];
$aud       = $old['audience_type'] ?? 'all';
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/notificaciones">Notificaciones</a> &rsaquo;
        <span><?= $isCreate ? 'Programar' : 'Editar' ?></span>
    </p>
    <h1><?= $isCreate ? 'Programar notificación' : 'Editar notificación' ?></h1>
</section>

<form method="post" action="<?= e($action) ?>" class="admin-form notif-form" novalidate>
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <input type="radio" id="aud-all"  class="notif-radio" name="audience_type" value="all"  <?= $aud === 'all'  ? 'checked' : '' ?>>
    <input type="radio" id="aud-role" class="notif-radio" name="audience_type" value="role" <?= $aud === 'role' ? 'checked' : '' ?>>
    <input type="radio" id="aud-kit"  class="notif-radio" name="audience_type" value="kit"  <?= $aud === 'kit'  ? 'checked' : '' ?>>
    <input type="radio" id="aud-user" class="notif-radio" name="audience_type" value="user" <?= $aud === 'user' ? 'checked' : '' ?>>
    <input type="checkbox" id="is_recurring" class="notif-radio" name="is_recurring" value="1" <?= ($old['is_recurring'] ?? '') === '1' ? 'checked' : '' ?>>

    <div class="field">
        <label for="title">Título</label>
        <input type="text" id="title" name="title" maxlength="150" value="<?= e($old['title'] ?? '') ?>" required>
        <?php if (!empty($errors['title'])): ?><small class="field-error"><?= e($errors['title']) ?></small><?php endif; ?>
    </div>

    <div class="field">
        <label for="body">Mensaje</label>
        <textarea id="body" name="body" rows="3" required><?= e($old['body'] ?? '') ?></textarea>
        <?php if (!empty($errors['body'])): ?><small class="field-error"><?= e($errors['body']) ?></small><?php endif; ?>
    </div>

    <div class="field field-checkbox">
        <label>
            <input type="checkbox" name="include_patches" value="1" <?= ($old['include_patches'] ?? '') === '1' ? 'checked' : '' ?>>
            Agregar el parche del día a cada destinatario
        </label>
        <small class="field-hint">Al enviarse, se agrega al mensaje el nombre del parche (o parches) que le toca ese día a cada persona, según su kit activo. Si la persona no tiene kit activo, no se agrega nada.</small>
    </div>

    <div class="field">
        <label for="url">Enlace al tocar la notificación (opcional)</label>
        <input type="text" id="url" name="url" maxlength="255" placeholder="/mi-kit" value="<?= e($old['url'] ?? '') ?>">
        <small class="field-hint">Ruta interna (ej. /mi-kit) o URL completa. Si se deja vacío, abre /dashboard.</small>
        <?php if (!empty($errors['url'])): ?><small class="field-error"><?= e($errors['url']) ?></small><?php endif; ?>
    </div>

    <div class="field">
        <label>¿A quién se dirige?</label>
        <div class="notif-pillrow">
            <label for="aud-all"  class="notif-pill">Todos los usuarios</label>
            <label for="aud-role" class="notif-pill">Por rol</label>
            <label for="aud-kit"  class="notif-pill">Por kit activo</label>
            <label for="aud-user" class="notif-pill">Persona específica</label>
        </div>
        <?php if (!empty($errors['audience_type'])): ?><small class="field-error"><?= e($errors['audience_type']) ?></small><?php endif; ?>
    </div>

    <div class="field aud-field-role">
        <label for="audience_role">Rol</label>
        <select id="audience_role" name="audience_role">
            <option value="">— Selecciona un rol —</option>
            <?php foreach ($roleLabels as $slug => $label): ?>
                <option value="<?= e($slug) ?>" <?= ($old['audience_role'] ?? '') === $slug ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['audience_role'])): ?><small class="field-error"><?= e($errors['audience_role']) ?></small><?php endif; ?>
    </div>

    <div class="field aud-field-kit">
        <label for="audience_kit_slug">Kit activo</label>
        <select id="audience_kit_slug" name="audience_kit_slug">
            <option value="">— Selecciona un kit —</option>
            <?php foreach ($kitLabels as $slug => $label): ?>
                <option value="<?= e($slug) ?>" <?= ($old['audience_kit_slug'] ?? '') === $slug ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <small class="field-hint">Solo llega a quienes tengan ese kit activo en este momento.</small>
        <?php if (!empty($errors['audience_kit_slug'])): ?><small class="field-error"><?= e($errors['audience_kit_slug']) ?></small><?php endif; ?>
    </div>

    <div class="field aud-field-user">
        <label for="audience_user_id">Persona</label>
        <select id="audience_user_id" name="audience_user_id">
            <option value="">— Selecciona una persona —</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= e($u['id']) ?>" <?= ($old['audience_user_id'] ?? '') === (string) $u['id'] ? 'selected' : '' ?>>
                    <?= e($u['name']) ?> (<?= e($u['email']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['audience_user_id'])): ?><small class="field-error"><?= e($errors['audience_user_id']) ?></small><?php endif; ?>
    </div>

    <div class="field">
        <label for="scheduled_at">Fecha y hora de envío (CDMX)</label>
        <input type="datetime-local" id="scheduled_at" name="scheduled_at" value="<?= e($old['scheduled_at'] ?? '') ?>" required>
        <small class="field-hint">Hora de Ciudad de México. Ahora mismo son las <?= e((new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City')))->format('Y-m-d H:i')) ?> en CDMX.</small>
        <?php if (!empty($errors['scheduled_at'])): ?><small class="field-error"><?= e($errors['scheduled_at']) ?></small><?php endif; ?>
    </div>

    <div class="field">
        <label for="is_recurring" class="notif-pill">🔁 Repetir automáticamente</label>
    </div>

    <div class="field recurrence-field">
        <label for="recurrence_freq">Frecuencia</label>
        <select id="recurrence_freq" name="recurrence_freq">
            <option value="daily"  <?= ($old['recurrence_freq'] ?? '') === 'daily'  ? 'selected' : '' ?>>Diaria (todos los días, a la misma hora)</option>
            <option value="weekly" <?= ($old['recurrence_freq'] ?? '') === 'weekly' ? 'selected' : '' ?>>Semanal (cada 7 días, mismo día y hora)</option>
        </select>
        <small class="field-hint">Después de cada envío se calcula automáticamente la siguiente fecha.</small>
        <?php if (!empty($errors['recurrence_freq'])): ?><small class="field-error"><?= e($errors['recurrence_freq']) ?></small><?php endif; ?>
    </div>

    <div class="form-actions">
        <a class="button button-ghost" href="/admin/notificaciones">Cancelar</a>
        <button type="submit" class="button button-primary">
            <?= $isCreate ? 'Programar notificación' : 'Guardar cambios' ?>
        </button>
    </div>
</form>
