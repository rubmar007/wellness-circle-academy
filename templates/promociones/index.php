<?php
declare(strict_types=1);
/**
 * @var array<int, array<string,mixed>> $promociones
 * @var string $csrf
 */
$pageTitle = 'Promociones';

function promoVideoEmbed(string $url): string
{
    // YouTube watch o youtu.be
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
        $id = htmlspecialchars($m[1], ENT_QUOTES);
        return '<div class="media-video-wrap"><iframe src="https://www.youtube.com/embed/' . $id
            . '" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
    }
    // Vimeo
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
        $id = htmlspecialchars($m[1], ENT_QUOTES);
        return '<div class="media-video-wrap"><iframe src="https://player.vimeo.com/video/' . $id
            . '" loading="lazy" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe></div>';
    }
    // URL directa de video
    $safe = htmlspecialchars($url, ENT_QUOTES);
    return '<video class="media-video-direct" src="' . $safe . '" controls preload="metadata"></video>';
}
?>
<section class="page-head">
    <h1>Promociones</h1>
</section>

<?php if ($promociones === []): ?>
    <p class="section-empty">No hay promociones disponibles por el momento.</p>
<?php else: ?>
    <?php foreach ($promociones as $p): ?>
        <article class="section-card media-card">

            <?php if (!empty($p['image_path'])): ?>
                <img src="<?= e($p['image_path']) ?>" alt="" class="media-card-img" loading="lazy">
            <?php endif; ?>

            <div class="media-card-body">
                <h2 class="media-card-title"><?= e($p['title']) ?></h2>

                <?php if (!empty($p['body'])): ?>
                    <p class="muted small"><?= e_nl2br((string) $p['body']) ?></p>
                <?php endif; ?>

                <?php if (!empty($p['video_url'])): ?>
                    <?= promoVideoEmbed((string) $p['video_url']) ?>
                <?php endif; ?>

                <?php if (!empty($p['link_url'])): ?>
                    <a href="<?= e($p['link_url']) ?>"
                       class="button button-primary media-card-cta"
                       target="_blank" rel="noopener noreferrer">
                        <?= e(!empty($p['link_label']) ? $p['link_label'] : 'Ver más') ?>
                    </a>
                <?php endif; ?>
            </div>

        </article>
    <?php endforeach; ?>
<?php endif; ?>
