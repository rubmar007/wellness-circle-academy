<?php
declare(strict_types=1);

use App\Controllers\AdminEventsController;

/**
 * @var array<int, array<string,mixed>>    $events
 * @var array{type:string,msg:string}|null $flash
 * @var string $csrf
 */
$pageTitle = 'Eventos';

$meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$types = AdminEventsController::TYPES;
?>
<section class="page-head">
    <p class="breadcrumb"><a href="/admin">Admin</a> &rsaquo; <span>Eventos</span></p>
    <div class="page-head-row">
        <h1>Eventos</h1>
        <a class="button button-primary" href="/admin/eventos/nuevo">Nuevo evento</a>
    </div>
</section>

<?php if ($flash !== null): ?>
    <p class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></p>
<?php endif; ?>

<?php if ($events === []): ?>
    <p class="empty-state">No hay eventos todavía. Crea el primero con "Nuevo evento".</p>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Título</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th class="ta-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $ev): ?>
                    <?php
                    $dt    = new \DateTime((string) $ev['starts_at']);
                    $fecha = $dt->format('j') . ' ' . $meses[(int) $dt->format('n') - 1]
                           . ' ' . $dt->format('Y') . ' ' . $dt->format('H:i');
                    $label = $types[(string) $ev['event_type']] ?? (string) $ev['event_type'];
                    ?>
                    <tr>
                        <td><?= e($fecha) ?></td>
                        <td><?= e($ev['title']) ?></td>
                        <td><span class="badge badge-muted"><?= e($label) ?></span></td>
                        <td>
                            <?= $ev['is_published']
                                ? '<span class="badge badge-success">publicado</span>'
                                : '<span class="badge badge-muted">borrador</span>' ?>
                        </td>
                        <td class="ta-right">
                            <div class="table-actions">
                                <a class="button button-ghost button-sm" href="/admin/eventos/<?= e($ev['id']) ?>/editar">Editar</a>
                                <a class="button button-ghost button-sm button-danger" href="/admin/eventos/<?= e($ev['id']) ?>/eliminar">Eliminar</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
