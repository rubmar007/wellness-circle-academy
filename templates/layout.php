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

// Navegación de secciones (Req B). Sidebar a la izquierda. Activo según la ruta.
$navPath = $themeBack;
$sectionNav = [
    ['href' => '/inicio',         'label' => 'Inicio',              'icon' => '🏠', 'match' => '/inicio'],
    ['href' => '/dashboard',      'label' => 'Tus primeros pasos',  'icon' => '🚀', 'match' => '/dashboard'],
    ['href' => '/soy-cliente',    'label' => 'Soy Cliente',         'icon' => '⭐', 'match' => '/soy-cliente'],
    ['href' => '/mapa-parches',   'label' => 'Mapa de Parches',     'icon' => '🩹', 'match' => '/mapa-parches'],
    ['href' => '/noticias',        'label' => 'Noticias',            'icon' => '📰', 'match' => '/noticias'],
    ['href' => '/entrenamiento',  'label' => 'Entrenamiento',       'icon' => '🎓', 'match' => '/entrenamiento'],
    ['href' => '/materiales',     'label' => 'Materiales',          'icon' => '📂', 'match' => '/materiales'],
    ['href' => '/eventos',        'label' => 'Eventos',             'icon' => '📅', 'match' => '/eventos'],
    ['href' => '/promociones',    'label' => 'Promociones',         'icon' => '🎁', 'match' => '/promociones'],
    ['href' => '/normas',         'label' => 'Normas y Reglamentos','icon' => '📜', 'match' => '/normas'],
    ['href' => '/perfil',         'label' => 'Perfil',              'icon' => '👤', 'match' => '/perfil'],
];
$whatsappNumber = preg_replace('/\D+/', '', (string) \App\Support\Env::get('WHATSAPP_NUMBER', ''));
?>
<!doctype html>
<html lang="es" data-theme="<?= e($theme) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="<?= e($colorScheme) ?>">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/styles.css') ?>">
    <link rel="stylesheet" href="/assets/css/sections.css?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/sections.css') ?>">
    <link rel="stylesheet" href="/assets/css/patchmap.css?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/patchmap.css') ?>">
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

    <?php if ($auth): ?>
        <input type="checkbox" id="appnav" class="app-nav-cb" hidden>
        <label for="appnav" class="app-nav-toggle">☰ &nbsp;Secciones</label>
        <div class="app-shell">
            <nav class="app-sidebar" aria-label="Secciones">
                <ul>
                    <?php
                    $isCliente    = ($auth['role'] ?? '') === 'cliente';
                    $teamOnlyItems = ['/noticias', '/entrenamiento', '/materiales', '/promociones'];
                    foreach ($sectionNav as $item):
                        if ($isCliente && in_array($item['match'], $teamOnlyItems, true)) {
                            continue;
                        }
                    ?>
                        <li>
                            <a href="<?= e($item['href']) ?>"
                               class="<?= ($navPath === $item['match'] || str_starts_with($navPath, $item['match'] . '/')) ? 'is-active' : '' ?>">
                                <span class="nav-icon" aria-hidden="true"><?= $item['icon'] ?></span>
                                <span><?= e($item['label']) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <?php if ($whatsappNumber !== ''): ?>
                        <li>
                            <a class="app-sidebar-wa" href="https://wa.me/<?= e($whatsappNumber) ?>" target="_blank" rel="noopener noreferrer">
                                <span class="nav-icon" aria-hidden="true">💬</span>
                                <span>Contactar por WhatsApp</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>

            <main id="main" class="app-main">
<?= $content ?>
            </main>
        </div>
    <?php else: ?>
        <main id="main" class="container main-area">
<?= $content ?>
        </main>
    <?php endif; ?>

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
