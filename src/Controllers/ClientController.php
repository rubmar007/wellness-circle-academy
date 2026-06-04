<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database\Connection;
use App\View;

final class ClientController
{
    /** @param array<string,string> $params */
    public function show(array $params): void
    {
        Auth::requireLogin();

        $page = Connection::get()->query(
            'SELECT * FROM client_page WHERE id = 1'
        )->fetch();

        View::render('client/show', [
            'page' => is_array($page) ? $page : [],
        ]);
    }
}
