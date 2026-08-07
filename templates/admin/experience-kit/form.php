<?php
declare(strict_types=1);
/**
 * @var array<int, array<string,mixed>> $clientes
 * @var array<string,string>            $kitLabels
 * @var array<string,string>            $errors
 * @var array<string,string>            $old
 * @var string $csrf
 */
$pageTitle = 'Asignar kit';
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/experience-kit">WCA Experience Kit</a> &rsaquo;
        <span>Asignar kit</span>
    </p>
    <h1>Asignar kit a un cliente</h1>
</section>

<?php if ($clientes === []): ?>
    <p class="empty-state">No hay clientes disponibles sin kit activo. Crea la cuenta desde <a href="/admin/usuarios/nuevo">Usuarios</a> con rol Cliente, o finaliza el kit activo del cliente que buscas.</p>
<?php else: ?>
<form method="post" action="/admin/experience-kit" class="admin-form" novalidate>
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <div class="field">
        <label for="user_id">Cliente</label>
        <select id="user_id" name="user_id" required>
            <option value="">— Selecciona un cliente —</option>
            <?php foreach ($clientes as $c): ?>
                <option value="<?= e($c['id']) ?>" <?= ($old['user_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['name']) ?> (<?= e($c['email']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['user_id'])): ?>
            <small class="field-error"><?= e($errors['user_id']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="kit_slug">Kit</label>
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

    <div class="field-row">
        <div class="field">
            <label for="weight_kg">Peso (kg, opcional)</label>
            <input
                type="number"
                id="weight_kg"
                name="weight_kg"
                value="<?= e($old['weight_kg'] ?? '') ?>"
                step="0.1"
                min="1"
                max="400">
            <small class="field-hint">Se usa para calcular la meta diaria de hidratación. Sin este dato se usa una meta genérica de 8 vasos.</small>
            <?php if (!empty($errors['weight_kg'])): ?>
                <small class="field-error"><?= e($errors['weight_kg']) ?></small>
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
    </div>

    <div class="form-actions">
        <a class="button button-ghost" href="/admin/experience-kit">Cancelar</a>
        <button type="submit" class="button button-primary">Asignar kit</button>
    </div>
</form>
<?php endif; ?>
