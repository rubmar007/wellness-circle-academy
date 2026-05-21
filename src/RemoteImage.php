<?php

declare(strict_types=1);

namespace App;

use App\Support\Env;
use RuntimeException;
use finfo;

/**
 * Descarga imágenes desde URLs externas (whitelisted) y las guarda en
 * /public/assets/uploads/ con UUID rename y validación MIME real.
 *
 * Defensas anti-SSRF y de contenido:
 *  - Solo HTTPS.
 *  - Whitelist estricta de hosts: drive.google.com, googleusercontent.com,
 *    drive.usercontent.google.com.
 *  - Validación de hop final: si cURL es redirigido fuera del whitelist
 *    durante el follow, falla.
 *  - Tamaño máximo (UPLOAD_MAX_BYTES, default 5 MB).
 *  - MIME real determinado con finfo sobre el archivo descargado, NO
 *    confiando en Content-Type del servidor remoto.
 *  - Solo JPG/PNG/WebP.
 *
 * Soporta URLs de Google Drive en cualquiera de los formatos comunes y
 * las normaliza al endpoint de descarga directa.
 */
final class RemoteImage
{
    /** @var array<int,string> Hosts permitidos como destino INICIAL y como HOPS de redirección. */
    private const ALLOWED_HOSTS = [
        'drive.google.com',
        'drive.usercontent.google.com',
        'docs.google.com',
        // Dominios de googleusercontent (varios subdominios) los validamos por sufijo.
    ];

    /** @var array<string,string> */
    private const ALLOWED_IMAGE_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Descarga la imagen apuntada por la URL y devuelve la ruta pública local.
     */
    public static function fetchToUploads(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            throw new RuntimeException('URL vacía.');
        }

        $normalized = self::normalizeUrl($url);
        self::assertHostAllowed($normalized);

        $maxBytes = Env::int('UPLOAD_MAX_BYTES', 5_242_880);

        $tmp = tempnam(sys_get_temp_dir(), 'wca_dl_');
        if ($tmp === false) {
            throw new RuntimeException('No se pudo crear archivo temporal.');
        }
        $fp = fopen($tmp, 'wb');
        if ($fp === false) {
            @unlink($tmp);
            throw new RuntimeException('No se pudo abrir archivo temporal.');
        }

        $bytesWritten = 0;
        $overflow     = false;

        $ch = curl_init($normalized);
        if ($ch === false) {
            fclose($fp);
            @unlink($tmp);
            throw new RuntimeException('No se pudo inicializar cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_WRITEFUNCTION   => static function ($ch, string $chunk) use (&$bytesWritten, &$overflow, $maxBytes, $fp): int {
                $bytesWritten += strlen($chunk);
                if ($bytesWritten > $maxBytes) {
                    $overflow = true;
                    return 0; // Abortar la descarga.
                }
                return fwrite($fp, $chunk) ?: 0;
            },
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_MAXREDIRS       => 5,
            CURLOPT_PROTOCOLS       => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_SSL_VERIFYHOST  => 2,
            CURLOPT_USERAGENT       => 'wca-fetch/1.0',
            // Cabecera Accept de imagen; muchos servidores responden de forma
            // distinta si esperan navegador.
            CURLOPT_HTTPHEADER      => ['Accept: image/*'],
        ]);

        $ok        = curl_exec($ch);
        $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl  = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $curlError = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        try {
            if ($overflow) {
                $mb = (int) round($maxBytes / 1024 / 1024);
                throw new RuntimeException('La imagen remota supera ' . $mb . ' MB.');
            }
            if ($ok === false || $bytesWritten === 0) {
                error_log('[wca] RemoteImage: cURL falló: ' . $curlError . ' final=' . $finalUrl);
                throw new RuntimeException('No se pudo descargar la imagen remota.');
            }
            if ($httpCode < 200 || $httpCode >= 300) {
                throw new RuntimeException('El servidor remoto respondió HTTP ' . $httpCode . '.');
            }
            self::assertHostAllowed($finalUrl);

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($tmp);
            if (!is_string($mime) || !isset(self::ALLOWED_IMAGE_MIME[$mime])) {
                error_log('[wca] RemoteImage: MIME no permitido tras descarga: ' . var_export($mime, true) . ' url=' . $normalized);
                throw new RuntimeException('El recurso descargado no es una imagen JPG/PNG/WebP. ¿El archivo está como "cualquier persona con el enlace puede ver"?');
            }

            $ext  = self::ALLOWED_IMAGE_MIME[$mime];
            $name = bin2hex(random_bytes(16)) . '.' . $ext;
            $dir  = dirname(__DIR__) . '/public/assets/uploads';

            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException('No se pudo preparar el destino de subida.');
            }

            $dest = $dir . '/' . $name;
            if (!rename($tmp, $dest)) {
                // rename puede fallar entre filesystems; fallback a copy+unlink.
                if (!copy($tmp, $dest)) {
                    throw new RuntimeException('No se pudo guardar la imagen descargada.');
                }
                @unlink($tmp);
            }
            @chmod($dest, 0644);

            return '/assets/uploads/' . $name;
        } catch (\Throwable $e) {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
            throw $e;
        }
    }

    /**
     * Normaliza URLs de Google Drive a la versión directa de descarga.
     * Otras URLs HTTPS se devuelven tal cual (a validar después por host).
     */
    private static function normalizeUrl(string $url): string
    {
        if (preg_match('#^https://drive\.google\.com/file/d/([a-zA-Z0-9_-]{10,})#', $url, $m) === 1) {
            return 'https://drive.google.com/uc?export=download&id=' . $m[1];
        }
        if (preg_match('#^https://drive\.google\.com/open\?id=([a-zA-Z0-9_-]{10,})#', $url, $m) === 1) {
            return 'https://drive.google.com/uc?export=download&id=' . $m[1];
        }
        // Ya es la URL directa o pertenece a otro host whitelisted; devolver tal cual.
        return $url;
    }

    private static function assertHostAllowed(string $url): void
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            throw new RuntimeException('URL malformada.');
        }
        if (mb_strtolower($parts['scheme']) !== 'https') {
            throw new RuntimeException('Solo se aceptan URLs HTTPS.');
        }

        $host = mb_strtolower($parts['host']);

        if (in_array($host, self::ALLOWED_HOSTS, true)) {
            return;
        }
        // Permitimos cualquier subdominio de googleusercontent.com porque
        // Drive redirige a un host dinámico (lh3.googleusercontent.com,
        // drive.usercontent.google.com, etc.).
        if (str_ends_with($host, '.googleusercontent.com') || $host === 'googleusercontent.com') {
            return;
        }

        throw new RuntimeException('URL fuera de la lista permitida (' . $host . '). Solo se aceptan links de Google Drive.');
    }
}
