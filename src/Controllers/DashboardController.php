<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database\Connection;
use App\View;

final class DashboardController
{
    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireLogin();

        $stmt = Connection::get()->query(
            'SELECT id, slug, title, description, cover_image, display_order
               FROM programs
              WHERE is_published = TRUE
              ORDER BY display_order ASC, title ASC'
        );
        $programs = $stmt !== false ? $stmt->fetchAll() : [];

        View::render('dashboard', [
            'programs' => $programs,
        ]);
    }
}
