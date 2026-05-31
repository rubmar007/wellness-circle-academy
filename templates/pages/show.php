<?php
declare(strict_types=1);
/**
 * @var array<string,mixed> $page
 * @var string $csrf
 */
$pageTitle = (string) $page['title'];
?>
<section class="page-head">
    <h1><?= e($page['title']) ?></h1>
</section>

<article class="section-card">
    <?php if (empty($page['body'])): ?>
        <p class="section-empty">Aún no hay contenido.</p>
    <?php else: ?>
        <p><?= e_nl2br((string) $page['body']) ?></p>
    <?php endif; ?>
</article>
