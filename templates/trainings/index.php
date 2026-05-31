<?php
declare(strict_types=1);
/**
 * @var array<string,string>                      $categories
 * @var array<string, array<int, array<string,mixed>>> $grouped
 * @var string $csrf
 */
$pageTitle = 'Entrenamiento';

$hasAny = false;
foreach ($grouped as $videos) {
    if ($videos !== []) {
        $hasAny = true;
        break;
    }
}
?>
<section class="page-head">
    <h1>Entrenamiento</h1>
</section>

<?php if (!$hasAny): ?>
    <p class="section-empty">Aún no hay entrenamientos.</p>
<?php else: ?>
    <?php $firstOpen = true; ?>
    <?php foreach ($categories as $key => $label): ?>
        <?php
        $videos = $grouped[$key] ?? [];
        if ($videos === []) {
            continue;
        }
        ?>
        <details class="topic"<?= $firstOpen ? ' open' : '' ?>>
            <summary><?= e($label) ?></summary>
            <div class="topic-body">
                <?php foreach ($videos as $v): ?>
                    <?php
                    $video = \App\Embed::parseVideo($v['video_url'] ?? null);
                    ?>
                    <div class="video-row">
                        <h2><?= e($v['title']) ?></h2>
                        <?php if ($video !== null): ?>
                            <div class="video-frame">
                                <iframe
                                    src="<?= e($video['embed_url']) ?>"
                                    title="<?= e($v['title']) ?>"
                                    loading="lazy"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen></iframe>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </details>
        <?php $firstOpen = false; ?>
    <?php endforeach; ?>
<?php endif; ?>
