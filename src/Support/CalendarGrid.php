<?php

declare(strict_types=1);

namespace App\Support;

use DateTime;

/**
 * Cuadrícula mensual de 42 celdas (6 semanas de domingo a sábado) que cubren
 * un mes, para las vistas de calendario tipo Google Calendar (admin y
 * público de /eventos). Compartido para no repetir el cálculo de celdas en
 * cada controlador — cada uno sigue haciendo su propio SELECT de eventos
 * (columnas y filtros distintos: admin ve borradores, público no).
 */
final class CalendarGrid
{
    /** Resuelve el primer día del mes desde un query param ?mes=YYYY-MM (o el mes actual si falta o es inválido). */
    public static function monthStartFromParam(string $param): DateTime
    {
        $monthStart = DateTime::createFromFormat('Y-m-d', $param . '-01');
        if ($monthStart === false) {
            $monthStart = new DateTime('first day of this month');
        }
        $monthStart->setTime(0, 0);
        return $monthStart;
    }

    /** Primer día (domingo) de la cuadrícula de 42 celdas que cubre el mes dado. */
    public static function gridStart(DateTime $monthStart): DateTime
    {
        $gridStart = clone $monthStart;
        $gridStart->modify('-' . $gridStart->format('w') . ' days');
        return $gridStart;
    }

    /**
     * @param array<int, array<string,mixed>> $events cada fila con clave 'starts_at'
     * @return array<int, array{date: DateTime, inMonth: bool, events: array<int, array<string,mixed>>}>
     */
    public static function buildCells(DateTime $monthStart, DateTime $gridStart, array $events): array
    {
        $byDay = [];
        foreach ($events as $ev) {
            $key = (new DateTime((string) $ev['starts_at']))->format('Y-m-d');
            $byDay[$key][] = $ev;
        }

        $cells  = [];
        $cursor = clone $gridStart;
        for ($i = 0; $i < 42; $i++) {
            $key     = $cursor->format('Y-m-d');
            $cells[] = [
                'date'    => clone $cursor,
                'inMonth' => $cursor->format('Y-m') === $monthStart->format('Y-m'),
                'events'  => $byDay[$key] ?? [],
            ];
            $cursor->modify('+1 day');
        }
        return $cells;
    }
}
