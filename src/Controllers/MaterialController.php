<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database\Connection;
use App\View;

final class MaterialController
{
    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireTeamMember();

        $rows = Connection::get()->query(
            'SELECT id, type, title, url, image_url, folder, description, display_order, is_published
               FROM materials
              WHERE is_published = TRUE
              ORDER BY folder ASC NULLS FIRST, display_order ASC, id ASC'
        )->fetchAll();

        $pdfs   = [];
        $images = [];
        $links  = [];
        foreach ($rows as $row) {
            $folder = $row['folder'] !== null && $row['folder'] !== '' ? $row['folder'] : '';
            switch ($row['type']) {
                case 'pdf':
                    $pdfs[$folder][] = $row;
                    break;
                case 'image':
                    $images[$folder][] = $row;
                    break;
                case 'link':
                    $links[$folder][] = $row;
                    break;
            }
        }

        View::render('materials/index', [
            'pdfs'   => $pdfs,
            'images' => $images,
            'links'  => $links,
        ]);
    }
}
