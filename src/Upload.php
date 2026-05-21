<?php

declare(strict_types=1);

namespace App;

use App\Support\Env;
use RuntimeException;
use finfo;

final class Upload
{
    /** @var array<string,string> Whitelist de MIME types reales -> extensión final. */
    private const ALLOWED_IMAGE_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Sube una imagen y devuelve la URL pública relativa lista para guardar en BD.
     *
     * @param array{name:string, type:string, tmp_name:string, error:int, size:int}|null $file
     * @return string Ruta pública (ej: /assets/uploads/abc.jpg) o cadena vacía si no se subió nada.
     * @throws RuntimeException Si el archivo se intentó subir pero es inválido.
     */
    public static function image(?array $file): string
    {
        if ($file === null || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return '';
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::errorMessage($file['error']));
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            error_log('[wca] Upload::image: tmp_name no es archivo subido válido');
            throw new RuntimeException('Archivo de subida inválido.');
        }

        $maxBytes = Env::int('UPLOAD_MAX_BYTES', 5_242_880);
        if ($file['size'] > $maxBytes) {
            $mb = (int) round($maxBytes / 1024 / 1024);
            throw new RuntimeException('El archivo supera el tamaño máximo de ' . $mb . ' MB.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!is_string($mime) || !isset(self::ALLOWED_IMAGE_MIME[$mime])) {
            error_log('[wca] Upload::image: MIME no permitido: ' . var_export($mime, true));
            throw new RuntimeException('Formato no permitido. Solo JPG, PNG o WebP.');
        }

        $ext   = self::ALLOWED_IMAGE_MIME[$mime];
        $name  = bin2hex(random_bytes(16)) . '.' . $ext;
        $dir   = dirname(__DIR__) . '/public/assets/uploads';

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            error_log('[wca] Upload::image: no se pudo crear ' . $dir);
            throw new RuntimeException('No se pudo preparar el destino de subida.');
        }

        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            error_log('[wca] Upload::image: move_uploaded_file falló para ' . $file['name']);
            throw new RuntimeException('No se pudo guardar el archivo.');
        }

        @chmod($dest, 0644);

        return '/assets/uploads/' . $name;
    }

    /**
     * Elimina una imagen subida previamente. Falla silencioso si no existe o
     * si la ruta no pertenece a la carpeta de uploads (defensa contra path
     * traversal en datos legacy de BD).
     */
    public static function deleteImage(?string $publicPath): void
    {
        if (!is_string($publicPath) || $publicPath === '') {
            return;
        }
        if (!str_starts_with($publicPath, '/assets/uploads/')) {
            return;
        }
        $name = basename($publicPath);
        if (preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name) !== 1) {
            return;
        }
        $path = dirname(__DIR__) . '/public/assets/uploads/' . $name;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private static function errorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño permitido por el servidor.',
            UPLOAD_ERR_PARTIAL   => 'La subida se interrumpió. Intenta de nuevo.',
            UPLOAD_ERR_NO_TMP_DIR=> 'Falta carpeta temporal del servidor (config PHP).',
            UPLOAD_ERR_CANT_WRITE=> 'El servidor no pudo escribir el archivo.',
            UPLOAD_ERR_EXTENSION => 'La subida fue bloqueada por una extensión PHP.',
            default              => 'Error desconocido al subir el archivo (code ' . $code . ').',
        };
    }
}
