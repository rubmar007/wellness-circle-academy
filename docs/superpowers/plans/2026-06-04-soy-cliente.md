# Soy Cliente — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Agregar sección "Soy Cliente" con rol `cliente` que limita acceso a Inicio, Tus Primeros Pasos, Soy Cliente, Eventos, Normas y Perfil.

**Architecture:** Tabla `client_page` de fila única (id=1) gestionada por el admin. `ClientController` renderiza la página. `Auth::requireTeamMember()` bloquea clientes en Entrenamiento, Materiales y Notificaciones. Nav en `layout.php` filtra ítems por rol.

**Tech Stack:** PHP 8.3, PostgreSQL/Neon, `Upload::image()`, `Embed::parseVideo()`, CSS existente + `.copy-card-body` nuevo.

---

### Task 1: Migración — tabla `client_page`

**Files:**
- Create: `database/migrations/2026-06-04-add-client-page.sql`

- [ ] **Step 1: Crear el archivo de migración**

```sql
CREATE TABLE client_page (
    id                        SMALLINT PRIMARY KEY DEFAULT 1,
    welcome_image_url         VARCHAR(500),
    uso_texto                 TEXT,
    activar_texto             TEXT,
    activar_video_url         VARCHAR(500),
    desactivar_texto          TEXT,
    desactivar_video_url      VARCHAR(500),
    preferente_texto          TEXT,
    preferente_video_url      VARCHAR(500),
    beneficios_autoenvio_url  VARCHAR(500),
    beneficios_preferente_url VARCHAR(500),
    texto_libre               TEXT,
    updated_at                TIMESTAMPTZ DEFAULT NOW()
);

INSERT INTO client_page (id) VALUES (1);
```

- [ ] **Step 2: Aplicar migración en Neon**

```bash
psql "$DATABASE_URL" -f database/migrations/2026-06-04-add-client-page.sql
```

Expected output: `CREATE TABLE` y `INSERT 0 1`.

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026-06-04-add-client-page.sql
git commit -m "feat: migración tabla client_page"
```

---

### Task 2: Rol `cliente` — Auth + AdminUsersController + form

**Files:**
- Modify: `src/Auth.php` (después de línea 76, tras `requireAdmin()`)
- Modify: `src/Controllers/AdminUsersController.php` (línea ~196 en `extractInput()`)
- Modify: `templates/admin/users/form.php` (selector `<select id="role">`)

- [ ] **Step 1: Agregar `Auth::requireTeamMember()` en `src/Auth.php`**

Insertar después del cierre de `requireAdmin()`:

```php
public static function requireTeamMember(): void
{
    self::requireLogin();
    $user = self::user();
    if ($user !== null && $user['role'] === 'cliente') {
        http_response_code(403);
        require dirname(__DIR__) . '/templates/errors/403.php';
        exit;
    }
}
```

- [ ] **Step 2: Permitir `'cliente'` en `AdminUsersController::extractInput()`**

En `src/Controllers/AdminUsersController.php`, reemplazar:

```php
if (!in_array($role, ['admin', 'member'], true)) {
    $role = 'member';
}
```

por:

```php
if (!in_array($role, ['admin', 'member', 'cliente'], true)) {
    $role = 'member';
}
```

- [ ] **Step 3: Agregar opción `cliente` en `templates/admin/users/form.php`**

Reemplazar el bloque `<select id="role" ...>`:

```php
<select id="role" name="role" required>
    <option value="member"  <?= ($old['role'] ?? '') === 'member'  ? 'selected' : '' ?>>Miembro</option>
    <option value="admin"   <?= ($old['role'] ?? '') === 'admin'   ? 'selected' : '' ?>>Administrador</option>
    <option value="cliente" <?= ($old['role'] ?? '') === 'cliente' ? 'selected' : '' ?>>Cliente</option>
