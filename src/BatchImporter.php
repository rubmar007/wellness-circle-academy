<?php

declare(strict_types=1);

namespace App;

use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Reader\Exception\ReaderException;
use RuntimeException;

/**
 * Lee un XLSX subido por el admin y devuelve filas validadas listas para
 * upsert en la tabla `lessons`. Procesa solo la primera hoja del libro.
 *
 * Estructura esperada (fila 1 = encabezados, ignorados por nombre exacto,
 * solo importa el orden de columnas):
 *   A: day_number        (entero, requerido)
 *   B: title             (string, requerido, máx. 200)
 *   C: objective         (string opcional, máx. 8000)
 *   D: post_text         (string opcional, máx. 8000)
 *   E: story_text        (string opcional, máx. 8000)
 *   F: conversation_text (string opcional, máx. 8000)
 *   G: action_text       (string opcional, máx. 8000)
 *   H: tip_text          (string opcional, máx. 8000)
 *   I: checklist         (string opcional, ítems separados por | o salto de línea, máx. 20 ítems)
 *   J: is_published      (1/0, sí/no, true/false; vacío = no publicado)
 */
final class BatchImporter
{
    public const COLUMN_COUNT = 10;

    public const HEADERS = [
        'day_number',
        'title',
        'objective',
        'post_text',
        'story_text',
        'conversation_text',
        'action_text',
        'tip_text',
        'checklist',
        'is_published',
    ];

    public const HEADERS_HUMAN = [
        'Día',
        'Título',
        'Objetivo',
        'Publicación',
        'Story',
        'Conversación',
        'Acción del día',
        'Tip',
        'Checklist (ítems separados por |)',
        'Publicado (1/0)',
    ];

    /**
     * Devuelve:
     *   [
     *     'rows'   => array<int, array{lineNumber:int, data:array<string,mixed>}>,
     *     'errors' => array<int, string>   // mensajes de error globales o por fila
     *   ]
     */
    public function read(string $path): array
    {
        $rows   = [];
        $errors = [];
        $seenDays = [];

        try {
            $reader = new XlsxReader();
            $reader->open($path);
        } catch (IOException | ReaderException $e) {
            return ['rows' => [], 'errors' => ['No se pudo abrir el archivo XLSX: ' . $e->getMessage()]];
        }

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $lineNumber = 0;

                foreach ($sheet->getRowIterator() as $row) {
                    $lineNumber++;

                    // Saltar encabezado.
                    if ($lineNumber === 1) {
                        continue;
                    }

                    $values = $row->toArray();

                    // Saltar filas completamente vacías.
                    if ($this->isRowEmpty($values)) {
                        continue;
                    }

                    $rowData = $this->extractCells($values);
                    $rowErrors = $this->validateRow($rowData, $lineNumber, $seenDays);

                    if ($rowErrors !== []) {
                        foreach ($rowErrors as $msg) {
                            $errors[] = 'Fila ' . $lineNumber . ': ' . $msg;
                        }
                        continue;
                    }

                    $seenDays[$rowData['day_number']] = true;

                    $rows[] = [
                        'lineNumber' => $lineNumber,
                        'data'       => $rowData,
                    ];
                }

                // Solo procesamos la primera hoja.
                break;
            }
        } finally {
            $reader->close();
        }

        if ($rows === [] && $errors === []) {
            $errors[] = 'El archivo no contiene filas con datos.';
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /** @param array<int, mixed> $values */
    private function isRowEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && (is_string($value) ? trim($value) !== '' : true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array<int, mixed> $raw
     * @return array<string, mixed>
     */
    private function extractCells(array $raw): array
    {
        $values = array_fill(0, self::COLUMN_COUNT, '');
        $i = 0;
        foreach ($raw as $v) {
            if ($i >= self::COLUMN_COUNT) {
                break;
            }
            if ($v === null) {
                $values[$i] = '';
            } elseif (is_bool($v)) {
                $values[$i] = $v ? '1' : '0';
            } elseif (is_object($v) && method_exists($v, 'format')) {
                // DateTime u objetos similares.
                $values[$i] = $v->format('Y-m-d');
            } else {
                $values[$i] = (string) $v;
            }
            $i++;
        }

        return [
            'day_number'        => $this->parseDayNumber($values[0]),
            'title'             => trim($values[1]),
            'objective'         => $this->cleanLongText($values[2]),
            'post_text'         => $this->cleanLongText($values[3]),
            'story_text'        => $this->cleanLongText($values[4]),
            'conversation_text' => $this->cleanLongText($values[5]),
            'action_text'       => $this->cleanLongText($values[6]),
            'tip_text'          => $this->cleanLongText($values[7]),
            'checklist_items'   => $this->parseChecklist($values[8]),
            'is_published'      => $this->parseBool($values[9]),
        ];
    }

    private function parseDayNumber(string $raw): int
    {
        $raw = trim($raw);
        // Algunos editores guardan números enteros como "1.0".
        if (preg_match('/^([1-9][0-9]{0,3})(\.0+)?$/', $raw, $m) === 1) {
            return (int) $m[1];
        }
        return 0;
    }

    private function cleanLongText(string $raw): string
    {
        // Normaliza saltos de línea de Windows a \n y trimea extremos.
        $clean = str_replace(["\r\n", "\r"], "\n", $raw);
        return trim($clean);
    }

    /**
     * Acepta "item1|item2|item3" o "item1\nitem2\nitem3" o mezcla.
     * @return array<int, string>
     */
    private function parseChecklist(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $parts = preg_split('/[|\n]/', $raw) ?: [];
        $items = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $items[] = mb_substr($p, 0, 200);
            }
            if (count($items) >= 20) {
                break;
            }
        }
        return $items;
    }

    private function parseBool(string $raw): bool
    {
        $r = mb_strtolower(trim($raw));
        return in_array($r, ['1', 'true', 'verdadero', 'si', 'sí', 'yes', 'y', 'x', 'publicado'], true);
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, bool>     $seenDays
     * @return array<int, string>
     */
    private function validateRow(array $row, int $lineNumber, array $seenDays): array
    {
        $errors = [];

        if ($row['day_number'] <= 0 || $row['day_number'] > 9999) {
            $errors[] = 'Día inválido (debe ser un entero entre 1 y 9999).';
        } else {
            if (isset($seenDays[$row['day_number']])) {
                $errors[] = 'Día ' . $row['day_number'] . ' duplicado en este archivo.';
            }
        }

        if ($row['title'] === '' || mb_strlen($row['title']) > 200) {
            $errors[] = 'Título obligatorio (máx. 200 caracteres).';
        }

        foreach (['objective', 'post_text', 'story_text', 'conversation_text', 'action_text', 'tip_text'] as $field) {
            if (mb_strlen((string) $row[$field]) > 8000) {
                $errors[] = 'Campo "' . $field . '" demasiado largo (máx. 8000 caracteres).';
            }
        }

        $checklistTotal = array_sum(array_map('mb_strlen', $row['checklist_items']));
        if ($checklistTotal > 4000) {
            $errors[] = 'Checklist demasiado larga (máx. 4000 caracteres entre todos los ítems).';
        }

        return $errors;
    }
}
