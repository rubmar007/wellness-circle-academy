<?php
declare(strict_types=1);
/**
 * @var string $mode          'create' o 'edit'
 * @var array|null $user
 * @var array<string,string> $errors
 * @var array<string,string> $old
 * @var string $csrf
 */
$isCreate  = $mode === 'create';
$pageTitle = $isCreate ? 'Nuevo usuario' : 'Editar usuario';
$action    = $isCreate ? '/admin/usuarios' : '/admin/usuarios/' . (int) $user['id'];
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/usuarios">Usuarios</a> &rsaquo;
        <span><?= e($pageTitle) ?></span>
    </p>
    <h1><?= e($pageTitle) ?></h1>
</section>

<form method="post" action="<?= e($action) ?>" class="admin-form" novalidate>
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <div class="field">
        <label for="name">Nombre</label>
        <input
            type="text"
            id="name"
            name="name"
            value="<?= e($old['name'] ?? '') ?>"
            required
            minlength="2"
            maxlength="120">
        <?php if (!empty($errors['name'])): ?>
            <small class="field-error"><?= e($errors['name']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="email">Email</label>
        <input
            type="email"
            id="email"
            name="email"
            value="<?= e($old['email'] ?? '') ?>"
            required
            autocapitalize="none"
            spellcheck="false"
            maxlength="190">
        <?php if (!empty($errors['email'])): ?>
            <small class="field-error"><?= e($errors['email']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="role">Rol</label>
        <select id="role" name="role" required>
            <option value="member" <?= ($old['role'] ?? '') === 'member' ? 'selected' : '' ?>>Miembro</option>
            <option value="admin"  <?= ($old['role'] ?? '') === 'admin'  ? 'selected' : '' ?>>Administrador</option>
        </select>
        <?php if (!empty($errors['role'])): ?>
            <small class="field-error"><?= e($errors['role']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="password">
            Contraseña <?= $isCreate ? '' : '<span class="field-hint-inline">(opcional al editar)</span>' ?>
        </label>
        <input
            type="password"
            id="password"
            name="password"
            autocomplete="new-password"
            <?= $isCreate ? 'required minlength="10"' : 'minlength="0"' ?>
            maxlength="200">
        <small class="field-hint">
            <?= $isCreate
                ? 'Mínimo 10 caracteres. La contraseña se guarda con Argon2id y no se podrá recuperar después.'
                : 'Déjalo vacío para no cambiar la contraseña actual. Mínimo 10 caracteres si la cambias.' ?>
        </small>
        <?php if (!empty($errors['password'])): ?>
            <small class="field-error"><?= e($errors['password']) ?></small>
        <?php endif; ?>
    </div>

    <div class="form-actions">
        <a class="button button-ghost" href="/admin/usuarios">Cancelar</a>
        <button type="submit" class="button button-primary">
            <?= $isCreate ? 'Crear usuario' : 'Guardar cambios' ?>
        </button>
    </div>
</form>
