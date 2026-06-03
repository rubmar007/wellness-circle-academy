<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database\Connection;
use App\View;

final class EventController
{
    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireLogin();

        $events = Connection::get()->query(
            "SELECT id, title, event_type, starts_at, join_url, description, image_url
               FROM events
              WHERE is_published = TRUE
                AND starts_at >= NOW() - interval '1 day'
              ORDER BY starts_at ASC"
        )->fetchAll();

        View::render('events/index', [
            'events' => $events,
        ]);
    }
}
