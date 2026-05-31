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

$theme = \App\Controllers\ThemeController::current();
$themeLabels = ['oscuro' => 'Oscuro', 'claro' => 'Claro', 'lifewave' => 'Lifewave', 'marino' => 'Marino'];
$colorScheme = in_array($theme, ['claro', 'lifewave'], true) ? 'light' : 'dark';

// Ruta actual (sin query) para que el cambio de tema regrese a la misma página.
$themeBack = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$themeBack = is_string($themeBack) && $themeBack !== '' ? $themeBack : '/';
?>
<!doctype html>
<html lang="es" data-theme="<?= e($theme) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="<?= e($colorScheme) ?>">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <a class="skip-link" href="#main">Saltar al contenido</a>

    <header class="site-header">
        <div class="container header-row">
            <a class="brand" href="<?= $auth ? '/dashboard' : '/' ?>">
                <img class="brand-mark" src="/assets/img/logo.png" alt="" width="36" height="36">
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
            <div class="theme-picker">
                <span class="theme-picker-label">Tema:</span>
                <?php foreach ($themeLabels as $slug => $label): ?>
                    <form method="post" action="/tema" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="theme" value="<?= e($slug) ?>">
                        <input type="hidden" name="back" value="<?= e($themeBack) ?>">
                        <button type="submit" class="theme-pill <?= $theme === $slug ? 'is-active' : '' ?>"><?= e($label) ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    </footer>

    <script nonce="<?= e($nonce) ?>" src="/assets/js/copy.js"></script>
</body>
</html>
