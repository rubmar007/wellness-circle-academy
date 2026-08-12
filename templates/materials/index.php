<?php
declare(strict_types=1);
/**
 * @var array<string, list<array<string,mixed>>> $pdfs
 * @var array<string, list<array<string,mixed>>> $images
 * @var array<string, list<array<string,mixed>>> $links
 * @var string $csrf
 */
$pageTitle = 'Materiales';
$hasPdfs   = $pdfs   !== [];
$hasImages = $images !== [];
$hasLinks  = $links  !== [];

// Hasta 3 descripciones breves por material tipo Imagen, en este orden fijo.
$descFields = ['description' => 1, 'description2' => 2, 'description3' => 3];
?>
<section class="page-head">
    <p class="breadcrumb"><a href="/dashboard">Dashboard</a> &rsaquo; <span>Materiales</span></p>
    <h1>Materiales</h1>
</section>

<div class="mat-filter-wrap">
    <input type="radio" name="mf" id="mf-todos" checked>
    <input type="radio" name="mf" id="mf-pdfs">
    <input type="radio" name="mf" id="mf-imagenes">
    <input type="radio" name="mf" id="mf-enlaces">

    <div class="mat-filters">
        <label class="mat-filter-label" for="mf-todos">Todos</label>
        <label class="mat-filter-label" for="mf-pdfs">PDFs</label>
        <label class="mat-filter-label" for="mf-imagenes">Imágenes</label>
        <label class="mat-filter-label" for="mf-enlaces">Enlaces</label>
    </div>

    <div class="mat-sections">

        <section class="section-card mat-sec mat-sec-pdfs">
            <h2>PDFs</h2>
            <?php if (!$hasPdfs): ?>
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

        <section class="section-card mat-sec mat-sec-imagenes">
            <h2>Imágenes</h2>
            <?php if (!$hasImages): ?>
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
                            <div class="material-card">
                                <a class="material-card-media" href="<?= $link ?>" target="_blank" rel="noopener noreferrer">
                                    <img class="material-img" src="<?= e($img['image_url']) ?>" alt="<?= e($img['title']) ?>" loading="lazy">
                                </a>
                                <span class="material-card-title"><?= e($img['title']) ?></span>
                                <?php foreach ($descFields as $field => $n): ?>
                                    <?php if (!empty($img[$field])): ?>
                                        <?php
                                        $toggleId = 'mdesc-' . (int) $img['id'] . '-' . $n;
                                        $textId   = 'mdesc-text-' . (int) $img['id'] . '-' . $n;
                                        ?>
                                        <div class="material-card-desc-row">
                                            <input type="checkbox" id="<?= e($toggleId) ?>" class="material-desc-toggle" hidden>
                                            <label for="<?= e($toggleId) ?>" class="material-card-desc"><?= e($img[$field]) ?></label>
                                            <div class="material-desc-modal">
                                                <label for="<?= e($toggleId) ?>" class="material-desc-modal-backdrop" aria-hidden="true"></label>
                                                <div class="material-desc-modal-box" role="dialog" aria-modal="true">
                                                    <p class="material-desc-modal-text" id="<?= e($textId) ?>"><?= e($img[$field]) ?></p>
                                                    <div class="material-desc-modal-actions">
                                                        <button
                                                            type="button"
                                                            class="button button-ghost button-sm copy-button"
                                                            data-copy-target="#<?= e($textId) ?>"
                                                            data-copy-label-original="Copiar"
                                                            data-copy-label-done="Copiado"
                                                            aria-label="Copiar descripción">
                                                            Copiar
                                                        </button>
                                                        <label for="<?= e($toggleId) ?>" class="button button-ghost button-sm">Cerrar</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section class="section-card mat-sec mat-sec-enlaces">
            <h2>Enlaces</h2>
            <?php if (!$hasLinks): ?>
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

    </div>
</div>
