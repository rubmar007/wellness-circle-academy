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

// Navegación de secciones (Req B). Activo según el primer segmento de la ruta.
$navPath = $themeBack;
$sectionNav = [
    ['href' => '/inicio',         'label' => 'Inicio',            'match' => '/inicio'],
    ['href' => '/dashboard',      'label' => 'Tus primeros pasos','match' => '/dashboard'],
    ['href' => '/entrenamiento',  'label' => 'Entrenamiento',     'match' => '/entrenamiento'],
    ['href' => '/materiales',     'label' => 'Materiales',        'match' => '/materiales'],
    ['href' => '/eventos',        'label' => 'Eventos',           'match' => '/eventos'],
    ['href' => '/notificaciones', 'label' => 'Notificaciones',    'match' => '/notificaciones'],
    ['href' => '/normas',         'label' => 'Normas',            'match' => '/normas'],
    ['href' => '/perfil',         'label' => 'Perfil',            'match' => '/perfil'],
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
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/sections.css">
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

    <?php if ($auth): ?>
        <nav class="section-nav" aria-label="Secciones">
            <div class="section-nav-inner">
                <?php foreach ($sectionNav as $item): ?>
                    <a href="<?= e($item['href']) ?>"
                       class="<?= ($navPath === $item['match'] || str_starts_with($navPath, $item['match'] . '/')) ? 'is-active' : '' ?>"><?= e($item['label']) ?></a>
                <?php endforeach; ?>
                <?php if ($whatsappNumber !== ''): ?>
                    <a class="section-nav-wa" href="https://wa.me/<?= e($whatsappNumber) ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a>
                <?php endif; ?>
            </div>
        </nav>
    <?php endif; ?>

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
