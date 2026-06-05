<?php
declare(strict_types=1);
/**
 * @var array<string,mixed> $page
 * @var array<string,mixed> $auth
 */
$pageTitle = 'Soy Cliente';
?>
<section class="page-head">
    <h1>Soy Cliente</h1>
</section>

<?php if (!empty($page['welcome_image_url'])): ?>
<article class="client-welcome">
    <div class="client-welcome-wrap">
        <img src="<?= e($page['welcome_image_url']) ?>" alt="Bienvenida" loading="lazy">
        <p class="client-welcome-name">Bienvenid@ <?= e($auth['name'] ?? '') ?></p>
    </div>
</article>
<?php endif; ?>

<?php
$renderTextBlock = function (string $title, ?string $text): void {
    if ($text === null || trim($text) === '') {
        return;
    }
    ?>
    <article class="copy-card">
        <header class="copy-card-head">
            <h2><?= e($title) ?></h2>
        </header>
        <div class="copy-card-body"><?= nl2br(e($text)) ?></div>
    </article>
    <?php
};

$renderVideo = function (?string $url): void {
    $video = \App\Embed::parseVideo($url);
    if ($video === null) {
        return;
    }
    ?>
    <div class="video-frame">
        <iframe
            src="<?= e($video['embed_url']) ?>"
            title="<?= e($video['title']) ?>"
            loading="lazy"
            referrerpolicy="strict-origin-when-cross-origin"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen></iframe>
    </div>
    <?php
};

$renderImageBlock = function (string $title, ?string $url): void {
    if ($url === null || $url === '') {
        return;
    }
    ?>
    <article class="image-card">
        <h2><?= e($title) ?></h2>
        <img src="<?= e($url) ?>" alt="<?= e($title) ?>" loading="lazy">
    </article>
    <?php
};
?>

<?php $renderTextBlock('¿Cómo utilizar el producto? / Importancia de la Hidratación', $page['uso_texto'] ?? null); ?>

<?php if (!empty($page['uso_pdf_url'])): ?>
<article class="copy-card client-pdf-card">
    <header class="copy-card-head">
        <h2>Guia de uso</h2>
    </header>
    <div class="copy-card-body">
        <a class="button button-ghost" href="<?= e($page['uso_pdf_url']) ?>" target="_blank" rel="noopener">Ver / Descargar PDF</a>
    </div>
    <object data="<?= e($page['uso_pdf_url']) ?>" type="application/pdf" class="pdf-embed">
        <p>Tu navegador no puede mostrar el PDF. <a href="<?= e($page['uso_pdf_url']) ?>" target="_blank">Descargarlo aqui</a>.</p>
    </object>
</article>
<?php endif; ?>

<?php if (!empty($page['activar_texto']) || !empty($page['activar_video_url'])): ?>
<article class="copy-card">
    <header class="copy-card-head">
        <h2>¿Cómo activar autoenvío?</h2>
    </header>
    <?php if (!empty($page['activar_texto'])): ?>
        <div class="copy-card-body"><?= nl2br(e($page['activar_texto'])) ?></div>
    <?php endif; ?>
    <?php $renderVideo($page['activar_video_url'] ?? null); ?>
</article>
<?php endif; ?>

<?php if (!empty($page['desactivar_texto']) || !empty($page['desactivar_video_url'])): ?>
<article class="copy-card">
    <header class="copy-card-head">
        <h2>¿Cómo desactivar autoenvío?</h2>
    </header>
    <?php if (!empty($page['desactivar_texto'])): ?>
        <div class="copy-card-body"><?= nl2br(e($page['desactivar_texto'])) ?></div>
    <?php endif; ?>
    <?php $renderVideo($page['desactivar_video_url'] ?? null); ?>
</article>
<?php endif; ?>

<?php if (!empty($page['preferente_texto']) || !empty($page['preferente_video_url'])): ?>
<article class="copy-card">
    <header class="copy-card-head">
        <h2>¿Cómo convertirte en cliente preferente plus?</h2>
    </header>
    <?php if (!empty($page['preferente_texto'])): ?>
        <div class="copy-card-body"><?= nl2br(e($page['preferente_texto'])) ?></div>
    <?php endif; ?>
    <?php $renderVideo($page['preferente_video_url'] ?? null); ?>
</article>
<?php endif; ?>

<?php $renderImageBlock('Beneficios del autoenvío / Regalos', $page['beneficios_autoenvio_url'] ?? null); ?>
<?php $renderImageBlock('Beneficios del cliente preferente plus', $page['beneficios_preferente_url'] ?? null); ?>

<?php if (!empty($page['texto_libre']) && trim((string) $page['texto_libre']) !== ''): ?>
<article class="copy-card">
    <div class="copy-card-body"><?= nl2br(e($page['texto_libre'])) ?></div>
</article>
<?php endif; ?>
