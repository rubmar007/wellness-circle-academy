<?php
declare(strict_types=1);
/**
 * @var array<int, array<string,mixed>> $patches
 * @var array<string, array{label: string, patches: array<int,string>}> $kits
 * @var string $nonce
 */
$pageTitle = 'Mapa de Parches';

// Cache-busting: los avatars se sirven con cache-control:max-age=14400 (4h).
// Sin versionar la URL, cualquier reemplazo del archivo (como este mismo
// ajuste de tamaño) sigue mostrando la versión vieja en cache del navegador
// o del CDN hasta que expire. Mismo patrón que ya usa layout.php con el CSS.
$bodymapDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/bodymap';
$frontV = filemtime($bodymapDir . '/front.jpg');
$backV  = filemtime($bodymapDir . '/back.jpg');

$icons = [
    'x39' => '⚡',
    'x49' => '🔥',
    'sp6' => '💧',
    'aeon' => '🛡️',
    'carnosine' => '🧠',
    'glutation' => '🧬',
    'silentnights' => '🌙',
    'alavida' => '☀️',
    'icewave' => '🧊',
];

/** @param array<int,string> $kitSlugs */
function patchmapKitClasses(string $patchId, array $kits): string
{
    $classes = [];
    foreach ($kits as $slug => $kit) {
        if (in_array($patchId, $kit['patches'], true)) {
            $classes[] = 'k-' . $slug;
        }
    }
    return implode(' ', $classes);
}

// CSP no permite `style=""` inline (style-src sin 'unsafe-inline'). Los
// colores por parche y las coordenadas por punto se generan aquí como reglas
// CSS con clases, en un <style> con el nonce del request — mismo mecanismo
// que ya se usa para el <script> del layout.
$dynamicCss = '';
foreach ($patches as $p) {
    $dynamicCss .= '.accent-' . $p['id'] . '{--accent:' . $p['color'] . "}\n";
}

// Índice global por punto (compartido entre el <style> generado aquí y los
// dos loops de la figura frente/atrás más abajo) para que las clases .pt-N
// coincidan siempre con la coordenada correcta sin depender de contadores
// que se reinicien por vista.
$allPoints = [];
$idx = 0;
foreach ($patches as $p) {
    foreach ($p['points'] as $pt) {
        $allPoints[] = ['patchId' => $p['id'], 'idx' => $idx, 'pt' => $pt];
        $dynamicCss .= '.pt-' . $idx . '{top:' . $pt['y'] . '%;left:' . $pt['x'] . "%}\n";
        $idx++;
    }
}
?>
<style nonce="<?= e($nonce) ?>"><?= $dynamicCss ?></style>

<section class="page-head">
    <h1>Mapa de colocación de parches</h1>
    <p class="muted">Selecciona un parche para ver dónde y cómo colocarlo. Filtra por tu kit si quieres ver solo los que te corresponden.</p>
</section>

