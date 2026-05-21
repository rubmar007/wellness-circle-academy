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
            return;
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
    public function logout(array $params): void
    {
        Csrf::requireValid();
        Auth::logout();
        View::redirect('/login');
    }
}