</select>
```

- [ ] **Step 4: Commit**

```bash
git add src/Auth.php src/Controllers/AdminUsersController.php templates/admin/users/form.php
git commit -m "feat: rol cliente — requireTeamMember + opción en form de usuarios"
```

---

### Task 3: Guards — bloquear rol `cliente` en Entrenamiento, Materiales, Notificaciones

**Files:**
- Modify: `src/Controllers/TrainingController.php` (línea ~24 en `index()`)
- Modify: `src/Controllers/MaterialController.php` (línea ~16 en `index()`)
- Modify: `src/Controllers/AnnouncementController.php` (línea ~26 en `index()`)

- [ ] **Step 1: `TrainingController::index()` — reemplazar guard**

```php
// antes:
Auth::requireLogin();
// después:
Auth::requireTeamMember();
```

- [ ] **Step 2: `MaterialController::index()` — mismo cambio**

```php
// antes:
Auth::requireLogin();
// después:
Auth::requireTeamMember();
```

- [ ] **Step 3: `AnnouncementController::index()` — mismo cambio**

```php
// antes:
Auth::requireLogin();
// después:
Auth::requireTeamMember();
```

- [ ] **Step 4: Commit**

```bash
git add src/Controllers/TrainingController.php src/Controllers/MaterialController.php src/Controllers/AnnouncementController.php
git commit -m "feat: bloquear rol cliente en Entrenamiento, Materiales, Notificaciones"
```

---

### Task 4: `ClientController` + template `client/show.php`

**Files:**
- Create: `src/Controllers/ClientController.php`
- Create: `templates/client/show.php`

- [ ] **Step 1: Crear `src/Controllers/ClientController.php`**

```php
<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database\Connection;
use App\View;

final class ClientController
{
    /** @param array<string,string> $params */
    public function show(array $params): void
    {
        Auth::requireLogin();

        $page = Connection::get()->query(
            'SELECT * FROM client_page WHERE id = 1'
        )->fetch();

        View::render('client/show', [
            'page' => is_array($page) ? $page : [],
        ]);
    }
}
```

- [ ] **Step 2: Crear `templates/client/show.php`**

```php
<?php
declare(strict_types=1);
/**
 * @var array<string,mixed> $page
 * @var array<string,mixed> $auth
 */
$pageTitle = 'Soy Cliente';
?>
<section class="page-head">
    <h1>Soy Cliente</h1>
</section>

<?php if (!empty($page['welcome_image_url'])): ?>
<article class="client-welcome">
    <div class="client-welcome-wrap">
        <img src="<?= e($page['welcome_image_url']) ?>" alt="Bienvenida" loading="lazy">
        <p class="client-welcome-name">Bienvenid@ <?= e($auth['name'] ?? '') ?></p>
    </div>
</article>
<?php endif; ?>

<?php
$renderTextBlock = function (string $title, ?string $text): void {
    if ($text === null || trim($text) === '') {
        return;
    }
    ?>
    <article class="copy-card">
        <header class="copy-card-head">
            <h2><?= e($title) ?></h2>
        </header>
        <div class="copy-card-body"><?= nl2br(e($text)) ?></div>
    </article>
    <?php
};

$renderVideo = function (?string $url): void {
    $video = \App\Embed::parseVideo($url);
    if ($video === null) {
        return;
    }
    ?>
    <div class="video-frame">
        <iframe
            src="<?= e($video['embed_url']) ?>"
            title="<?= e($video['title']) ?>"
            loading="lazy"
            referrerpolicy="strict-origin-when-cross-origin"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen></iframe>
    </div>
    <?php
};

$renderImageBlock = function (string $title, ?string $url): void {
    if ($url === null || $url === '') {
        return;
    }
    ?>
    <article class="image-card">
        <h2><?= e($title) ?></h2>
        <img src="<?= e($url) ?>" alt="<?= e($title) ?>" loading="lazy">
    </article>
    <?php
};
?>

<?php $renderTextBlock('¿Cómo utilizar el producto? / Importancia de la Hidratación', $page['uso_texto'] ?? null); ?>

