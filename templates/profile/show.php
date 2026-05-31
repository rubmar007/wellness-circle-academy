<?php
declare(strict_types=1);
/**
 * Perfil del miembro (ver y editar nombre + foto).
 *
 * @var array<string,mixed> $profile
 * @var array<string,string> $errors
 * @var array{type:string,msg:string}|null $flash
 * @var string $csrf
 */
$pageTitle = 'Perfil';
$initial = static function (string $name): string {
    $n = trim($name);
    return $n === '' ? '?' : mb_strtoupper(mb_substr($n, 0, 1));
};
?>
<section class="page-head">
    <h1>Perfil</h1>
    <p class="muted">Tu información y tu foto.</p>
</section>

<?php if ($flash !== null): ?>
    <p class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></p>
<?php endif; ?>

<div class="profile-head">
    <span class="avatar avatar-84">
        <?php if (!empty($profile['photo_url'])): ?>
            <img src="<?= e($profile['photo_url']) ?>" alt="" loading="lazy">
        <?php else: ?>
            <?= e($initial((string) $profile['name'])) ?>
        <?php endif; ?>
    </span>
    <div>
        <h2 class="profile-name"><?= e($profile['name']) ?></h2>
        <p class="muted small">
            <?= e($profile['email']) ?>
            <?= ($profile['role'] ?? '') === 'admin' ? ' · Administrador' : '' ?>
        </p>
    </div>
</div>

<form class="admin-form" method="post" action="/perfil" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <div class="field">
        <label for="name">Nombre</label>
        <input type="text" id="name" name="name" value="<?= e($profile['name']) ?>" required maxlength="120">
        <?php if (!empty($errors['name'])): ?>
            <small class="field-error"><?= e($errors['name']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="photo">Foto de perfil</label>
        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
        <small class="field-hint">JPG, PNG o WebP. Máximo 5 MB. Si no eliges archivo, se conserva la actual.</small>
        <?php if (!empty($errors['photo'])): ?>
            <small class="field-error"><?= e($errors['photo']) ?></small>
        <?php endif; ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="button button-primary">Guardar cambios</button>
    </div>
</form>
