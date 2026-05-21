<?php

declare(strict_types=1);

/**
 * Script CLI para crear usuarios en Wellness Circle Academy.
 *
 * Uso:
 *   php bin/create-user.php
 *
 * El script pide nombre, email, rol (admin|member) y contraseña de forma
 * interactiva. La contraseña NO se hace echo y NO queda en el historial del
 * shell. Sirve para crear el primer admin y para crear nuevos usuarios
 * mientras no exista el panel admin completo en la UI.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script solo se ejecuta por CLI.\n");
    exit(1);
}

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';

use App\Auth;
use App\Database\Connection;
use App\Support\Env;

Env::load($basePath);

fwrite(STDOUT, "Crear usuario en Wellness Circle Academy\n");
fwrite(STDOUT, "----------------------------------------\n");

$name  = prompt('Nombre completo: ');
$email = mb_strtolower(trim(prompt('Email: ')));
$role  = trim(prompt('Rol [admin/member] (default: member): ')) ?: 'member';

if (!in_array($role, ['admin', 'member'], true)) {
    fwrite(STDERR, "Rol inválido. Debe ser 'admin' o 'member'.\n");
    exit(2);
}
if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 120) {
    fwrite(STDERR, "Nombre inválido (2 a 120 caracteres).\n");
    exit(2);
}
if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 190) {
    fwrite(STDERR, "Email inválido.\n");
    exit(2);
}
if (Auth::emailExists($email)) {
    fwrite(STDERR, "Ese email ya está registrado.\n");
    exit(2);
}

$password  = promptPassword('Contraseña (mín. 10 caracteres): ');
$password2 = promptPassword('Confirma contraseña:           ');

if (mb_strlen($password) < 10) {
    fwrite(STDERR, "La contraseña debe tener al menos 10 caracteres.\n");
    exit(2);
}
if ($password !== $password2) {
    fwrite(STDERR, "Las contraseñas no coinciden.\n");
    exit(2);
}

$id = Auth::createUser($name, $email, $password, $role);

fwrite(STDOUT, sprintf("\nUsuario creado: #%d %s <%s> rol=%s\n", $id, $name, $email, $role));
exit(0);

// -----------------------------------------------------------------

function prompt(string $label): string
{
    fwrite(STDOUT, $label);
    $line = fgets(STDIN);
    if ($line === false) {
        fwrite(STDERR, "\nCancelado.\n");
        exit(130);
    }
    return rtrim($line, "\r\n");
}

function promptPassword(string $label): string
{
    fwrite(STDOUT, $label);

    // Intenta apagar el echo si hay TTY (stty); si no, lee normal con aviso.
    $hasStty = false;
    if (function_exists('shell_exec')) {
        $sttyCheck = @shell_exec('stty -a 2>/dev/null');
        $hasStty = is_string($sttyCheck) && $sttyCheck !== '';
    }

    if ($hasStty) {
        $oldStty = trim((string) shell_exec('stty -g'));
        shell_exec('stty -echo');
        $line = fgets(STDIN);
        shell_exec('stty ' . escapeshellarg($oldStty));
        fwrite(STDOUT, "\n");
    } else {
        fwrite(STDOUT, "(aviso: no se pudo ocultar la entrada) ");
        $line = fgets(STDIN);
    }

    if ($line === false) {
        fwrite(STDERR, "\nCancelado.\n");
        exit(130);
    }
    return rtrim($line, "\r\n");
}
