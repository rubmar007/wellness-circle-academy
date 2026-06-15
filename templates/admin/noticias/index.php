<?php
declare(strict_types=1);
/**
 * @var array<int, array<string,mixed>>   $noticias
 * @var array{type:string,msg:string}|null $flash
 * @var string $csrf
 */
$pageTitle = 'Noticias';
?>
<section class="page-head">
    <p class="breadcrumb"><a href="/admin">Admin</a> &rsaquo; <span>Noticias</span></p>
    <div class="page-head-row">
        <h1>Noticias</h1>
        <a class="button button-primary" href="/admin/noticias/nueva">Nueva noticia</a>
    </div>
</section>

<?php if ($flash !== null): ?>
    <p class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></p>
<?php endif; ?>

<?php if ($noticias === []): ?>
    <p class="empty-state">No hay noticias todavía.</p>
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
                <?php foreach ($noticias as $n): ?>
                    <tr>
                        <td><?= e($n['title']) ?></td>
                        <td class="muted small">
                            <?php
                            $medios = [];
                            if (!empty($n['image_path'])) $medios[] = 'Imagen';
                            if (!empty($n['video_url']))   $medios[] = 'Video';
                            if (!empty($n['link_url']))    $medios[] = 'Enlace';
                            echo $medios !== [] ? implode(', ', $medios) : '—';
                            ?>
                        </td>
                        <td>
                            <?= $n['is_published']
                                ? '<span class="badge badge-success">publicado</span>'
                                : '<span class="badge badge-muted">borrador</span>' ?>
                        </td>
                        <td class="ta-right">
                            <div class="table-actions">
                                <a class="button button-ghost button-sm" href="/admin/noticias/<?= e($n['id']) ?>/editar">Editar</a>
                                <a class="button button-ghost button-sm button-danger" href="/admin/noticias/<?= e($n['id']) ?>/eliminar">Eliminar</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
