<?php
declare(strict_types=1);
/**
 * @var array<string,mixed>  $program
 * @var array<int,string>    $errors
 * @var int                  $max_upload_mb
 * @var int                  $header_count
 * @var array<int,string>    $headers_human
 * @var string               $csrf
 */
$pageTitle = 'Cargar batch — ' . (string) $program['title'];
?>
<section class="page-head">
    <p class="breadcrumb">
        <a href="/admin">Admin</a> &rsaquo;
        <a href="/admin/programas">Programas</a> &rsaquo;
        <a href="/admin/programas/<?= e($program['id']) ?>/lecciones"><?= e($program['title']) ?></a> &rsaquo;
        <span>Cargar batch</span>
    </p>
    <h1>Cargar lecciones desde XLSX</h1>
    <p class="muted">
        Sube un archivo Excel (.xlsx) con las lecciones del programa
        <strong><?= e($program['title']) ?></strong>. Si el día ya existe se actualiza,
        si no se crea. No se borra nada.
    </p>
</section>

<?php if ($errors !== []): ?>
    <div class="alert alert-error">
        <strong>Hay errores:</strong>
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<section class="batch-grid">
    <article class="batch-card">
        <h2>1. Descargar plantilla</h2>
        <p>Baja la plantilla en blanco con los encabezados correctos y un ejemplo del Día 1 ya cargado.</p>
        <p>Puedes abrirla con <strong>Google Sheets</strong> (Archivo → Importar) o Microsoft Excel.</p>
        <p>
            <a class="button button-primary"
               href="/admin/programas/<?= e($program['id']) ?>/batch/plantilla">
                Descargar plantilla XLSX
            </a>
        </p>
    </article>

    <article class="batch-card">
        <h2>2. Subir XLSX rellenado</h2>
        <form method="post" action="/admin/programas/<?= e($program['id']) ?>/batch"
              enctype="multipart/form-data" class="admin-form" novalidate>
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

            <div class="field">
                <label for="xlsx">Archivo .xlsx</label>
                <input
                    type="file"
                    id="xlsx"
                    name="xlsx"
                    accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                    required>
                <small class="field-hint">Máximo <?= e($max_upload_mb) ?> MB. Solo se procesa la primera hoja.</small>
            </div>

            <div class="form-actions">
                <a class="button button-ghost"
                   href="/admin/programas/<?= e($program['id']) ?>/lecciones">Cancelar</a>
                <button type="submit" class="button button-primary">Procesar archivo</button>
            </div>
        </form>
    </article>
</section>

<section class="batch-format">
    <h2>Formato esperado del archivo</h2>
    <p>La <strong>fila 1</strong> son los encabezados (los nombres no importan, sí el <strong>orden</strong> de las columnas).</p>
    <ol class="batch-columns">
        <?php foreach ($headers_human as $i => $header): ?>
            <li>
                <strong>Columna <?= chr(65 + (int) $i) ?>:</strong>
                <?= e($header) ?>
            </li>
        <?php endforeach; ?>
    </ol>
    <p class="muted small">
        <strong>Checklist:</strong> separa los ítems con el carácter <code>|</code> o con saltos de línea dentro de la misma celda.
        <strong>Publicado:</strong> usa <code>1</code> o <code>sí</code> para publicar, <code>0</code> o vacío para dejarlo en borrador.
        Las filas completamente vacías se ignoran.
    </p>
</section>
