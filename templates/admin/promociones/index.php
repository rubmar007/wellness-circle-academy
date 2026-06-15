<?php
declare(strict_types=1);
/**
 * @var array<int, array<string,mixed>>   $promociones
 * @var array{type:string,msg:string}|null $flash
 * @var string $csrf
 */
$pageTitle = 'Promociones';
?>
<section class="page-head">
    <p class="breadcrumb"><a href="/admin">Admin</a> &rsaquo; <span>Promociones</span></p>
    <div class="page-head-row">
        <h1>Promociones</h1>
        <a class="button button-primary" href="/admin/promociones/nueva">Nueva promoción</a>
    </div>
</section>

<?php if ($flash !== null): ?>
    <p class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></p>
<?php endif; ?>

<?php if ($promociones === []): ?>
    <p class="empty-state">No hay promociones todavía.</p>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Medios</th>
                    <th>Estado</th>
                    <th class="ta-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($promociones as $p): ?>
                    <tr>
                        <td><?= e($p['title']) ?></td>
                        <td class="muted small">
                            <?php
                            $medios = [];
                            if (!empty($p['image_path'])) $medios[] = 'Imagen';
                            if (!empty($p['video_url']))   $medios[] = 'Video';
                            if (!empty($p['link_url']))    $medios[] = 'Enlace';
                            echo $medios !== [] ? implode(', ', $medios) : '—';
                            ?>
                        </td>
                        <td>
                            <?= $p['is_published']
                                ? '<span class="badge badge-success">publicado</span>'
                                : '<span class="badge badge-muted">borrador</span>' ?>
                        </td>
                        <td class="ta-right">
                            <div class="table-actions">
                                <a class="button button-ghost button-sm" href="/admin/promociones/<?= e($p['id']) ?>/editar">Editar</a>
                                <a class="button button-ghost button-sm button-danger" href="/admin/promociones/<?= e($p['id']) ?>/eliminar">Eliminar</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
