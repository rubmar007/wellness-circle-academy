<?php

declare(strict_types=1);

// Cuando este archivo se ejecuta como router de `php -S` (servidor built-in,
// usado en Railway), tenemos que indicarle al servidor que sirva los archivos
// estáticos directamente devolviendo false. En SAPI distinto (Apache, fpm),
// el servidor web ya sirve estáticos por sí mismo y este bloque es no-op.
if (PHP_SAPI === 'cli-server') {
    $requested = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    if ($requested !== '/' && $requested !== false) {
        $candidate = __DIR__ . '/' . ltrim($requested, '/');
        // Resolver y verificar que el archivo está dentro de public/ para
        // bloquear path traversal (../../etc/passwd, etc).
        $real     = realpath($candidate);
        $publicDir = realpath(__DIR__);
        if ($real !== false && $publicDir !== false
            && str_starts_with($real, $publicDir . DIRECTORY_SEPARATOR)
            && is_file($real)) {
            return false; // php -S sirve el archivo estático tal cual.
        }
    }
}

use App\Auth;
use App\Controllers\AdminClientKitsController;
use App\Controllers\AdminNoticiasController;
use App\Controllers\AdminPromocionesController;
use App\Controllers\AdminBatchController;
use App\Controllers\AdminController;
use App\Controllers\AdminEventsController;
use App\Controllers\AdminLessonsController;
use App\Controllers\AdminMaterialsController;
use App\Controllers\AdminPagesController;
use App\Controllers\AdminProgramsController;
use App\Controllers\AdminTrainingsController;
use App\Controllers\AdminClientController;
use App\Controllers\AdminUsersController;
use App\Controllers\NoticiaController;
use App\Controllers\PromocionController;
use App\Controllers\ClientController;
use App\Controllers\ClientKitController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\EventController;
use App\Controllers\FeedController;
use App\Controllers\HomeController;
use App\Controllers\LessonController;
use App\Controllers\MaterialController;
use App\Controllers\MyExperiencesController;
use App\Controllers\PageController;
use App\Controllers\PasswordResetController;
use App\Controllers\PatchMapController;
use App\Controllers\ProfileController;
use App\Controllers\ProgramController;
use App\Controllers\ProgressController;
use App\Controllers\ThemeController;
use App\Controllers\TrainingController;
use App\Router;
use App\Security;
use App\Support\Env;

$basePath = dirname(__DIR__);

require $basePath . '/vendor/autoload.php';

Env::load($basePath);

// En producción, forzar HTTPS con redirect 301. Defensa contra envío de
// credenciales en HTTP plano. Se usa el host real del request para que
// funcione con cualquier custom domain sin depender de APP_URL.
if (Env::get('APP_ENV') === 'production' && !\App\Security::isHttps()) {
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $uri  = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    if ($host !== '' && preg_match('/^[A-Za-z0-9.\-:]+$/', $host) === 1) {
        header('Location: https://' . $host . $uri, true, 301);
        exit;
    }
}

$debug = Env::bool('APP_DEBUG', false);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

