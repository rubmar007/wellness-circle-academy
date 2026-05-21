<?php
declare(strict_types=1);
/**
 * @var array<string,string> $errors
 * @var array<string,string> $old
 * @var bool $configured
 * @var string $csrf
 */
$pageTitle = 'Recuperar acceso';
?>
<section class="auth-card">
    <h1>Recuperar acceso</h1>
    <p class="muted">
        Si tu email está registrado como administrador activo, recibirás un link
        en pocos minutos para definir una nueva contraseña. El link expira en 15 minutos
        y solo se puede usar una vez.
    </p>

    <?php if (!$configured): ?>
        <p class="alert alert-error">
            El servicio de email aún no está configurado en este entorno.
            Avisa al administrador del sistema para que añada las variables de Mailgun
            en <code>.env</code>.
        </p>
    <?php endif; ?>

    <form method="post" action="/ctoadmin" novalidate>
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <div class="field">
            <label for="email">Email de administrador</label>
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

        <button type="submit" class="button button-primary button-block">Enviar link de recuperación</button>
    </form>
</section>
