<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Support\PatchMapData;
use App\View;

final class PatchMapController
{
    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireLogin();

        View::render('patchmap/index', [
            'patches' => PatchMapData::patches(),
            'kits'    => PatchMapData::kits(),
        ]);
    }
}
