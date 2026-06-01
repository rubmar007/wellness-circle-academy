<?php
declare(strict_types=1);
/**
 * Feed de Inicio (vista miembro).
 *
 * @var array<int,array<string,mixed>> $posts
 * @var bool                           $is_admin
 * @var array{type:string,msg:string}|null $flash
 * @var array<string,mixed>|null       $auth
 * @var string                         $csrf
 */
$pageTitle = 'Inicio';

$timeAgo = static function (string $ts): string {
    $t = strtotime($ts);
    if ($t === false) {
        return '';
    }
    $diff = time() - $t;
    if ($diff < 60)    return 'hace un momento';
    if ($diff < 3600)  return 'hace ' . (int) floor($diff / 60) . ' min';
    if ($diff < 86400) return 'hace ' . (int) floor($diff / 3600) . ' h';
    return date('d/m/Y', $t);
};

$initial = static function (string $name): string {
    $n = trim($name);
    return $n === '' ? '?' : mb_strtoupper(mb_substr($n, 0, 1));
};
?>
<div class="dashboard-logo-wrap">
    <img class="dashboard-logo" src="/assets/img/logo.png" alt="Wellness Circle Academy">
</div>

<section class="page-head">
    <h1>Hola, <?= e($auth['name'] ?? 'Miembro') ?></h1>
    <p class="muted">El muro del equipo. Comparte tu avance, tu historia o un logro del día.</p>
</section>

<?php if ($flash !== null): ?>
    <p class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></p>
<?php endif; ?>

<form class="feed-composer" method="post" action="/inicio" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <label class="field-hint" for="post-body">¿Qué quieres compartir hoy?</label>
    <textarea id="post-body" name="body" maxlength="4000" required placeholder="Escribe aquí…"></textarea>
    <div class="feed-actions">
        <label class="feed-file">
            Foto (opcional):
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
        </label>
        <button type="submit" class="button button-primary">Publicar</button>
    </div>
</form>

<?php if ($posts === []): ?>
    <p class="section-empty">Todavía no hay publicaciones. ¡Sé el primero en compartir!</p>
<?php else: ?>
    <?php foreach ($posts as $p): ?>
        <article class="post-card">
            <div class="post">
                <span class="avatar avatar-44">
                    <?php if (!empty($p['author_photo'])): ?>
                        <img src="<?= e($p['author_photo']) ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <?= e($initial((string) $p['author_name'])) ?>
                    <?php endif; ?>
                </span>
                <div class="post-body">
                    <div class="post-meta">
                        <span class="post-author"><?= e($p['author_name']) ?></span>
                        <span class="post-time"><?= e($timeAgo((string) $p['created_at'])) ?></span>
                    </div>
                    <p class="post-text"><?= e_nl2br($p['body']) ?></p>
                    <?php if (!empty($p['image_url'])): ?>
                        <img class="post-photo" src="<?= e($p['image_url']) ?>" alt="" loading="lazy">
                    <?php endif; ?>
                    <?php if ($is_admin || (int) $p['user_id'] === (int) ($auth['id'] ?? 0)): ?>
                        <div class="post-mod">
                            <form method="post" action="/inicio/<?= e($p['id']) ?>/eliminar">
                                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                                <button type="submit" class="button button-ghost button-sm button-danger">Eliminar</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
<?php endif; ?>
