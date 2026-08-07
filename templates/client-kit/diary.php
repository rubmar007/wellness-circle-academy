<?php
declare(strict_types=1);
/**
 * @var array<string,mixed> $kit
 * @var string $kitLabel
 * @var int $dayNumber
 * @var array<string,string> $baseFields
 * @var array<string, array{label:string,type:string}> $extraFields
 * @var array<string,mixed> $diary
 * @var string $notes
 * @var array{type:string,msg:string}|null $flash
 * @var string $csrf
 */
$pageTitle = 'Diario del día';
?>
<section class="page-head">
    <p class="breadcrumb"><a href="/mi-kit">Mi Kit</a> &rsaquo; <span>Diario</span></p>
    <h1>Diario / encuesta — <?= e($kitLabel) ?>, día <?= e($dayNumber) ?></h1>
</section>

<?php if ($flash !== null): ?>
    <p class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></p>
<?php endif; ?>

<form method="post" action="/mi-kit/diario" class="admin-form" novalidate>
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <fieldset class="field-group">
        <legend>Campos base</legend>
        <?php foreach ($baseFields as $key => $label): ?>
            <div class="field">
                <label for="diary_<?= e($key) ?>"><?= e($label) ?> (1-10)</label>
                <select id="diary_<?= e($key) ?>" name="diary[<?= e($key) ?>]">
                    <option value="">—</option>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?= $i ?>" <?= (($diary[$key] ?? null) == $i) ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        <?php endforeach; ?>
    </fieldset>

    <?php if ($extraFields !== []): ?>
        <fieldset class="field-group">
            <legend>Campos específicos de tu kit</legend>
            <?php foreach ($extraFields as $key => $spec): ?>
                <div class="field">
                    <label for="diary_<?= e($key) ?>"><?= e($spec['label']) ?></label>
                    <?php if ($spec['type'] === 'number'): ?>
                        <input type="number" id="diary_<?= e($key) ?>" name="diary[<?= e($key) ?>]"
                               step="0.1" value="<?= e((string) ($diary[$key] ?? '')) ?>">
                    <?php else: ?>
                        <select id="diary_<?= e($key) ?>" name="diary[<?= e($key) ?>]">
                            <option value="">—</option>
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?= $i ?>" <?= (($diary[$key] ?? null) == $i) ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </fieldset>
    <?php endif; ?>

    <div class="field">
        <label for="notes">Notas (opcional)</label>
        <textarea id="notes" name="notes" rows="4" maxlength="2000"><?= e($notes) ?></textarea>
    </div>

    <div class="form-actions">
        <a class="button button-ghost" href="/mi-kit">Cancelar</a>
        <button type="submit" class="button button-primary">Guardar diario</button>
    </div>
</form>
