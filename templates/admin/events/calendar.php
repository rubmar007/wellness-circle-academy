<?php
declare(strict_types=1);

use App\Controllers\AdminEventsController;

/**
 * @var \DateTime $monthStart
 * @var array<int, array{date: \DateTime, inMonth: bool, events: array<int, array<string,mixed>>}> $cells
 * @var string $prevMonth
 * @var string $nextMonth
 */
$pageTitle = 'Calendario de eventos';

$mesesFull = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
];
$diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
$types      = AdminEventsController::TYPES;
$today      = (new \DateTime('today'))->format('Y-m-d');
?>
<section class="page-head">
    <p class="breadcrumb"><a href="/admin">Admin</a> &rsaquo; <a href="/admin/eventos">Eventos</a> &rsaquo; <span>Calendario</span></p>
    <div class="page-head-row">
        <h1><?= e($mesesFull[(int) $monthStart->format('n') - 1]) ?> <?= e($monthStart->format('Y')) ?></h1>
        <div class="table-actions">
            <a class="button button-ghost" href="/admin/eventos">📋 Ver lista</a>
            <a class="button button-primary" href="/admin/eventos/nuevo">Nuevo evento</a>
        </div>
    </div>
</section>

<div class="cal-nav">
    <a class="button button-ghost button-sm" href="/admin/eventos/calendario?mes=<?= e($prevMonth) ?>">&laquo; Mes anterior</a>
    <a class="button button-ghost button-sm" href="/admin/eventos/calendario">Hoy</a>
    <a class="button button-ghost button-sm" href="/admin/eventos/calendario?mes=<?= e($nextMonth) ?>">Mes siguiente &raquo;</a>
</div>

<div class="cal-grid-wrap">
<div class="cal-grid">
    <?php foreach ($diasSemana as $d): ?>
        <div class="cal-weekday"><?= e($d) ?></div>
    <?php endforeach; ?>

    <?php foreach ($cells as $i => $cell): ?>
        <?php
        $dateStr = $cell['date']->format('Y-m-d');
        $events  = $cell['events'];
        $visible = array_slice($events, 0, 3);
        $extra   = array_slice($events, 3);
        ?>
        <div class="cal-day <?= $cell['inMonth'] ? '' : 'cal-day-outside' ?> <?= $dateStr === $today ? 'cal-day-today' : '' ?>">
            <div class="cal-day-head">
                <span class="cal-day-num"><?= e($cell['date']->format('j')) ?></span>
                <a class="cal-day-add" href="/admin/eventos/nuevo?fecha=<?= e($dateStr) ?>" title="Nuevo evento este día">+</a>
            </div>
            <?php if ($events !== []): ?>
                <div class="cal-day-events">
                    <?php foreach ($visible as $ev): ?>
                        <a class="cal-chip cal-chip-<?= e((string) $ev['event_type']) ?> <?= $ev['is_published'] ? '' : 'cal-chip-draft' ?>"
                           href="/admin/eventos/<?= e($ev['id']) ?>/editar"
                           title="<?= e((string) $ev['title']) ?> — <?= e($types[(string) $ev['event_type']] ?? (string) $ev['event_type']) ?>">
                            <span class="cal-chip-time"><?= e((new \DateTime((string) $ev['starts_at']))->format('H:i')) ?></span>
                            <span class="cal-chip-title"><?= e((string) $ev['title']) ?></span>
                        </a>
                    <?php endforeach; ?>

                    <?php if ($extra !== []): ?>
                        <input type="checkbox" id="cal-more-<?= $i ?>" class="cal-more-toggle" hidden>
                        <label for="cal-more-<?= $i ?>" class="cal-chip cal-chip-more">+<?= count($extra) ?> más</label>
                        <div class="cal-day-extra">
                            <?php foreach ($extra as $ev): ?>
                                <a class="cal-chip cal-chip-<?= e((string) $ev['event_type']) ?> <?= $ev['is_published'] ? '' : 'cal-chip-draft' ?>"
                                   href="/admin/eventos/<?= e($ev['id']) ?>/editar"
                                   title="<?= e((string) $ev['title']) ?> — <?= e($types[(string) $ev['event_type']] ?? (string) $ev['event_type']) ?>">
                                    <span class="cal-chip-time"><?= e((new \DateTime((string) $ev['starts_at']))->format('H:i')) ?></span>
                                    <span class="cal-chip-title"><?= e((string) $ev['title']) ?></span>
                                </a>
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
    <span class="cal-legend-item"><span class="cal-legend-dot cal-chip-draft"></span>Borrador (sin publicar)</span>
</p>