<div class="bodymap">
    <!-- Estado (radios ocultos, sin JS) -->
    <input type="radio" name="bm-kit" id="kit-todos" class="bm-input" checked hidden>
    <?php foreach ($kits as $slug => $kit): ?>
        <input type="radio" name="bm-kit" id="kit-<?= e($slug) ?>" class="bm-input" hidden>
    <?php endforeach; ?>

    <input type="radio" name="bm-view" id="view-front" class="bm-input" checked hidden>
    <input type="radio" name="bm-view" id="view-back" class="bm-input" hidden>

    <?php foreach ($patches as $i => $p): ?>
        <input type="radio" name="bm-patch" id="patch-<?= e($p['id']) ?>" class="bm-input" <?= $i === 0 ? 'checked' : '' ?> hidden>
    <?php endforeach; ?>

    <div class="bodymap-toolbar">
        <div class="bm-pillrow bm-kitrow" role="group" aria-label="Filtrar por kit">
            <label for="kit-todos" class="bm-pill">Todos</label>
            <?php foreach ($kits as $slug => $kit): ?>
                <label for="kit-<?= e($slug) ?>" class="bm-pill"><?= e($kit['label']) ?></label>
            <?php endforeach; ?>
        </div>
        <div class="bm-pillrow bm-viewrow" role="group" aria-label="Vista">
            <label for="view-front" class="bm-pill bm-pill-view">Frente</label>
            <label for="view-back" class="bm-pill bm-pill-view">Atrás</label>
        </div>
    </div>

    <div class="bodymap-layout">
        <aside class="patch-sidebar">
            <?php foreach ($patches as $p): ?>
                <label for="patch-<?= e($p['id']) ?>"
                       class="patch-item accent-<?= e($p['id']) ?> <?= e(patchmapKitClasses((string) $p['id'], $kits)) ?>">
                    <span class="patch-item-icon"><?= $icons[$p['id']] ?? '✦' ?></span>
                    <span class="patch-item-text">
                        <span class="patch-item-name"><?= e($p['name']) ?></span>
                        <span class="patch-item-sub"><?= e($p['subtitle']) ?></span>
                    </span>
                </label>
            <?php endforeach; ?>
        </aside>

        <main class="bodymap-stage">
            <div class="stage-view stage-front">
                <span class="side-tag side-tag-left" aria-hidden="true">Derecha</span>
                <span class="side-tag side-tag-right" aria-hidden="true">Izquierda</span>
                <img src="/assets/img/bodymap/front.jpg?v=<?= e((string) $frontV) ?>" alt="Figura de frente" class="stage-img">
                <?php foreach ($allPoints as $ap): if ($ap['pt']['view'] !== 'front') { continue; } ?>
                    <span class="point point-<?= e($ap['patchId']) ?> accent-<?= e($ap['patchId']) ?> pt-<?= $ap['idx'] ?>" tabindex="0">
                        <span class="point-tip"><?= e($ap['pt']['label']) ?> — <?= e($ap['pt']['desc']) ?></span>
                    </span>
                <?php endforeach; ?>
            </div>
            <div class="stage-view stage-back">
                <span class="side-tag side-tag-left" aria-hidden="true">Izquierda</span>
                <span class="side-tag side-tag-right" aria-hidden="true">Derecha</span>
                <img src="/assets/img/bodymap/back.jpg?v=<?= e((string) $backV) ?>" alt="Figura de espalda" class="stage-img">
                <?php foreach ($allPoints as $ap): if ($ap['pt']['view'] !== 'back') { continue; } ?>
                    <span class="point point-<?= e($ap['patchId']) ?> accent-<?= e($ap['patchId']) ?> pt-<?= $ap['idx'] ?>" tabindex="0">
                        <span class="point-tip"><?= e($ap['pt']['label']) ?> — <?= e($ap['pt']['desc']) ?></span>
                    </span>
                <?php endforeach; ?>
            </div>
        </main>

        <aside class="patch-info">
            <?php foreach ($patches as $p): ?>
                <div class="info-panel info-panel-<?= e($p['id']) ?> accent-<?= e($p['id']) ?>">
                    <div class="info-panel-head">
                        <span class="info-panel-icon"><?= $icons[$p['id']] ?? '✦' ?></span>
                        <div>
                            <h2><?= e($p['name']) ?></h2>
                            <span class="info-panel-tag">Protocolo</span>
                        </div>
                    </div>
                    <p class="info-panel-desc"><?= e($p['desc']) ?></p>
                    <p class="info-panel-schedule"><strong>Horario:</strong> <?= e($p['schedule']) ?></p>

                    <?php if ($p['points'] === []): ?>
                        <p class="section-empty">Sin coordenadas confirmadas todavía para este parche.</p>
                    <?php else: ?>
                        <p class="info-panel-coords-label">Coordenadas de aplicación:</p>
                        <ul class="info-panel-coords">
                            <?php foreach ($p['points'] as $pt): ?>
                                <li>
                                    <strong><?= e($pt['label']) ?></strong>
                                    <span class="muted"><?= e($pt['desc']) ?></span>
                                    <span class="info-panel-view-tag"><?= $pt['view'] === 'front' ? 'Frente' : 'Atrás' ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </aside>
    </div>
</div>
