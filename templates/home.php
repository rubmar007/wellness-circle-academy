<?php
declare(strict_types=1);
/** @var string $appName */
$pageTitle = 'Bienvenida';
?>
<section class="hero">
    <h1>Bienvenida a <?= e($appName) ?></h1>
    <p class="lead">
        La plataforma privada de duplicación para equipos de bienestar.
        Entra cada día, copia el contenido listo y construye tu red con simpleza.
    </p>

    <div class="hero-actions">
        <a class="button button-primary" href="/login">Entrar</a>
        <a class="button button-ghost" href="/registro">Crear cuenta</a>
    </div>
</section>

<section class="features">
    <article class="feature-card">
        <h2>Copia y pega</h2>
        <p>Publicaciones, stories y conversaciones listas para usar en redes.</p>
    </article>
    <article class="feature-card">
        <h2>Descarga imágenes</h2>
        <p>Material visual descargable, listo para publicar desde tu celular.</p>
    </article>
    <article class="feature-card">
        <h2>Checklist diario</h2>
        <p>Sigue tu progreso día por día y duplica el sistema en tu equipo.</p>
    </article>
</section>
