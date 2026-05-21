<?php

declare(strict_types=1);

/**
 * Helpers globales usados por las plantillas.
 *
 * Este archivo NO declara namespace a propósito: las funciones aquí definidas
 * viven en el namespace raíz y se invocan como e(...) / e_nl2br(...) desde
 * cualquier template.
 */

if (!function_exists('e')) {
    /**
     * Escape de HTML. Convierte cualquier valor a string seguro para HTML.
     */
    function e(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            if ($value === false) {
                $value = '';
            }
        }
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('e_nl2br')) {
    /**
     * Convierte texto plano a HTML preservando saltos de línea. Escapado siempre.
     */
    function e_nl2br(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return nl2br(htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);
    }
}
