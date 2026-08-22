<?php
declare(strict_types=1);
/**
 * @var array<int, array<string,mixed>>      $rows
 * @var array<string,string>                 $kitLabels
 * @var array{type:string,msg:string}|null   $flash
 * @var string $csrf
 */
$pageTitle = 'WCA Experience Kit';
?>
<section class="page-head">
    <p class="breadcrumb"><a href="/admin">Admin</a> &rsaquo; <span>WCA Experience Kit</span></p>
    <div class="page-head-row">
        <h1>WCA Experience Kit</h1>
        <a class="button button-primary" href="/admin/experience-kit/nuevo">Asignar kit</a>
    </div>
    <p class="muted">Seguimiento en vivo de los kits activos. "Falta seguimiento" = sin registro de agua en las últimas 4 horas.</p>
</section>

<?php if ($flash !== null): ?>
    <p class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></p>
<?php endif; ?>

<?php if ($rows === []): ?>
    <p class="empty-state">No hay kits activos todavía. Asigna el primero con "Asignar kit".</p>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Cliente / Participante</th>
                    <th>Promotor responsable</th>
                    <th>Kit</th>
                    <th>Día</th>
                    <th>Parche hoy</th>
                    <th>Agua</th>
                    <th>Ejercicio</th>
                    <th>Diario</th>
                    <th class="ta-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): $kit = $r['kit']; ?>
                    <tr>
                        <td>
                            <?= e($kit['name']) ?>
                            <span class="badge badge-muted"><?= match ($kit['role']) { 'cliente' => 'cliente', 'admin' => 'admin', default => 'promotor' } ?></span><br>
                            <span class="muted small"><?= e($kit['email']) ?></span>
                        </td>
                        <td>
                            <?= $kit['promoter_name'] !== null
                                ? e($kit['promoter_name'])
                                : '<span class="muted">Sin asignar</span>' ?>
                        </td>
                        <td><?= e($kitLabels[$kit['kit_slug']] ?? $kit['kit_slug']) ?></td>
                        <td>
                            <?= e($r['dayNumber']) ?>/7
                            <?php if ($r['isCompleted']): ?>
                                <br><span class="badge badge-gold small">Completado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $r['patchApplied']
                                ? '<span class="badge badge-success">sí</span>'
                                : '<span class="badge badge-muted">no</span>' ?>
                        </td>
                        <td>
                            <?php if ($r['needsFollowUp']): ?>
                                <span class="badge badge-error">le falta seguimiento</span>
                            <?php else: ?>
                                <span class="badge badge-success">hace <?= e($r['hoursSinceWater']) ?>h</span>
                            <?php endif; ?>
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
                        <td class="ta-right">
                            <div class="table-actions">
                                <a class="button button-ghost button-sm" href="/admin/experience-kit/<?= e($kit['id']) ?>/editar">Editar</a>
                                <form method="post" action="/admin/experience-kit/<?= e($kit['id']) ?>/finalizar" class="inline-form">
                                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                                    <button type="submit" class="button button-ghost button-sm">Finalizar</button>
                                </form>
                                <a class="button button-ghost button-sm button-danger" href="/admin/experience-kit/<?= e($kit['id']) ?>/eliminar">Eliminar</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