<?php if (!empty($page['activar_texto']) || !empty($page['activar_video_url'])): ?>
<article class="copy-card">
    <header class="copy-card-head">
        <h2>¿Cómo activar autoenvío?</h2>
    </header>
    <?php if (!empty($page['activar_texto'])): ?>
        <div class="copy-card-body"><?= nl2br(e($page['activar_texto'])) ?></div>
    <?php endif; ?>
    <?php $renderVideo($page['activar_video_url'] ?? null); ?>
</article>
<?php endif; ?>

<?php if (!empty($page['desactivar_texto']) || !empty($page['desactivar_video_url'])): ?>
<article class="copy-card">
    <header class="copy-card-head">
        <h2>¿Cómo desactivar autoenvío?</h2>
    </header>
    <?php if (!empty($page['desactivar_texto'])): ?>
        <div class="copy-card-body"><?= nl2br(e($page['desactivar_texto'])) ?></div>
    <?php endif; ?>
    <?php $renderVideo($page['desactivar_video_url'] ?? null); ?>
</article>
<?php endif; ?>

<?php if (!empty($page['preferente_texto']) || !empty($page['preferente_video_url'])): ?>
<article class="copy-card">
    <header class="copy-card-head">
        <h2>¿Cómo convertirte en cliente preferente plus?</h2>
    </header>
    <?php if (!empty($page['preferente_texto'])): ?>
        <div class="copy-card-body"><?= nl2br(e($page['preferente_texto'])) ?></div>
    <?php endif; ?>
    <?php $renderVideo($page['preferente_video_url'] ?? null); ?>
</article>
<?php endif; ?>

<?php $renderImageBlock('Beneficios del autoenvío / Regalos', $page['beneficios_autoenvio_url'] ?? null); ?>
<?php $renderImageBlock('Beneficios del cliente preferente plus', $page['beneficios_preferente_url'] ?? null); ?>

<?php if (!empty($page['texto_libre']) && trim((string) $page['texto_libre']) !== ''): ?>
<article class="copy-card">
    <div class="copy-card-body"><?= nl2br(e($page['texto_libre'])) ?></div>
</article>
<?php endif; ?>
```

- [ ] **Step 3: Commit**

```bash
git add src/Controllers/ClientController.php templates/client/show.php
git commit -m "feat: ClientController + template Soy Cliente"
```

---

### Task 5: `AdminClientController` + template `admin/client/edit.php`

**Files:**
- Create: `src/Controllers/AdminClientController.php`
- Create: `templates/admin/client/edit.php`

- [ ] **Step 1: Crear `src/Controllers/AdminClientController.php`**

```php
<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Database\Connection;
use App\Embed;
use App\Upload;
use App\View;
use RuntimeException;

