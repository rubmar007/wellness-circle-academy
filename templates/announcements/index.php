<?php
declare(strict_types=1);
/**
 * @var array<int, array<string,mixed>>                 $announcements
 * @var array<string, array{label:string, emoji:string}> $kinds
 * @var string $csrf
 */
$pageTitle = 'Notificaciones';
?>
<section class="page-head">
    <h1>Notificaciones</h1>
</section>

<?php if ($announcements === []): ?>
    <p class="section-empty">Aún no hay notificaciones.</p>
<?php else: ?>
    <?php foreach ($announcements as $a): ?>
        <?php
        $kind  = (string) $a['kind'];
        $emoji = $kinds[$kind]['emoji'] ?? '📣';
        ?>
        <article class="section-card">
            <div class="award">
                <span class="award-medal"><?= e($emoji) ?></span>
                <div>
                    <h2 class="award-title"><?= e($a['title']) ?></h2>
                    <?php if (!empty($a['body'])): ?>
                        <p class="muted small"><?= e_nl2br((string) $a['body']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
<?php endif; ?>
