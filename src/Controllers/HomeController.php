<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\View;

final class HomeController
{
    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        if (Auth::check()) {
            View::redirect('/dashboard');
        }

        View::render('home');
    }
}
