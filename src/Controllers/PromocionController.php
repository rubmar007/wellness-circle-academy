<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database\Connection;
use App\View;

final class PromocionController
{
    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireTeamMember();

        $promociones = Connection::get()->query(
            'SELECT id, title, body, image_path, video_url, link_url, link_label, created_at
               FROM announcements
              WHERE is_published = TRUE
              ORDER BY created_at DESC'
        )->fetchAll();

        View::render('promociones/index', [
            'promociones' => $promociones,
        ]);
    }
}
