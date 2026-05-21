<?php
declare(strict_types=1);
/**
 * @var array<string,mixed>            $program
 * @var bool                           $success
 * @var array<int,string>              $errors
 * @var array<int, array{day:int,title:string}> $created
 * @var array<int, array{day:int,title:string}> $updated
 */
$pageTitle = $success ? 'Importación completada' : 'Importación con errores';
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/programas">Programas</a> &rsaquo;
        <a href="/admin/programas/<?= e($program['id']) ?>/lecciones"><?= e($program['title']) ?></a> &rsaquo;
        <span>Resultado batch</span>
    </p>
    <h1><?= e($pageTitle) ?></h1>
</section>

<?php if ($success): ?>
    <p class="alert alert-success">
        Importación completada con éxito:
        <strong><?= e(count($created)) ?></strong> creadas,
        <strong><?= e(count($updated)) ?></strong> actualizadas.
    </p>

    <?php if ($created !== []): ?>
        <article class="batch-card">
            <h2>Lecciones creadas (<?= e(count($created)) ?>)</h2>
            <ul>
                <?php foreach ($created as $row): ?>
                    <li>Día <?= e($row['day']) ?> — <?= e($row['title']) ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
    <?php endif; ?>

    <?php if ($updated !== []): ?>
        <article class="batch-card">
            <h2>Lecciones actualizadas (<?= e(count($updated)) ?>)</h2>
            <ul>
                <?php foreach ($updated as $row): ?>
                    <li>Día <?= e($row['day']) ?> — <?= e($row['title']) ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
    <?php endif; ?>

<?php else: ?>
    <div class="alert alert-error">
        <strong>No se importó nada.</strong> La transacción se canceló porque el archivo
        contiene errores. Corrígelos en el XLSX y vuelve a subirlo.
    </div>

    <article class="batch-card">
        <h2>Errores detectados (<?= e(count($errors)) ?>)</h2>
        <ul class="batch-errors">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </article>
<?php endif; ?>

<div class="form-actions">
    <a class="button button-ghost" href="/admin/programas/<?= e($program['id']) ?>/lecciones">Ir a lecciones</a>
    <a class="button button-primary" href="/admin/programas/<?= e($program['id']) ?>/batch">
        <?= $success ? 'Subir otro archivo' : 'Volver a intentar' ?>
    </a>
</div>
