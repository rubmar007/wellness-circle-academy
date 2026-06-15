<?php
declare(strict_types=1);
/**
 * @var array<int, array<string,mixed>> $noticias
 * @var string $csrf
 */
$pageTitle = 'Noticias';

function noticiaVideoEmbed(string $url): string
{
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
        $id = htmlspecialchars($m[1], ENT_QUOTES);
        return '<div class="media-video-wrap"><iframe src="https://www.youtube.com/embed/' . $id
            . '" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
    }
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
        $id = htmlspecialchars($m[1], ENT_QUOTES);
        return '<div class="media-video-wrap"><iframe src="https://player.vimeo.com/video/' . $id
            . '" loading="lazy" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe></div>';
    }
    $safe = htmlspecialchars($url, ENT_QUOTES);
    return '<video class="media-video-direct" src="' . $safe . '" controls preload="metadata"></video>';
}
?>
<section class="page-head">
    <h1>Noticias</h1>
</section>

<?php if ($noticias === []): ?>
    <p class="section-empty">No hay noticias disponibles por el momento.</p>
<?php else: ?>
    <?php foreach ($noticias as $n): ?>
        <article class="section-card media-card">

            <?php if (!empty($n['image_path'])): ?>
                <img src="<?= e($n['image_path']) ?>" alt="" class="media-card-img" loading="lazy">
            <?php endif; ?>

            <div class="media-card-body">
                <h2 class="media-card-title"><?= e($n['title']) ?></h2>

                <?php if (!empty($n['body'])): ?>
                    <p class="muted small"><?= e_nl2br((string) $n['body']) ?></p>
                <?php endif; ?>

                <?php if (!empty($n['video_url'])): ?>
                    <?= noticiaVideoEmbed((string) $n['video_url']) ?>
                <?php endif; ?>

                <?php if (!empty($n['link_url'])): ?>
                    <a href="<?= e($n['link_url']) ?>"
                       class="button button-primary media-card-cta"
                       target="_blank" rel="noopener noreferrer">
                        <?= e(!empty($n['link_label']) ? $n['link_label'] : 'Ver más') ?>
                    </a>
                <?php endif; ?>
            </div>

        </article>
    <?php endforeach; ?>
<?php endif; ?>