final class AdminClientController
{
    /** @param array<string,string> $params */
    public function edit(array $params): void
    {
        Auth::requireAdmin();

        $page = Connection::get()->query(
            'SELECT * FROM client_page WHERE id = 1'
        )->fetch();

        View::render('admin/client/edit', [
            'page'   => is_array($page) ? $page : [],
            'errors' => [],
            'flash'  => self::popFlash(),
        ]);
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $pdo  = Connection::get();
        $page = $pdo->query('SELECT * FROM client_page WHERE id = 1')->fetch();
        $page = is_array($page) ? $page : [];

        $errors = [];

        // --- Textos y URLs de video ---
        $uso_texto           = trim((string) ($_POST['uso_texto']           ?? ''));
        $activar_texto       = trim((string) ($_POST['activar_texto']       ?? ''));
        $activar_video_url   = trim((string) ($_POST['activar_video_url']   ?? ''));
        $desactivar_texto    = trim((string) ($_POST['desactivar_texto']    ?? ''));
        $desactivar_video_url = trim((string) ($_POST['desactivar_video_url'] ?? ''));
        $preferente_texto    = trim((string) ($_POST['preferente_texto']    ?? ''));
        $preferente_video_url = trim((string) ($_POST['preferente_video_url'] ?? ''));
        $texto_libre         = trim((string) ($_POST['texto_libre']         ?? ''));

        // Validar URLs de video (si no están vacías deben ser YouTube/Vimeo)
        if ($activar_video_url !== '' && Embed::parseVideo($activar_video_url) === null) {
            $errors['activar_video_url'] = 'URL de YouTube o Vimeo no válida.';
        }
        if ($desactivar_video_url !== '' && Embed::parseVideo($desactivar_video_url) === null) {
            $errors['desactivar_video_url'] = 'URL de YouTube o Vimeo no válida.';
        }
        if ($preferente_video_url !== '' && Embed::parseVideo($preferente_video_url) === null) {
            $errors['preferente_video_url'] = 'URL de YouTube o Vimeo no válida.';
        }

        // --- Imágenes ---
        $imageFields = [
            'welcome_image'         => 'welcome_image_url',
            'beneficios_autoenvio'  => 'beneficios_autoenvio_url',
            'beneficios_preferente' => 'beneficios_preferente_url',
        ];

        $newUploads  = [];  // campo => nueva ruta subida
        $finalImages = [];  // campo_url => ruta efectiva (nueva o existente)

        foreach ($imageFields as $inputName => $colName) {
            $existing = (string) ($page[$colName] ?? '');
            $file     = $_FILES[$inputName] ?? null;
            $hasUpload = is_array($file)
                && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

            if ($hasUpload) {
                try {
                    $newPath = Upload::image($file);
                    $newUploads[$colName]  = $newPath;
                    $finalImages[$colName] = $newPath;
                } catch (RuntimeException $e) {
                    $errors[$inputName] = $e->getMessage();
                    $finalImages[$colName] = $existing;
                }
            } else {
                $finalImages[$colName] = $existing;
            }
        }

        if ($errors !== []) {
            // Borrar las imágenes que se subieron antes del error
            foreach ($newUploads as $path) {
                Upload::deleteImage($path);
            }
            View::render('admin/client/edit', [
                'page'   => $page,
                'errors' => $errors,
                'flash'  => null,
            ]);
            return;
        }

        // Borrar imágenes anteriores que fueron reemplazadas
        foreach ($imageFields as $inputName => $colName) {
            $existing = (string) ($page[$colName] ?? '');
            if (isset($newUploads[$colName]) && $existing !== '' && $existing !== $newUploads[$colName]) {
                Upload::deleteImage($existing);
            }
        }

        $stmt = $pdo->prepare(
            'UPDATE client_page SET
                welcome_image_url         = :wi,
                uso_texto                 = :ut,
                activar_texto             = :at,
                activar_video_url         = :av,
                desactivar_texto          = :dt,
                desactivar_video_url      = :dv,
                preferente_texto          = :pt,
                preferente_video_url      = :pv,
                beneficios_autoenvio_url  = :ba,
                beneficios_preferente_url = :bp,
                texto_libre               = :tl,
                updated_at                = NOW()
             WHERE id = 1'
        );
        $stmt->execute([
            ':wi' => $finalImages['welcome_image_url']         !== '' ? $finalImages['welcome_image_url']         : null,
            ':ut' => $uso_texto           !== '' ? $uso_texto           : null,
            ':at' => $activar_texto       !== '' ? $activar_texto       : null,
            ':av' => $activar_video_url   !== '' ? $activar_video_url   : null,
            ':dt' => $desactivar_texto    !== '' ? $desactivar_texto    : null,
            ':dv' => $desactivar_video_url !== '' ? $desactivar_video_url : null,
            ':pt' => $preferente_texto    !== '' ? $preferente_texto    : null,
            ':pv' => $preferente_video_url !== '' ? $preferente_video_url : null,
            ':ba' => $finalImages['beneficios_autoenvio_url']  !== '' ? $finalImages['beneficios_autoenvio_url']  : null,
            ':bp' => $finalImages['beneficios_preferente_url'] !== '' ? $finalImages['beneficios_preferente_url'] : null,
            ':tl' => $texto_libre !== '' ? $texto_libre : null,
        ]);

        self::setFlash('Página Soy Cliente actualizada.');
        View::redirect('/admin/cliente');
    }

