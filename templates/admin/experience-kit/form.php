<?php
declare(strict_types=1);
/**
 * @var string                          $mode      'create' | 'edit'
 * @var array<string,mixed>|null        $kit       fila de client_kits (solo en modo edit)
 * @var array<int, array<string,mixed>> $clientes  candidatos sin kit activo (solo en modo create)
 * @var array<int, array<string,mixed>> $promoters usuarios role=member elegibles como Promotor responsable
 * @var array<string,string>            $kitLabels
 * @var array<string,string>            $errors
 * @var array<string,string>            $old
 * @var string $csrf
 */
$isCreate  = $mode === 'create';
$pageTitle = $isCreate ? 'Asignar kit' : 'Editar kit';
$action    = $isCreate ? '/admin/experience-kit' : '/admin/experience-kit/' . (int) $kit['id'];
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/experience-kit">WCA Experience Kit</a> &rsaquo;
        <span><?= $isCreate ? 'Asignar kit' : 'Editar kit' ?></span>
    </p>
    <h1><?= $isCreate ? 'Asignar kit a un cliente o promotor' : 'Editar kit de ' . e($kit['name']) ?></h1>
</section>

<?php if ($isCreate && $clientes === []): ?>
    <p class="empty-state">No hay clientes ni promotores disponibles sin kit activo. Crea la cuenta desde <a href="/admin/usuarios/nuevo">Usuarios</a> con rol Cliente o Member, o finaliza el kit activo de quien buscas.</p>
<?php else: ?>
<form method="post" action="<?= e($action) ?>" class="admin-form" novalidate>
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <?php if ($isCreate): ?>
        <div class="field">
            <label for="user_id">Cliente / Participante</label>
            <select id="user_id" name="user_id" required>
                <option value="">— Selecciona una persona —</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= e($c['id']) ?>" <?= ($old['user_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?> (<?= e($c['email']) ?>) — <?= $c['role'] === 'cliente' ? 'Cliente' : 'Promotor' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['user_id'])): ?>
                <small class="field-error"><?= e($errors['user_id']) ?></small>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="field">
            <label>Cliente / Participante</label>
            <p><?= e($kit['name']) ?> (<?= e($kit['email']) ?>) — <?= $kit['role'] === 'cliente' ? 'Cliente' : 'Promotor' ?></p>
            <small class="field-hint">Para reasignar el kit a otra persona, elimina este kit y crea uno nuevo.</small>
        </div>
    <?php endif; ?>

    <div class="field">
        <label for="kit_slug">Experience Kit</label>
        <select id="kit_slug" name="kit_slug" required>
            <option value="">— Selecciona un kit —</option>
            <?php foreach ($kitLabels as $slug => $label): ?>
                <option value="<?= e($slug) ?>" <?= ($old['kit_slug'] ?? '') === $slug ? 'selected' : '' ?>>
                    <?= e($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['kit_slug'])): ?>
            <small class="field-error"><?= e($errors['kit_slug']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="promoter_id">Promotor / Asesor responsable</label>
        <select id="promoter_id" name="promoter_id">
            <option value="">— Sin asignar —</option>
            <?php foreach ($promoters as $p): ?>
                <option value="<?= e($p['id']) ?>" <?= ($old['promoter_id'] ?? '') === (string) $p['id'] ? 'selected' : '' ?>>
                    <?= e($p['name']) ?> (<?= e($p['email']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <small class="field-hint">Quien da seguimiento de solo lectura a este Experience desde «Mis Experience». Opcional, y se puede cambiar después.</small>
        <?php if (!empty($errors['promoter_id'])): ?>
            <small class="field-error"><?= e($errors['promoter_id']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="started_at">Día 1 del kit</label>
        <input
            type="date"
            id="started_at"
            name="started_at"
            value="<?= e($old['started_at'] ?? date('Y-m-d')) ?>"
            required>
        <?php if (!empty($errors['started_at'])): ?>
            <small class="field-error"><?= e($errors['started_at']) ?></small>
        <?php endif; ?>
    </div>

    <p class="field-hint">El peso no se captura aquí — lo registra directamente la persona desde «Mi Kit» y su meta de hidratación se calcula con ese dato.</p>

    <div class="form-actions">
        <a class="button button-ghost" href="/admin/experience-kit">Cancelar</a>
        <button type="submit" class="button button-primary">
            <?= $isCreate ? 'Asignar kit' : 'Guardar cambios' ?>
        </button>
    </div>
</form>
<?php endif; ?>
