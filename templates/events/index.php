<?php
declare(strict_types=1);

use App\Controllers\AdminEventsController;

/**
 * @var array<int, array<string,mixed>> $events
 */
$pageTitle = 'Eventos';

$meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$types = AdminEventsController::TYPES;
?>
<section class="page-head">
    <h1>Eventos</h1>
</section>

<div>
    <input type="radio" name="evf" id="evf-todos" checked>
    <input type="radio" name="evf" id="evf-taller">
    <input type="radio" name="evf" id="evf-entrenamiento">
    <input type="radio" name="evf" id="evf-oportunidad">

    <div class="event-filters">
        <label class="event-filter-label" for="evf-todos">Todos</label>
        <label class="event-filter-label" for="evf-taller">Taller</label>
        <label class="event-filter-label" for="evf-entrenamiento">Entrenamiento</label>
        <label class="event-filter-label" for="evf-oportunidad">Oportunidad</label>
    </div>

    <?php if ($events === []): ?>
        <p class="section-empty">No hay eventos próximos.</p>
    <?php else: ?>
        <div class="events-list">
            <?php foreach ($events as $ev): ?>
                <?php
                $dt    = new \DateTime((string) $ev['starts_at']);
                $dia   = $dt->format('j');
                $mes   = $meses[(int) $dt->format('n') - 1];
                $hora  = $dt->format('H:i');
                $type  = (string) $ev['event_type'];
                $label = $types[$type] ?? $type;
                $joinUrl = (string) ($ev['join_url'] ?? '');
                $desc    = (string) ($ev['description'] ?? '');
                ?>
                <article class="event-card type-<?= e($type) ?>">
                    <?php if (!empty($ev['image_url'])): ?>
                        <img class="event-img" src="<?= e($ev['image_url']) ?>" alt="<?= e($ev['title']) ?>" loading="lazy">
                    <?php endif; ?>
                    <div class="event-row">
                        <div class="event-date">
                            <span class="d"><?= e($dia) ?></span>
                            <span class="m"><?= e($mes) ?></span>
                        </div>
                        <div>
                            <span class="event-tag"><?= e($label) ?></span>
                            <h2 class="event-title"><?= e($ev['title']) ?></h2>
                            <p class="muted small"><?= e($dia) ?> <?= e($mes) ?> &middot; <?= e($hora) ?></p>
                            <?php if ($desc !== ''): ?>
                                <p class="small"><?= e_nl2br($desc) ?></p>
                            <?php endif; ?>
                            <?php if ($joinUrl !== ''): ?>
                                <a class="button button-primary button-sm" href="<?= e($joinUrl) ?>" target="_blank" rel="noopener noreferrer">Entrar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
