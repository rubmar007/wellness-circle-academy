<?php
declare(strict_types=1);
/**
 * @var array<string,mixed>   $lesson
 * @var array<int, string>    $checklist
 * @var array<int, int>       $completed
 * @var int|null              $prev_day
 * @var int|null              $next_day
 * @var string                $csrf
 */
$pageTitle = 'Día ' . $lesson['day_number'] . ' — ' . (string) $lesson['title'];
$backPath  = '/programas/' . $lesson['program_slug'] . '/dia/' . $lesson['day_number'];
$completedSet = array_flip(array_map('intval', $completed));
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/dashboard">Dashboard</a> &rsaquo;
        <a href="/programas/<?= e($lesson['program_slug']) ?>"><?= e($lesson['program_title']) ?></a> &rsaquo;
        <span>Día <?= e($lesson['day_number']) ?></span>
    </p>
    <h1>Día <?= e($lesson['day_number']) ?> — <?= e($lesson['title']) ?></h1>
    <?php if (!empty($lesson['objective'])): ?>
        <p class="lesson-objective-banner"><?= e($lesson['objective']) ?></p>
    <?php endif; ?>
</section>

<?php
/**
 * Helper local para tarjetas de texto copiable.
 *
 * @param string $title     Título de la tarjeta.
 * @param string $body      Texto a copiar (escapado automáticamente).
 * @param string $idPrefix  Prefijo único para el id del textarea.
 */
$renderCopyCard = function (string $title, ?string $body, string $idPrefix) use ($lesson): void {
    if ($body === null || trim($body) === '') {
        return;
    }
    $id = 'copy-' . $idPrefix . '-' . (int) $lesson['lesson_id'];
?>
    <article class="copy-card">
        <header class="copy-card-head">
            <h2><?= e($title) ?></h2>
            <button
                type="button"
                class="button button-ghost copy-button"
                data-copy-target="#<?= e($id) ?>"
                data-copy-label-original="Copiar texto"
                data-copy-label-done="Copiado">
                Copiar texto
            </button>
        </header>
        <textarea
            id="<?= e($id) ?>"
            class="copy-card-text"
            readonly
            spellcheck="false"
            aria-label="<?= e($title) ?>"><?= e($body) ?></textarea>
    </article>
<?php
};
?>

<?php $renderCopyCard('Publicación principal',    $lesson['post_text'],         'post'); ?>
<?php $renderCopyCard('Story sugerida',           $lesson['story_text'],        'story'); ?>
<?php $renderCopyCard('Conversación ejemplo',     $lesson['conversation_text'], 'convo'); ?>

<?php if (!empty($lesson['image_url'])): ?>
    <article class="image-card">
        <h2>Imagen del día</h2>
        <img src="<?= e($lesson['image_url']) ?>" alt="Imagen del día <?= e($lesson['day_number']) ?>" loading="lazy">
        <a
            class="button button-primary"
            href="<?= e($lesson['image_url']) ?>"
            download="wca-dia-<?= e($lesson['day_number']) ?>.jpg">
            Descargar imagen
        </a>
    </article>
<?php endif; ?>

<?php if (!empty($lesson['action_text'])): ?>
    <article class="info-card">
        <h2>Acción del día</h2>
        <div class="info-card-body"><?= e_nl2br($lesson['action_text']) ?></div>
    </article>
<?php endif; ?>

<?php if (!empty($lesson['tip_text'])): ?>
    <article class="info-card info-card-tip">
        <h2>Tip del día</h2>
        <div class="info-card-body"><?= e_nl2br($lesson['tip_text']) ?></div>
    </article>
<?php endif; ?>

<?php if ($checklist !== []): ?>
    <article class="checklist-card">
        <h2>Checklist</h2>
        <ul class="checklist">
            <?php foreach ($checklist as $i => $label):
                $isDone = isset($completedSet[(int) $i]);
                $action = $isDone ? 'uncheck' : 'check';
            ?>
                <li class="checklist-item <?= $isDone ? 'checklist-item-done' : '' ?>">
                    <form method="post" action="/progreso" class="checklist-form">
                        <input type="hidden" name="_csrf"     value="<?= e($csrf) ?>">
                        <input type="hidden" name="lesson_id" value="<?= e($lesson['lesson_id']) ?>">
                        <input type="hidden" name="item_index" value="<?= e($i) ?>">
                        <input type="hidden" name="action"    value="<?= e($action) ?>">
                        <input type="hidden" name="back"      value="<?= e($backPath) ?>">
                        <button type="submit" class="checklist-toggle" aria-pressed="<?= $isDone ? 'true' : 'false' ?>">
                            <span class="checklist-box" aria-hidden="true"><?= $isDone ? '✓' : '' ?></span>
                            <span class="checklist-label"><?= e($label) ?></span>
                        </button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
<?php endif; ?>

<nav class="lesson-nav" aria-label="Navegación entre días">
    <?php if ($prev_day !== null): ?>
        <a class="button button-ghost" href="/programas/<?= e($lesson['program_slug']) ?>/dia/<?= e($prev_day) ?>">
            &larr; Día <?= e($prev_day) ?>
        </a>
    <?php else: ?>
        <span></span>
    <?php endif; ?>

    <a class="button button-ghost" href="/programas/<?= e($lesson['program_slug']) ?>">Ver todos los días</a>

    <?php if ($next_day !== null): ?>
        <a class="button button-primary" href="/programas/<?= e($lesson['program_slug']) ?>/dia/<?= e($next_day) ?>">
            Día <?= e($next_day) ?> &rarr;
        </a>
    <?php else: ?>
        <span></span>
    <?php endif; ?>
</nav>
