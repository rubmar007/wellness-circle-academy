<?php
declare(strict_types=1);
/**
 * @var array<string,mixed>  $page
 * @var array<string,string> $errors
 * @var array{type:string,msg:string}|null $flash
 * @var string $csrf
 */
$pageTitle = 'Soy Cliente — Contenido';

$imgField = function (string $name, string $label, ?string $current) use ($errors): void {
    ?>
    <div class="field">
        <label><?= e($label) ?></label>
        <?php if (!empty($current)): ?>
            <img src="<?= e($current) ?>" alt="<?= e($label) ?>" style="max-width:200px;display:block;margin-bottom:.5rem;">
        <?php endif; ?>
        <input type="file" name="<?= e($name) ?>" accept="image/jpeg,image/png,image/webp">
        <small class="field-hint">JPG, PNG o WebP. Max 5 MB. Deja vacío para conservar la imagen actual.</small>
        <?php if (!empty($errors[$name])): ?>
            <small class="field-error"><?= e($errors[$name]) ?></small>
        <?php endif; ?>
    </div>
    <?php
};

$textField = function (string $name, string $label, ?string $value) use ($errors): void {
    ?>
    <div class="field">
        <label for="<?= e($name) ?>"><?= e($label) ?></label>
        <textarea id="<?= e($name) ?>" name="<?= e($name) ?>" rows="4"><?= e($value ?? '') ?></textarea>
        <?php if (!empty($errors[$name])): ?>
            <small class="field-error"><?= e($errors[$name]) ?></small>
        <?php endif; ?>
    </div>
    <?php
};

$videoField = function (string $name, string $label, ?string $value) use ($errors): void {
    ?>
    <div class="field">
        <label for="<?= e($name) ?>"><?= e($label) ?></label>
        <input type="url" id="<?= e($name) ?>" name="<?= e($name) ?>" value="<?= e($value ?? '') ?>" placeholder="https://www.youtube.com/watch?v=...">
        <small class="field-hint">URL de YouTube o Vimeo. Deja vacío si no hay video.</small>
        <?php if (!empty($errors[$name])): ?>
            <small class="field-error"><?= e($errors[$name]) ?></small>
        <?php endif; ?>
    </div>
    <?php
};
?>

<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <span>Soy Cliente</span>
    </p>
    <h1>Soy Cliente — Contenido</h1>
</section>

<?php if ($flash !== null): ?>
    <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
<?php endif; ?>

<form method="post" action="/admin/cliente" class="admin-form" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <h2 class="form-section-title">Imagen de bienvenida</h2>
    <?php $imgField('welcome_image', 'Imagen de bienvenida', $page['welcome_image_url'] ?? null); ?>

    <h2 class="form-section-title">Como utilizar el producto / Importancia de la Hidratacion</h2>
    <?php $textField('uso_texto', 'Texto', $page['uso_texto'] ?? null); ?>

    <h2 class="form-section-title">PDF — Guia de uso (opcional)</h2>
    <div class="field">
        <label>Archivo PDF</label>
        <?php if (!empty($page['uso_pdf_url'])): ?>
            <p class="field-hint">PDF actual: <a href="<?= e($page['uso_pdf_url']) ?>" target="_blank">ver / descargar</a></p>
        <?php endif; ?>
        <input type="file" name="uso_pdf" accept="application/pdf">
        <small class="field-hint">Solo PDF. Max 5 MB. Deja vacío para conservar el PDF actual.</small>
        <?php if (!empty($errors['uso_pdf'])): ?>
            <small class="field-error"><?= e($errors['uso_pdf']) ?></small>
        <?php endif; ?>
    </div>

    <h2 class="form-section-title">Como activar autoenvio</h2>
    <?php $textField('activar_texto', 'Texto', $page['activar_texto'] ?? null); ?>
    <?php $videoField('activar_video_url', 'Video', $page['activar_video_url'] ?? null); ?>

    <h2 class="form-section-title">Como desactivar autoenvio</h2>
    <?php $textField('desactivar_texto', 'Texto', $page['desactivar_texto'] ?? null); ?>
    <?php $videoField('desactivar_video_url', 'Video', $page['desactivar_video_url'] ?? null); ?>

    <h2 class="form-section-title">Como convertirte en cliente preferente plus</h2>
    <?php $textField('preferente_texto', 'Texto', $page['preferente_texto'] ?? null); ?>
    <?php $videoField('preferente_video_url', 'Video', $page['preferente_video_url'] ?? null); ?>

    <h2 class="form-section-title">Beneficios del autoenvio / Regalos</h2>
    <?php $imgField('beneficios_autoenvio', 'Imagen', $page['beneficios_autoenvio_url'] ?? null); ?>

    <h2 class="form-section-title">Beneficios del cliente preferente plus</h2>
    <?php $imgField('beneficios_preferente', 'Imagen', $page['beneficios_preferente_url'] ?? null); ?>

    <h2 class="form-section-title">Texto libre (opcional)</h2>
    <?php $textField('texto_libre', 'Texto (no aparece si esta vacio)', $page['texto_libre'] ?? null); ?>

    <div class="form-actions">
        <a class="button button-ghost" href="/admin">Cancelar</a>
        <button type="submit" class="button button-primary">Guardar</button>
    </div>
</form>
