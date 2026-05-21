<?php

declare(strict_types=1);

use App\Auth;
use App\Controllers\AdminBatchController;
use App\Controllers\AdminController;
use App\Controllers\AdminLessonsController;
use App\Controllers\AdminProgramsController;
use App\Controllers\AdminUsersController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\LessonController;
use App\Controllers\ProgramController;
use App\Controllers\ProgressController;
use App\Router;
use App\Security;
use App\Support\Env;

$basePath = dirname(__DIR__);

require $basePath . '/vendor/autoload.php';

Env::load($basePath);

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

// Área privada (los controladores verifican Auth::requireLogin internamente)
$router->get('/dashboard',                       [DashboardController::class, 'index']);
$router->get('/programas/{slug}',                [ProgramController::class,   'show']);
$router->get('/programas/{slug}/dia/{day}',      [LessonController::class,    'show']);
$router->post('/progreso',                       [ProgressController::class,  'toggle']);

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

$router->dispatch();
