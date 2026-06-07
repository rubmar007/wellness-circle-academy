<?php
declare(strict_types=1);
/**
 * @var array<string,string> $errors
 * @var array<string,string> $old
 * @var string $csrf
 */
$pageTitle = 'Entrar';
?>
<div class="auth-main-card">

    <div class="auth-logo-section">
        <img src="/assets/img/logo-card.png" alt="Wellness Circle Academy" class="auth-logo-card">
    </div>

    <div class="auth-welcome">
        <span class="auth-welcome-script">Bienvenido a</span>
        <h1 class="auth-welcome-title">Wellness Circle Academy</h1>
        <p class="auth-tagline">Tu camino claro para crecer,<br>liderar y duplicar tu negocio.</p>
    </div>

    <div class="auth-form-wrap">

        <?php if (!empty($errors['general'])): ?>
            <p class="alert alert-error"><?= e($errors['general']) ?></p>
        <?php endif; ?>

        <form method="post" action="/login" novalidate>
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

            <div class="auth-field">
                <label for="email">Email</label>
                <div class="auth-input-wrap">
                    <span class="auth-input-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                    </span>
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
                </div>
                <?php if (!empty($errors['email'])): ?>
                    <small class="field-error"><?= e($errors['email']) ?></small>
                <?php endif; ?>
            </div>

            <div class="auth-field">
                <label for="password">Contraseña</label>
                <div class="auth-input-wrap">
                    <span class="auth-input-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Ingresa tu contraseña"
                        minlength="1"
                        maxlength="200">
                </div>
                <?php if (!empty($errors['password'])): ?>
                    <small class="field-error"><?= e($errors['password']) ?></small>
                <?php endif; ?>
            </div>

            <button type="submit" class="auth-submit-btn">Entrar</button>
        </form>

        <div class="auth-divider" aria-hidden="true"><span></span></div>

        <div class="auth-support">
            <div class="auth-support-icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="auth-support-text">
                <strong>¿Aún no tienes acceso?</strong>
                <p>Contacta a tu patrocinador para formar parte de <span class="text-gold">Wellness Circle Academy</span>.</p>
            </div>
        </div>

    </div><!-- /.auth-form-wrap -->
</div><!-- /.auth-main-card -->
