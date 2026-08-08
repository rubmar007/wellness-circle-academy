<?php
declare(strict_types=1);
/**
 * @var string                            $tab       'activos' | 'completados'
 * @var array<int, array<string,mixed>>   $rows
 * @var array<string,string>              $kitLabels
 * @var array{activos:int,completados:int,personas:int} $totals
 */
$pageTitle = 'Mis Experience';

$statusLabels = [
    'al_dia'      => ['label' => 'Al día', 'class' => 'badge-success'],
    'seguimiento' => ['label' => 'Requiere seguimiento', 'class' => 'badge-error'],
    'completado'  => ['label' => 'Completado', 'class' => 'badge-gold'],
];
?>
<section class="page-head">
    <h1>Mis Experience</h1>
    <p class="muted">Seguimiento de solo lectura de las personas que acompañas. El registro diario lo hace cada quien desde su propio Mi Kit.</p>
</section>

<div class="stats-row">
    <div class="stat-card">
        <span class="stat-card-value"><?= e($totals['activos']) ?></span>
        <span class="stat-card-label">activos</span>
    </div>
    <div class="stat-card">
        <span class="stat-card-value"><?= e($totals['completados']) ?></span>
        <span class="stat-card-label">completados</span>
    </div>
    <div class="stat-card">
        <span class="stat-card-value"><?= e($totals['personas']) ?></span>
        <span class="stat-card-label">personas acompañadas</span>
    </div>
</div>

<div class="bm-pillrow" role="group" aria-label="Filtro">
    <a href="/mis-experience?tab=activos" class="bm-pill <?= $tab === 'activos' ? 'is-active-pill' : '' ?>">Activos</a>
    <a href="/mis-experience?tab=completados" class="bm-pill <?= $tab === 'completados' ? 'is-active-pill' : '' ?>">Completados</a>
</div>

<?php if ($rows === []): ?>
    <p class="section-empty">
        <?= $tab === 'activos' ? 'No tienes Experience activos asignados por ahora.' : 'Todavía no tienes Experience completados.' ?>
    </p>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Experience</th>
                    <th>Día</th>
                    <th>Parche</th>
                    <th>Ejercicio</th>
                    <th>Diario</th>
                    <th>Estado</th>
                    <th class="ta-right">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): $kit = $r['kit']; $st = $statusLabels[$r['status']]; ?>
                    <tr>
                        <td>
                            <?= e($kit['name']) ?><br>
                            <span class="muted small"><?= e($kit['email']) ?></span>
                        </td>
                        <td><?= e($kitLabels[$kit['kit_slug']] ?? $kit['kit_slug']) ?></td>
                        <td><?= e($r['dayNumber']) ?>/7</td>
                        <td>
                            <?= $r['patchApplied']
                                ? '<span class="badge badge-success">sí</span>'
                                : '<span class="badge badge-muted">no</span>' ?>
                        </td>
                        <td>
                            <?= $r['exerciseDone']
                                ? '<span class="badge badge-success">sí</span>'
                                : '<span class="badge badge-muted">no</span>' ?>
                        </td>
                        <td>
                            <?= $r['diaryAnswered']
                                ? '<span class="badge badge-success">sí</span>'
                                : '<span class="badge badge-muted">no</span>' ?>
                        </td>
                        <td><span class="badge <?= $st['class'] ?>"><?= e($st['label']) ?></span></td>
                        <td class="ta-right">
                            <a class="button button-ghost button-sm" href="/mis-experience/<?= e($kit['id']) ?>">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
