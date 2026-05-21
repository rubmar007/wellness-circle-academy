<?php
declare(strict_types=1);
/**
 * @var string $token
 * @var array<string,string> $errors
 * @var string $csrf
 */
$pageTitle = 'Definir nueva contraseña';
?>
<section class="auth-card">
    <h1>Definir nueva contraseña</h1>
    <p class="muted">
        Crea una contraseña fuerte. Se guardará con Argon2id y no podrá recuperarse
        después; apúntala en tu gestor de contraseñas.
    </p>

    <?php if (!empty($errors['general'])): ?>
        <p class="alert alert-error"><?= e($errors['general']) ?></p>
    <?php endif; ?>

    <form method="post" action="/restablecer/<?= e($token) ?>" novalidate>
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <div class="field">
            <label for="password">Nueva contraseña</label>
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

        <button type="submit" class="button button-primary button-block">Actualizar contraseña</button>
    </form>
</section>