if (!$debug) {
    set_exception_handler(static function (\Throwable $e): void {
        error_log('[wca] ' . $e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        http_response_code(500);
        require dirname(__DIR__) . '/templates/errors/500.php';
        exit;
    });
}

$isHttps = Security::isHttps();

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name('wca_session');
session_start([
    'use_strict_mode'  => 1,
    'use_only_cookies' => 1,
    'sid_length'       => 48,
    'sid_bits_per_character' => 6,
]);

Security::applyHeaders($isHttps);

$router = new Router();

// Públicas (la home redirige a /login o /dashboard según sesión).
$router->get('/',          [HomeController::class, 'index']);
$router->get('/login',     [AuthController::class, 'showLogin']);
$router->post('/login',    [AuthController::class, 'login']);
$router->post('/logout',   [AuthController::class, 'logout']);

// Cambio de tema visual (cookie, sin JS). Disponible para cualquier visitante.
$router->post('/tema',     [ThemeController::class, 'set']);

// Recuperación de contraseña (URL "secreta", sin enlaces desde la app).
$router->get('/ctoadmin',                [PasswordResetController::class, 'show']);
$router->post('/ctoadmin',               [PasswordResetController::class, 'request']);
$router->get('/restablecer/{token}',     [PasswordResetController::class, 'showReset']);
$router->post('/restablecer/{token}',    [PasswordResetController::class, 'reset']);

// Área privada (los controladores verifican Auth::requireLogin internamente)
$router->get('/dashboard',                       [DashboardController::class, 'index']);
$router->get('/programas/{slug}',                [ProgramController::class,   'show']);
$router->get('/programas/{slug}/dia/{day}',      [LessonController::class,    'show']);
$router->post('/progreso',                       [ProgressController::class,  'toggle']);

// Secciones Req B (área de miembro)
$router->get('/inicio',                  [FeedController::class, 'index']);
$router->post('/inicio',                 [FeedController::class, 'store']);
$router->post('/inicio/{id}/eliminar',   [FeedController::class, 'destroy']);

$router->get('/noticias',                [NoticiaController::class, 'index']);
$router->get('/entrenamiento',           [TrainingController::class, 'index']);
$router->get('/materiales',              [MaterialController::class, 'index']);
$router->get('/eventos',                 [EventController::class, 'index']);
$router->get('/promociones',             [PromocionController::class, 'index']);
$router->get('/normas',                  [PageController::class, 'show']);
$router->get('/soy-cliente',             [ClientController::class,      'show']);
$router->get('/mapa-parches',            [PatchMapController::class,    'index']);

// WCA Experience Kit (cliente y member/promotor, ver Auth::requireLogin en el controlador)
$router->get('/mi-kit',                  [ClientKitController::class, 'index']);
$router->get('/mi-kit/diario',           [ClientKitController::class, 'diary']);
$router->post('/mi-kit/diario',          [ClientKitController::class, 'saveDiary']);
$router->post('/mi-kit/parche',          [ClientKitController::class, 'togglePatch']);
$router->post('/mi-kit/agua',            [ClientKitController::class, 'logWater']);
$router->post('/mi-kit/electrolitos',    [ClientKitController::class, 'toggleElectrolytes']);
$router->post('/mi-kit/movimiento',      [ClientKitController::class, 'saveMovement']);
$router->post('/mi-kit/peso',            [ClientKitController::class, 'saveWeight']);

// Mis Experience (solo rol member — Promotor responsable, ver MyExperiencesController)
$router->get('/mis-experience',          [MyExperiencesController::class, 'index']);
$router->get('/mis-experience/{id}',     [MyExperiencesController::class, 'show']);

$router->get('/perfil',                  [ProfileController::class, 'show']);
$router->post('/perfil',                 [ProfileController::class, 'update']);

// Admin (los controladores verifican Auth::requireAdmin internamente)
$router->get('/admin',     [AdminController::class, 'index']);

// Admin · usuarios
$router->get('/admin/usuarios',              [AdminUsersController::class, 'index']);
$router->get('/admin/usuarios/nuevo',        [AdminUsersController::class, 'create']);
$router->post('/admin/usuarios',             [AdminUsersController::class, 'store']);
$router->get('/admin/usuarios/{id}/editar',  [AdminUsersController::class, 'edit']);
$router->post('/admin/usuarios/{id}',        [AdminUsersController::class, 'update']);
$router->post('/admin/usuarios/{id}/toggle', [AdminUsersController::class, 'toggleActive']);

// Admin · programas
$router->get('/admin/programas',                [AdminProgramsController::class, 'index']);
$router->get('/admin/programas/nuevo',          [AdminProgramsController::class, 'create']);
$router->post('/admin/programas',               [AdminProgramsController::class, 'store']);
$router->get('/admin/programas/{id}/editar',    [AdminProgramsController::class, 'edit']);
$router->post('/admin/programas/{id}',          [AdminProgramsController::class, 'update']);
$router->get('/admin/programas/{id}/eliminar',  [AdminProgramsController::class, 'confirmDestroy']);
$router->post('/admin/programas/{id}/eliminar', [AdminProgramsController::class, 'destroy']);

// Admin · lecciones (anidadas bajo el programa para crear/listar; sueltas para editar/eliminar)
$router->get('/admin/programas/{programId}/lecciones',        [AdminLessonsController::class, 'index']);
$router->get('/admin/programas/{programId}/lecciones/nueva',  [AdminLessonsController::class, 'create']);
$router->post('/admin/programas/{programId}/lecciones',       [AdminLessonsController::class, 'store']);
$router->get('/admin/lecciones/{id}/editar',                  [AdminLessonsController::class, 'edit']);
$router->post('/admin/lecciones/{id}',                        [AdminLessonsController::class, 'update']);
$router->get('/admin/lecciones/{id}/eliminar',                [AdminLessonsController::class, 'confirmDestroy']);
$router->post('/admin/lecciones/{id}/eliminar',               [AdminLessonsController::class, 'destroy']);

// Admin · carga batch de lecciones desde XLSX
$router->get('/admin/programas/{id}/batch',             [AdminBatchController::class, 'show']);
$router->post('/admin/programas/{id}/batch',            [AdminBatchController::class, 'process']);
$router->get('/admin/programas/{id}/batch/plantilla',   [AdminBatchController::class, 'downloadTemplate']);

// Admin · Entrenamiento
$router->get('/admin/entrenamiento',                 [AdminTrainingsController::class, 'index']);
$router->get('/admin/entrenamiento/nuevo',           [AdminTrainingsController::class, 'create']);
$router->post('/admin/entrenamiento',                [AdminTrainingsController::class, 'store']);
$router->get('/admin/entrenamiento/{id}/editar',     [AdminTrainingsController::class, 'edit']);
$router->post('/admin/entrenamiento/{id}',           [AdminTrainingsController::class, 'update']);
$router->get('/admin/entrenamiento/{id}/eliminar',   [AdminTrainingsController::class, 'confirmDestroy']);
$router->post('/admin/entrenamiento/{id}/eliminar',  [AdminTrainingsController::class, 'destroy']);

// Admin · Materiales
$router->get('/admin/materiales',                 [AdminMaterialsController::class, 'index']);
$router->get('/admin/materiales/nuevo',           [AdminMaterialsController::class, 'create']);
$router->post('/admin/materiales',                [AdminMaterialsController::class, 'store']);
$router->get('/admin/materiales/{id}/editar',     [AdminMaterialsController::class, 'edit']);
$router->post('/admin/materiales/{id}',           [AdminMaterialsController::class, 'update']);
$router->get('/admin/materiales/{id}/eliminar',   [AdminMaterialsController::class, 'confirmDestroy']);
$router->post('/admin/materiales/{id}/eliminar',  [AdminMaterialsController::class, 'destroy']);

// Admin · Eventos
$router->get('/admin/eventos',                 [AdminEventsController::class, 'index']);
$router->get('/admin/eventos/nuevo',           [AdminEventsController::class, 'create']);
$router->post('/admin/eventos',                [AdminEventsController::class, 'store']);
$router->get('/admin/eventos/{id}/editar',     [AdminEventsController::class, 'edit']);
$router->post('/admin/eventos/{id}',           [AdminEventsController::class, 'update']);
$router->get('/admin/eventos/{id}/eliminar',   [AdminEventsController::class, 'confirmDestroy']);
$router->post('/admin/eventos/{id}/eliminar',  [AdminEventsController::class, 'destroy']);

// Admin · Noticias
$router->get('/admin/noticias',                 [AdminNoticiasController::class, 'index']);
$router->get('/admin/noticias/nueva',           [AdminNoticiasController::class, 'create']);
$router->post('/admin/noticias',                [AdminNoticiasController::class, 'store']);
$router->get('/admin/noticias/{id}/editar',     [AdminNoticiasController::class, 'edit']);
$router->post('/admin/noticias/{id}',           [AdminNoticiasController::class, 'update']);
$router->get('/admin/noticias/{id}/eliminar',   [AdminNoticiasController::class, 'confirmDestroy']);
$router->post('/admin/noticias/{id}/eliminar',  [AdminNoticiasController::class, 'destroy']);

// Admin · Promociones
$router->get('/admin/promociones',                 [AdminPromocionesController::class, 'index']);
$router->get('/admin/promociones/nueva',           [AdminPromocionesController::class, 'create']);
$router->post('/admin/promociones',                [AdminPromocionesController::class, 'store']);
$router->get('/admin/promociones/{id}/editar',     [AdminPromocionesController::class, 'edit']);
$router->post('/admin/promociones/{id}',           [AdminPromocionesController::class, 'update']);
$router->get('/admin/promociones/{id}/eliminar',   [AdminPromocionesController::class, 'confirmDestroy']);
$router->post('/admin/promociones/{id}/eliminar',  [AdminPromocionesController::class, 'destroy']);

// Admin · Normas y Reglamentos (página única)
$router->get('/admin/normas',  [AdminPagesController::class, 'edit']);
$router->post('/admin/normas', [AdminPagesController::class, 'update']);

// Admin · Soy Cliente (página única)
$router->get('/admin/cliente',  [AdminClientController::class, 'edit']);
$router->post('/admin/cliente', [AdminClientController::class, 'update']);

// Admin · WCA Experience Kit
$router->get('/admin/experience-kit',                  [AdminClientKitsController::class, 'index']);
$router->get('/admin/experience-kit/nuevo',            [AdminClientKitsController::class, 'create']);
$router->post('/admin/experience-kit',                 [AdminClientKitsController::class, 'store']);
$router->get('/admin/experience-kit/{id}/editar',      [AdminClientKitsController::class, 'edit']);
$router->post('/admin/experience-kit/{id}',            [AdminClientKitsController::class, 'update']);
$router->post('/admin/experience-kit/{id}/finalizar',  [AdminClientKitsController::class, 'finish']);
$router->get('/admin/experience-kit/{id}/eliminar',    [AdminClientKitsController::class, 'confirmDestroy']);
$router->post('/admin/experience-kit/{id}/eliminar',   [AdminClientKitsController::class, 'destroy']);

$router->dispatch();
