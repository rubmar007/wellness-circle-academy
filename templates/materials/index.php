<?php
declare(strict_types=1);
/**
 * @var array<int, array<string,mixed>> $pdfs
 * @var array<int, array<string,mixed>> $images
 * @var array<int, array<string,mixed>> $links
 * @var string $csrf
 */
$pageTitle = 'Materiales';
?>
<section class="page-head">
    <p class="breadcrumb"><a href="/dashboard">Dashboard</a> &rsaquo; <span>Materiales</span></p>
    <h1>Materiales</h1>
    <nav class="subtabs" aria-label="Categorías de materiales">
        <a class="button button-ghost button-sm" href="#pdfs">PDFs</a>
        <a class="button button-ghost button-sm" href="#imagenes">Imágenes</a>
        <a class="button button-ghost button-sm" href="#enlaces">Enlaces</a>
    </nav>
</section>

<section id="pdfs" class="section-card">
    <h2>PDFs</h2>
    <?php
    $pdfRendered = false;
    foreach ($pdfs as $pdf):
        $url = \App\Embed::sanitizeDownloadUrl($pdf['url'] ?? null);
        if ($url === null) {
            continue;
        }
        $pdfRendered = true;
    ?>
        <div class="material-row">
            <span><?= e($pdf['title']) ?></span>
            <a class="button button-ghost button-sm" href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer">Abrir en Drive</a>
        </div>
    <?php endforeach; ?>
    <?php if (!$pdfRendered): ?>
        <p class="muted">Nada por aquí todavía.</p>
    <?php endif; ?>
</section>

<section id="imagenes" class="section-card">
    <h2>Imágenes</h2>
    <?php if ($images === []): ?>
        <p class="muted">Nada por aquí todavía.</p>
    <?php else: ?>
        <div class="material-grid">
            <?php foreach ($images as $img): ?>
                <?php if (!empty($img['image_url'])): ?>
                    <?php $link = !empty($img['url']) ? e($img['url']) : e($img['image_url']); ?>
                    <a class="material-card" href="<?= $link ?>" target="_blank" rel="noopener noreferrer">
                        <img class="material-img" src="<?= e($img['image_url']) ?>" alt="<?= e($img['title']) ?>" loading="lazy">
                        <span class="material-card-title"><?= e($img['title']) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section id="enlaces" class="section-card">
    <h2>Enlaces</h2>
    <?php if ($links === []): ?>
        <p class="muted">Nada por aquí todavía.</p>
    <?php else: ?>
        <?php foreach ($links as $link): ?>
            <div class="material-row">
                <span><?= e($link['title']) ?></span>
                <a class="button button-ghost button-sm" href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer">Abrir enlace</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
