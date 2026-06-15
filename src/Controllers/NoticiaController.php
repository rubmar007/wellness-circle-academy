<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database\Connection;
use App\View;

final class NoticiaController
{
    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireTeamMember();

        $noticias = Connection::get()->query(
            'SELECT id, title, body, image_path, video_url, link_url, link_label, created_at
               FROM noticias
              WHERE is_published = TRUE
              ORDER BY created_at DESC'
        )->fetchAll();

        View::render('noticias/index', [
            'noticias' => $noticias,
        ]);
    }
}
