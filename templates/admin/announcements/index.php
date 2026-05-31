<?php
declare(strict_types=1);
/**
 * @var array<int, array<string,mixed>>                 $announcements
 * @var array<string, array{label:string, emoji:string}> $kinds
 * @var array{type:string,msg:string}|null              $flash
 * @var string $csrf
 */
$pageTitle = 'Notificaciones';
?>
<section class="page-head">
    <p class="breadcrumb"><a href="/admin">Admin</a> &rsaquo; <span>Notificaciones</span></p>
    <div class="page-head-row">
        <h1>Notificaciones</h1>
        <a class="button button-primary" href="/admin/notificaciones/nueva">Nueva notificación</a>
    </div>
</section>

<?php if ($flash !== null): ?>
    <p class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></p>
<?php endif; ?>

<?php if ($announcements === []): ?>
    <p class="empty-state">No hay notificaciones todavía. Crea la primera con "Nueva notificación".</p>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Título</th>
                    <th>Estado</th>
                    <th class="ta-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($announcements as $a): ?>
                    <?php $label = $kinds[(string) $a['kind']]['label'] ?? (string) $a['kind']; ?>
                    <tr>
                        <td><?= e($label) ?></td>
                        <td><?= e($a['title']) ?></td>
                        <td>
                            <?= $a['is_published']
                                ? '<span class="badge badge-success">publicado</span>'
                                : '<span class="badge badge-muted">borrador</span>' ?>
                        </td>
                        <td class="ta-right">
                            <div class="table-actions">
                                <a class="button button-ghost button-sm" href="/admin/notificaciones/<?= e($a['id']) ?>/editar">Editar</a>
                                <a class="button button-ghost button-sm button-danger" href="/admin/notificaciones/<?= e($a['id']) ?>/eliminar">Eliminar</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
