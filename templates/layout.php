<?php
declare(strict_types=1);
/**
 * Layout principal.
 *
 * @var string       $content   HTML ya renderizado del template hijo.
 * @var array|null   $auth      Usuario autenticado (o null).
 * @var string       $csrf      Token CSRF de la sesión.
 * @var string       $nonce     Nonce CSP del request.
 * @var string       $appName   Nombre visible de la marca.
 * @var string|null  $pageTitle Título de la página (opcional).
 */
$title = isset($pageTitle) && is_string($pageTitle) && $pageTitle !== ''
    ? $pageTitle . ' — ' . $appName
    : $appName;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <a class="skip-link" href="#main">Saltar al contenido</a>

    <header class="site-header">
        <div class="container header-row">
            <a class="brand" href="<?= $auth ? '/dashboard' : '/' ?>">
                <span class="brand-mark" aria-hidden="true"></span>
                <span class="brand-text"><?= e($appName) ?></span>
            </a>

            <nav class="site-nav" aria-label="Principal">
                <?php if ($auth): ?>
                    <a href="/dashboard">Dashboard</a>
                    <?php if (($auth['role'] ?? '') === 'admin'): ?>
                        <a href="/admin">Admin</a>
                    <?php endif; ?>
                    <form method="post" action="/logout" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                        <button type="submit" class="link-button">Cerrar sesión</button>
                    </form>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main id="main" class="container main-area">
<?= $content ?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <small>&copy; <?= date('Y') ?> <?= e($appName) ?>. Plataforma privada.</small>
        </div>
    </footer>

    <script nonce="<?= e($nonce) ?>" src="/assets/js/copy.js"></script>
</body>
</html>