    private static function setFlash(string $msg, string $type = 'success'): void
    {
        $_SESSION['_flash'] = ['type' => $type, 'msg' => $msg];
    }

    /** @return array{type:string,msg:string}|null */
    private static function popFlash(): ?array
    {
        if (!isset($_SESSION['_flash'])) {
            return null;
        }
        $flash = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return is_array($flash) ? $flash : null;
    }
}
```

- [ ] **Step 2: Crear `templates/admin/client/edit.php`**

```php
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
        <small class="field-hint">JPG, PNG o WebP. Máx 5 MB. Deja vacío para conservar la imagen actual.</small>
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

    <h2 class="form-section-title">¿Cómo utilizar el producto? / Importancia de la Hidratación</h2>
    <?php $textField('uso_texto', 'Texto', $page['uso_texto'] ?? null); ?>

    <h2 class="form-section-title">¿Cómo activar autoenvío?</h2>
    <?php $textField('activar_texto', 'Texto', $page['activar_texto'] ?? null); ?>
    <?php $videoField('activar_video_url', 'Video', $page['activar_video_url'] ?? null); ?>

    <h2 class="form-section-title">¿Cómo desactivar autoenvío?</h2>
    <?php $textField('desactivar_texto', 'Texto', $page['desactivar_texto'] ?? null); ?>
    <?php $videoField('desactivar_video_url', 'Video', $page['desactivar_video_url'] ?? null); ?>

    <h2 class="form-section-title">¿Cómo convertirte en cliente preferente plus?</h2>
    <?php $textField('preferente_texto', 'Texto', $page['preferente_texto'] ?? null); ?>
    <?php $videoField('preferente_video_url', 'Video', $page['preferente_video_url'] ?? null); ?>

    <h2 class="form-section-title">Beneficios del autoenvío / Regalos</h2>
    <?php $imgField('beneficios_autoenvio', 'Imagen', $page['beneficios_autoenvio_url'] ?? null); ?>

    <h2 class="form-section-title">Beneficios del cliente preferente plus</h2>
    <?php $imgField('beneficios_preferente', 'Imagen', $page['beneficios_preferente_url'] ?? null); ?>

    <h2 class="form-section-title">Texto libre (opcional)</h2>
    <?php $textField('texto_libre', 'Texto (no aparece si está vacío)', $page['texto_libre'] ?? null); ?>

    <div class="form-actions">
        <a class="button button-ghost" href="/admin">Cancelar</a>
        <button type="submit" class="button button-primary">Guardar</button>
    </div>
