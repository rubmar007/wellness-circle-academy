<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database\Connection;
use App\View;

final class TrainingController
{
    /** @var array<string,string> */
    public const CATEGORIES = [
        'plan_compensacion' => 'Plan de Compensación',
        'clientes'          => 'Clientes',
        'autoenvio'         => 'Autoenvío',
        'oficina_virtual'   => 'Oficina virtual',
        'doctores'          => 'Doctores',
    ];

    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireLogin();

        $rows = Connection::get()->query(
            'SELECT id, category, title, video_url, display_order, is_published
               FROM trainings
              WHERE is_published = TRUE
              ORDER BY display_order ASC, id ASC'
        )->fetchAll();

        // Agrupa por categoría en el orden de CATEGORIES.
        $grouped = [];
        foreach (self::CATEGORIES as $key => $label) {
            $grouped[$key] = [];
        }
        foreach ($rows as $row) {
            $cat = (string) $row['category'];
            if (isset($grouped[$cat])) {
                $grouped[$cat][] = $row;
            }
        }

        View::render('trainings/index', [
            'categories' => self::CATEGORIES,
            'grouped'    => $grouped,
        ]);
    }
}
