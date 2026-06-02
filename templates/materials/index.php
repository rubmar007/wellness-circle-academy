<?php
declare(strict_types=1);
/**
 * @var array<string, list<array<string,mixed>>> $pdfs
 * @var array<string, list<array<string,mixed>>> $images
 * @var array<string, list<array<string,mixed>>> $links
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
    <?php if ($pdfs === []): ?>
        <p class="muted">Nada por aquí todavía.</p>
    <?php else: ?>
        <?php foreach ($pdfs as $folder => $items): ?>
            <?php if ($folder !== ''): ?>
                <h3 class="material-folder"><?= e($folder) ?></h3>
            <?php endif; ?>
            <?php foreach ($items as $pdf): ?>
                <?php $url = \App\Embed::sanitizeDownloadUrl($pdf['url'] ?? null); ?>
                <?php if ($url === null): continue; endif; ?>
                <div class="material-row">
                    <span><?= e($pdf['title']) ?></span>
                    <a class="button button-ghost button-sm" href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer">Abrir en Drive</a>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<section id="imagenes" class="section-card">
    <h2>Imágenes</h2>
    <?php if ($images === []): ?>
        <p class="muted">Nada por aquí todavía.</p>
    <?php else: ?>
        <?php foreach ($images as $folder => $items): ?>
            <?php if ($folder !== ''): ?>
                <h3 class="material-folder"><?= e($folder) ?></h3>
            <?php endif; ?>
            <div class="material-grid">
                <?php foreach ($items as $img): ?>
                    <?php if (empty($img['image_url'])): continue; endif; ?>
                    <?php $link = !empty($img['url']) ? e($img['url']) : e($img['image_url']); ?>
                    <a class="material-card" href="<?= $link ?>" target="_blank" rel="noopener noreferrer">
                        <img class="material-img" src="<?= e($img['image_url']) ?>" alt="<?= e($img['title']) ?>" loading="lazy">
                        <span class="material-card-title"><?= e($img['title']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<section id="enlaces" class="section-card">
    <h2>Enlaces</h2>
    <?php if ($links === []): ?>
        <p class="muted">Nada por aquí todavía.</p>
    <?php else: ?>
        <?php foreach ($links as $folder => $items): ?>
            <?php if ($folder !== ''): ?>
                <h3 class="material-folder"><?= e($folder) ?></h3>
            <?php endif; ?>
            <?php foreach ($items as $link): ?>
                <div class="material-row">
                    <span><?= e($link['title']) ?></span>
                    <a class="button button-ghost button-sm" href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer">Abrir enlace</a>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
