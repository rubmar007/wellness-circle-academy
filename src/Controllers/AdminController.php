<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database\Connection;
use App\View;

final class AdminController
{
    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireAdmin();

        $pdo = Connection::get();

        $totals = [
            'users'    => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'programs' => (int) $pdo->query('SELECT COUNT(*) FROM programs')->fetchColumn(),
            'lessons'  => (int) $pdo->query('SELECT COUNT(*) FROM lessons')->fetchColumn(),
        ];

        $programs = $pdo->query(
            'SELECT id, slug, title, is_published, display_order
               FROM programs
              ORDER BY display_order ASC, title ASC'
        )->fetchAll();

        View::render('admin/index', [
            'totals'   => $totals,
            'programs' => $programs,
        ]);
    }
}
