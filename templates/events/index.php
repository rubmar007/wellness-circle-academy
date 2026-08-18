<?php
declare(strict_types=1);

use App\Controllers\AdminEventsController;

/**
 * @var array<int, array<string,mixed>> $events
 * @var string $vista
 * @var \DateTime $monthStart
 * @var array<int, array{date: \DateTime, inMonth: bool, events: array<int, array<string,mixed>>}> $cells
 * @var string $prevMonth
 * @var string $nextMonth
 */
$pageTitle = 'Eventos';

$meses     = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$mesesFull = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
];
$diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
$types      = AdminEventsController::TYPES;
$todayStr   = (new \DateTime('today'))->format('Y-m-d');
?>
<section class="page-head">
    <h1>Eventos</h1>
</section>

<div>
    <input type="radio" name="evf" id="evf-todos" checked>
    <input type="radio" name="evf" id="evf-taller">
    <input type="radio" name="evf" id="evf-entrenamiento">
    <input type="radio" name="evf" id="evf-oportunidad">
    <input type="radio" name="evv" id="ev-semana" <?= $vista === 'semana' ? 'checked' : '' ?>>
    <input type="radio" name="evv" id="ev-mes"    <?= $vista === 'mes'    ? 'checked' : '' ?>>

    <div class="event-filters">
        <label class="event-filter-label" for="evf-todos">Todos</label>
        <label class="event-filter-label" for="evf-taller">Taller</label>
        <label class="event-filter-label" for="evf-entrenamiento">Entrenamiento</label>
        <label class="event-filter-label" for="evf-oportunidad">Oportunidad</label>
    </div>

    <div class="event-view-toggle">
        <label class="event-view-label" for="ev-semana">Vista Semanal</label>
        <label class="event-view-label" for="ev-mes">Mes completo</label>
    </div>

    <div class="events-week-section">
        <?php if ($events === []): ?>
            <p class="section-empty">No hay eventos programados esta semana.</p>
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

    <div class="events-month-section">
        <div class="cal-nav">
            <h2 class="cal-month-title"><?= e($mesesFull[(int) $monthStart->format('n') - 1]) ?> <?= e($monthStart->format('Y')) ?></h2>
            <a class="button button-ghost button-sm" href="/eventos?vista=mes&amp;mes=<?= e($prevMonth) ?>">&laquo; Mes anterior</a>
            <a class="button button-ghost button-sm" href="/eventos?vista=mes">Hoy</a>
            <a class="button button-ghost button-sm" href="/eventos?vista=mes&amp;mes=<?= e($nextMonth) ?>">Mes siguiente &raquo;</a>
        </div>

        <div class="cal-grid-wrap">
        <div class="cal-grid">
            <?php foreach ($diasSemana as $d): ?>
                <div class="cal-weekday"><?= e($d) ?></div>
            <?php endforeach; ?>

            <?php foreach ($cells as $cell): ?>
                <?php
                $dateStr = $cell['date']->format('Y-m-d');
                $cellEvents = $cell['events'];
                $visible = array_slice($cellEvents, 0, 3);
                $extra   = array_slice($cellEvents, 3);
                ?>
                <div class="cal-day <?= $cell['inMonth'] ? '' : 'cal-day-outside' ?> <?= $dateStr === $todayStr ? 'cal-day-today' : '' ?>">
                    <div class="cal-day-head">
                        <span class="cal-day-num"><?= e($cell['date']->format('j')) ?></span>
                    </div>
                    <?php if ($cellEvents !== []): ?>
                        <div class="cal-day-events">
                            <?php foreach ($visible as $ev): ?>
                                <?php $chipTitle = (string) $ev['title'] . ' — ' . ($types[(string) $ev['event_type']] ?? (string) $ev['event_type']); ?>
                                <?php if (!empty($ev['join_url'])): ?>
                                    <a class="cal-chip cal-chip-<?= e((string) $ev['event_type']) ?>" href="<?= e((string) $ev['join_url']) ?>" target="_blank" rel="noopener noreferrer" title="<?= e($chipTitle) ?>">
                                        <span class="cal-chip-time"><?= e((new \DateTime((string) $ev['starts_at']))->format('H:i')) ?></span>
                                        <span class="cal-chip-title"><?= e((string) $ev['title']) ?></span>
                                    </a>
                                <?php else: ?>
                                    <span class="cal-chip cal-chip-<?= e((string) $ev['event_type']) ?>" title="<?= e($chipTitle) ?>">
                                        <span class="cal-chip-time"><?= e((new \DateTime((string) $ev['starts_at']))->format('H:i')) ?></span>
                                        <span class="cal-chip-title"><?= e((string) $ev['title']) ?></span>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <?php if ($extra !== []): ?>
                                <input type="checkbox" id="cal-more-<?= e($dateStr) ?>" class="cal-more-toggle" hidden>
                                <label for="cal-more-<?= e($dateStr) ?>" class="cal-chip cal-chip-more">+<?= count($extra) ?> más</label>
                                <div class="cal-day-extra">
                                    <?php foreach ($extra as $ev): ?>
                                        <?php $chipTitle = (string) $ev['title'] . ' — ' . ($types[(string) $ev['event_type']] ?? (string) $ev['event_type']); ?>
                                        <?php if (!empty($ev['join_url'])): ?>
                                            <a class="cal-chip cal-chip-<?= e((string) $ev['event_type']) ?>" href="<?= e((string) $ev['join_url']) ?>" target="_blank" rel="noopener noreferrer" title="<?= e($chipTitle) ?>">
                                                <span class="cal-chip-time"><?= e((new \DateTime((string) $ev['starts_at']))->format('H:i')) ?></span>
                                                <span class="cal-chip-title"><?= e((string) $ev['title']) ?></span>
                                            </a>
                                        <?php else: ?>
                                            <span class="cal-chip cal-chip-<?= e((string) $ev['event_type']) ?>" title="<?= e($chipTitle) ?>">
                                                <span class="cal-chip-time"><?= e((new \DateTime((string) $ev['starts_at']))->format('H:i')) ?></span>
                                                <span class="cal-chip-title"><?= e((string) $ev['title']) ?></span>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        </div>

        <p class="cal-legend">
            <?php foreach ($types as $slug => $label): ?>
                <span class="cal-legend-item"><span class="cal-legend-dot cal-chip-<?= e($slug) ?>"></span><?= e($label) ?></span>
            <?php endforeach; ?>
        </p>
    </div>
</div>
