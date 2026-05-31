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
        Auth::requireLogin();

        $rows = Connection::get()->query(
            'SELECT id, type, title, url, image_url, display_order, is_published
               FROM materials
              WHERE is_published = TRUE
              ORDER BY display_order ASC, id ASC'
        )->fetchAll();

        $pdfs   = [];
        $images = [];
        $links  = [];
        foreach ($rows as $row) {
            switch ($row['type']) {
                case 'pdf':
                    $pdfs[] = $row;
                    break;
                case 'image':
                    $images[] = $row;
                    break;
                case 'link':
                    $links[] = $row;
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
