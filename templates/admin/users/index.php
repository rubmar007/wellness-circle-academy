<?php
declare(strict_types=1);
/**
 * @var array<int, array<string,mixed>>      $users
 * @var array{type:string,msg:string}|null   $flash
 * @var string $csrf
 * @var array  $auth
 */
$pageTitle = 'Usuarios';
?>
<section class="page-head">
    <p class="breadcrumb"><a href="/admin">Admin</a> &rsaquo; <span>Usuarios</span></p>
    <div class="page-head-row">
        <h1>Usuarios</h1>
        <a class="button button-primary" href="/admin/usuarios/nuevo">Nuevo usuario</a>
    </div>
</section>

<?php if ($flash !== null): ?>
    <p class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></p>
<?php endif; ?>

<?php if ($users === []): ?>
    <p class="empty-state">No hay usuarios. Crea el primero con "Nuevo usuario".</p>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th class="ta-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= e($u['name']) ?></td>
                        <td><code><?= e($u['email']) ?></code></td>
                        <td>
                            <span class="badge badge-<?= $u['role'] === 'admin' ? 'gold' : 'sky' ?>">
                                <?= e($u['role']) ?>
                            </span>
                        </td>
                        <td>
                            <?= $u['is_active']
                                ? '<span class="badge badge-success">activo</span>'
                                : '<span class="badge badge-muted">inactivo</span>' ?>
                        </td>
                        <td class="ta-right">
                            <a class="button button-ghost button-sm" href="/admin/usuarios/<?= e($u['id']) ?>/editar">Editar</a>
                            <?php if ((int) $u['id'] !== (int) $auth['id']): ?>
                                <form method="post" action="/admin/usuarios/<?= e($u['id']) ?>/toggle" class="inline-form">
                                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                                    <button type="submit" class="button button-ghost button-sm">
                                        <?= $u['is_active'] ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
