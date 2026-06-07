<?php
declare(strict_types=1);
/**
 * @var array<string,string> $errors
 * @var array<string,string> $old
 * @var string $csrf
 */
$pageTitle = 'Entrar';
?>
<div class="auth-particles" aria-hidden="true"></div>

<div class="auth-logo-wrap">
    <img class="auth-logo" src="/assets/img/logo.png" alt="Wellness Circle Academy">
</div>

<section class="auth-card">
    <div class="auth-welcome-head">
        <span class="auth-welcome-script">Bienvenido a</span>
        <h1>Wellness Circle<br>Academy</h1>
        <p class="auth-tagline">Tu camino claro para crecer, liderar y duplicar tu negocio.</p>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <p class="alert alert-error"><?= e($errors['general']) ?></p>
    <?php endif; ?>

    <form method="post" action="/login" novalidate>
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

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
                placeholder="Ingresa tu email"
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
                autocomplete="current-password"
                placeholder="Ingresa tu contraseña"
                minlength="1"
                maxlength="200">
            <?php if (!empty($errors['password'])): ?>
                <small class="field-error"><?= e($errors['password']) ?></small>
            <?php endif; ?>
        </div>

        <button type="submit" class="button button-primary button-block">Entrar</button>
    </form>

    <p class="auth-footer">
        <strong>¿Aún no tienes acceso?</strong><br>
        Contacta a tu patrocinador para formar parte de <span class="text-gold">Wellness Circle Academy</span>.
    </p>
</section>
