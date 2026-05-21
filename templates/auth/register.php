<?php
declare(strict_types=1);
/**
 * @var array<string,string> $errors
 * @var array<string,string> $old
 * @var string $csrf
 */
$pageTitle = 'Crear cuenta';
?>
<section class="auth-card">
    <h1>Crear cuenta</h1>

    <form method="post" action="/registro" novalidate>
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <div class="field">
            <label for="name">Nombre</label>
            <input
                type="text"
                id="name"
                name="name"
                value="<?= e($old['name'] ?? '') ?>"
                required
                autocomplete="name"
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
                autocomplete="email"
                autocapitalize="none"
                spellcheck="false"
                maxlength="190">
            <?php if (!empty($errors['email'])): ?>
                <small class="field-error"><?= e($errors['email']) ?></small>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="password">Contraseña</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="new-password"
                minlength="10"
                maxlength="200">
            <small class="field-hint">Mínimo 10 caracteres.</small>
            <?php if (!empty($errors['password'])): ?>
                <small class="field-error"><?= e($errors['password']) ?></small>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="password2">Confirmar contraseña</label>
            <input
                type="password"
                id="password2"
                name="password2"
                required
                autocomplete="new-password"
                minlength="10"
                maxlength="200">
            <?php if (!empty($errors['password2'])): ?>
                <small class="field-error"><?= e($errors['password2']) ?></small>
            <?php endif; ?>
        </div>

        <button type="submit" class="button button-primary button-block">Crear cuenta</button>
    </form>

    <p class="auth-footer">
        ¿Ya tienes cuenta? <a href="/login">Entra</a>.
    </p>
</section>