</form>
```

- [ ] **Step 3: Commit**

```bash
git add src/Controllers/AdminClientController.php templates/admin/client/edit.php
git commit -m "feat: AdminClientController + template admin/client/edit"
```

---

### Task 6: Rutas + Navegación + CSS

**Files:**
- Modify: `public/index.php`
- Modify: `templates/layout.php`
- Modify: `public/assets/css/sections.css`

- [ ] **Step 1: Agregar rutas en `public/index.php`**

Agregar después de la línea `$router->get('/normas', ...)` (aproximadamente línea 139):

```php
$router->get('/soy-cliente',  [ClientController::class,       'show']);
$router->get('/admin/cliente',  [AdminClientController::class, 'edit']);
$router->post('/admin/cliente', [AdminClientController::class, 'update']);
```

Agregar los `use` al bloque de `use` al inicio del archivo:

```php
use App\Controllers\ClientController;
use App\Controllers\AdminClientController;
```

- [ ] **Step 2: Actualizar `$sectionNav` en `templates/layout.php`**

Reemplazar el array `$sectionNav` (líneas 27–36) por:

```php
$sectionNav = [
    ['href' => '/inicio',         'label' => 'Inicio',              'icon' => '🏠', 'match' => '/inicio'],
    ['href' => '/dashboard',      'label' => 'Tus primeros pasos',  'icon' => '🚀', 'match' => '/dashboard'],
    ['href' => '/soy-cliente',    'label' => 'Soy Cliente',         'icon' => '⭐', 'match' => '/soy-cliente'],
    ['href' => '/entrenamiento',  'label' => 'Entrenamiento',       'icon' => '🎓', 'match' => '/entrenamiento'],
    ['href' => '/materiales',     'label' => 'Materiales',          'icon' => '📂', 'match' => '/materiales'],
    ['href' => '/eventos',        'label' => 'Eventos',             'icon' => '📅', 'match' => '/eventos'],
    ['href' => '/notificaciones', 'label' => 'Notificaciones',      'icon' => '🏅', 'match' => '/notificaciones'],
    ['href' => '/normas',         'label' => 'Normas y Reglamentos','icon' => '📜', 'match' => '/normas'],
    ['href' => '/perfil',         'label' => 'Perfil',              'icon' => '👤', 'match' => '/perfil'],
];
```

- [ ] **Step 3: Filtrar ítems de nav según rol en `templates/layout.php`**

En el bucle `foreach ($sectionNav as $item)`, envolver los ítems de Entrenamiento, Materiales y Notificaciones con una condición. Reemplazar el `foreach` completo (líneas ~80–88) por:

```php
<?php
$isCliente = ($auth['role'] ?? '') === 'cliente';
$teamOnlyItems = ['/entrenamiento', '/materiales', '/notificaciones'];
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
```

- [ ] **Step 4: Agregar link "Soy Cliente" en panel admin en `templates/layout.php`**

Dentro del bloque `if (($auth['role'] ?? '') === 'admin')`, agregar acceso rápido (después del link `/admin`):

```php
<li>
    <a href="/admin/cliente" class="<?= str_starts_with($navPath, '/admin/cliente') ? 'is-active' : '' ?>">
        <span class="nav-icon" aria-hidden="true">⭐</span>
        <span>Contenido Cliente</span>
    </a>
</li>
```

- [ ] **Step 5: Agregar CSS para bienvenida y copy-card-body en `public/assets/css/sections.css`**

Al final del archivo, agregar:

```css
/* Soy Cliente — bloque bienvenida */
.client-welcome {
    margin-bottom: 1.5rem;
}
.client-welcome-wrap {
    position: relative;
    display: inline-block;
    width: 100%;
}
.client-welcome-wrap img {
    width: 100%;
    height: auto;
    display: block;
    border-radius: var(--radius, 0.5rem);
}
.client-welcome-name {
    position: absolute;
    bottom: 1rem;
    left: 1rem;
    right: 1rem;
    font-size: 1.4rem;
    font-weight: 700;
    color: #fff;
    text-shadow: 0 1px 4px rgba(0,0,0,.7);
}

/* copy-card texto display (sin textarea) */
.copy-card-body {
    padding: 1rem 1.25rem;
    line-height: 1.7;
    white-space: pre-wrap;
    word-break: break-word;
}
```

- [ ] **Step 6: Commit**

```bash
git add public/index.php templates/layout.php public/assets/css/sections.css
git commit -m "feat: rutas /soy-cliente + nav filtrado por rol + CSS bienvenida"
```

---

### Task 7: Deploy

**Files:** ninguno nuevo.

- [ ] **Step 1: Push a Railway**

```bash
git push origin main
```

- [ ] **Step 2: Verificar que la migración ya fue aplicada en Neon**

Si Task 1 Step 2 ya se ejecutó, omitir. Si no:

```bash
psql "$DATABASE_URL" -f database/migrations/2026-06-04-add-client-page.sql
```

- [ ] **Step 3: Verificar en producción**

Revisar que:
1. `https://academiawca.com/soy-cliente` no arroja error para un usuario logueado
2. `https://academiawca.com/admin/cliente` carga el formulario para admin
3. Un usuario con rol `cliente` no ve Entrenamiento, Materiales ni Notificaciones en el nav
4. Un usuario con rol `cliente` que navega directo a `https://academiawca.com/entrenamiento` recibe 403
