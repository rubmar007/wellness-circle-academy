<?php
declare(strict_types=1);
/**
 * @var string $email
 */
$pageTitle = 'Solicitud enviada';
?>
<section class="auth-card">
    <h1>Solicitud recibida</h1>
    <p>
        Si <strong><?= e($email) ?></strong> corresponde a una cuenta de administrador
        activa, recibirás un email con el link de recuperación en los próximos minutos.
    </p>
    <p class="muted">
        Revisa también la carpeta de <em>Spam</em> o <em>Promociones</em>. El link expira
        15 minutos después de ser generado y solo puede usarse una vez.
    </p>
    <p>
        <a class="button button-ghost" href="/login">Volver al login</a>
    </p>
</section>
