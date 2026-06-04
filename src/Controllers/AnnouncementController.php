<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database\Connection;
use App\View;

final class AnnouncementController
{
    /**
     * Whitelist de tipos de anuncio: clave => ['label' => etiqueta, 'emoji' => medalla].
     *
     * @var array<string, array{label:string, emoji:string}>
     */
    public const KINDS = [
        'reconocimiento' => ['label' => 'Reconocimiento', 'emoji' => '🏅'],
        'aviso'          => ['label' => 'Aviso',          'emoji' => '📣'],
    ];

    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireTeamMember();

        $announcements = Connection::get()->query(
            'SELECT id, kind, title, body, is_published, created_at
               FROM announcements
              WHERE is_published = TRUE
              ORDER BY created_at DESC'
        )->fetchAll();

        View::render('announcements/index', [
            'announcements' => $announcements,
            'kinds'         => self::KINDS,
        ]);
    }
}
