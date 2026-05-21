<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\View;

final class AuthController
{
    /** @param array<string,string> $params */
    public function showLogin(array $params): void
    {
        if (Auth::check()) {
            View::redirect('/dashboard');
        }

        View::render('auth/login', [
            'errors' => [],
            'old'    => ['email' => ''],
        ]);
    }

    /** @param array<string,string> $params */
    public function login(array $params): void
    {
        Csrf::requireValid();

        $email    = trim((string) ($_POST['email']    ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $errors = [];
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Email inválido.';
        }
        if ($password === '') {
            $errors['password'] = 'Ingresa tu contraseña.';
        }

        if ($errors !== []) {
            View::render('auth/login', [
                'errors' => $errors,
                'old'    => ['email' => $email],
            ]);
            return;
        }

        $result = Auth::attemptLogin($email, $password);

        if (!$result['ok']) {
            View::render('auth/login', [
                'errors' => ['general' => $result['error']],
                'old'    => ['email' => $email],
            ]);
            return;
        }

        View::redirect('/dashboard');
    }

    /** @param array<string,string> $params */
    public function showRegister(array $params): void
    {
        if (Auth::check()) {
            View::redirect('/dashboard');
        }

        View::render('auth/register', [
            'errors' => [],
            'old'    => ['name' => '', 'email' => ''],
        ]);
    }

    /** @param array<string,string> $params */
    public function register(array $params): void
    {
        Csrf::requireValid();

        $name      = trim((string) ($_POST['name']      ?? ''));
        $email     = trim((string) ($_POST['email']     ?? ''));
        $password  = (string) ($_POST['password']  ?? '');
        $password2 = (string) ($_POST['password2'] ?? '');

        $errors = [];

        if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors['name'] = 'El nombre debe tener entre 2 y 120 caracteres.';
        }
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 190) {
            $errors['email'] = 'Email inválido.';
        }
        if (mb_strlen($password) < 10) {
            $errors['password'] = 'La contraseña debe tener al menos 10 caracteres.';
        }
        if ($password !== $password2) {
            $errors['password2'] = 'Las contraseñas no coinciden.';
        }
        if (!isset($errors['email']) && Auth::emailExists($email)) {
            $errors['email'] = 'Ese email ya está registrado.';
        }

        if ($errors !== []) {
            View::render('auth/register', [
                'errors' => $errors,
                'old'    => ['name' => $name, 'email' => $email],
            ]);
            return;
        }

        Auth::createUser($name, $email, $password, 'member');

        $login = Auth::attemptLogin($email, $password);
        if (!$login['ok']) {
            View::redirect('/login');
        }

        View::redirect('/dashboard');
    }

    /** @param array<string,string> $params */
    public function logout(array $params): void
    {
        Csrf::requireValid();
        Auth::logout();
        View::redirect('/');
    }
}
