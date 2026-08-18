<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database\Connection;
use App\Support\CalendarGrid;
use App\View;
use DateTime;

final class EventController
{
    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireLogin();

        $vista = ($_GET['vista'] ?? '') === 'mes' ? 'mes' : 'semana';

        // Vista Semanal (default): domingo a sábado de la semana actual, tal
        // como se mostraba antes (sin límite de tiempo) pero ahora acotado a
        // la semana en curso — Rub pidió que sea la vista que abre por
        // defecto.
        $today     = new DateTime('today');
        $weekStart = (clone $today)->modify('-' . $today->format('w') . ' days');
        $weekEnd   = (clone $weekStart)->modify('+7 days');

        $stmt = Connection::get()->prepare(
            "SELECT id, title, event_type, starts_at, join_url, description, image_url
               FROM events
              WHERE is_published = TRUE
                AND starts_at >= :start AND starts_at < :end
              ORDER BY starts_at ASC"
        );
        $stmt->execute([
            ':start' => $weekStart->format('Y-m-d H:i:s'),
            ':end'   => $weekEnd->format('Y-m-d H:i:s'),
        ]);
        $weekEvents = $stmt->fetchAll();

        // Vista Mes completo: calendario mensual tipo Google Calendar (mismo
        // componente que /admin/eventos/calendario), solo con eventos
        // publicados.
        $monthStart = CalendarGrid::monthStartFromParam(trim((string) ($_GET['mes'] ?? '')));
        $gridStart  = CalendarGrid::gridStart($monthStart);
        $gridEnd    = (clone $gridStart)->modify('+42 days');

        $stmt = Connection::get()->prepare(
            "SELECT id, title, event_type, starts_at, join_url
               FROM events
              WHERE is_published = TRUE
                AND starts_at >= :start AND starts_at < :end
              ORDER BY starts_at ASC"
        );
        $stmt->execute([
            ':start' => $gridStart->format('Y-m-d H:i:s'),
            ':end'   => $gridEnd->format('Y-m-d H:i:s'),
        ]);
        $cells = CalendarGrid::buildCells($monthStart, $gridStart, $stmt->fetchAll());

        View::render('events/index', [
            'events'     => $weekEvents,
            'vista'      => $vista,
            'monthStart' => $monthStart,
            'cells'      => $cells,
            'prevMonth'  => (clone $monthStart)->modify('-1 month')->format('Y-m'),
            'nextMonth'  => (clone $monthStart)->modify('+1 month')->format('Y-m'),
        ]);
    }
}
