<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Database\Connection;
use App\Embed;
use App\Upload;
use App\View;
use RuntimeException;

final class AdminClientController
{
    /** @param array<string,string> $params */
    public function edit(array $params): void
    {
        Auth::requireAdmin();

        $page = Connection::get()->query(
            'SELECT * FROM client_page WHERE id = 1'
        )->fetch();

        View::render('admin/client/edit', [
            'page'   => is_array($page) ? $page : [],
            'errors' => [],
            'flash'  => self::popFlash(),
        ]);
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $pdo  = Connection::get();
        $page = $pdo->query('SELECT * FROM client_page WHERE id = 1')->fetch();
        $page = is_array($page) ? $page : [];

        $errors = [];

        $uso_texto            = trim((string) ($_POST['uso_texto']            ?? ''));
        $activar_texto        = trim((string) ($_POST['activar_texto']        ?? ''));
        $activar_video_url    = trim((string) ($_POST['activar_video_url']    ?? ''));
        $desactivar_texto     = trim((string) ($_POST['desactivar_texto']     ?? ''));
        $desactivar_video_url = trim((string) ($_POST['desactivar_video_url'] ?? ''));
        $preferente_texto     = trim((string) ($_POST['preferente_texto']     ?? ''));
        $preferente_video_url = trim((string) ($_POST['preferente_video_url'] ?? ''));
        $texto_libre          = trim((string) ($_POST['texto_libre']          ?? ''));

        if ($activar_video_url !== '' && Embed::parseVideo($activar_video_url) === null) {
            $errors['activar_video_url'] = 'URL de YouTube o Vimeo no válida.';
        }
        if ($desactivar_video_url !== '' && Embed::parseVideo($desactivar_video_url) === null) {
            $errors['desactivar_video_url'] = 'URL de YouTube o Vimeo no válida.';
        }
        if ($preferente_video_url !== '' && Embed::parseVideo($preferente_video_url) === null) {
            $errors['preferente_video_url'] = 'URL de YouTube o Vimeo no válida.';
        }

        // --- Imágenes ---
        $imageFields = [
            'welcome_image'         => 'welcome_image_url',
            'beneficios_autoenvio'  => 'beneficios_autoenvio_url',
            'beneficios_preferente' => 'beneficios_preferente_url',
        ];

        $newUploads  = [];
        $finalImages = [];

        foreach ($imageFields as $inputName => $colName) {
            $existing  = (string) ($page[$colName] ?? '');
            $file      = $_FILES[$inputName] ?? null;
            $hasUpload = is_array($file)
                && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

            if ($hasUpload) {
                try {
                    $newPath = Upload::image($file);
                    $newUploads[$colName]  = $newPath;
                    $finalImages[$colName] = $newPath;
                } catch (RuntimeException $e) {
                    $errors[$inputName]    = $e->getMessage();
                    $finalImages[$colName] = $existing;
                }
            } else {
                $finalImages[$colName] = $existing;
            }
        }

        // --- PDF ---
        $existingPdf  = (string) ($page['uso_pdf_url'] ?? '');
        $pdfFile      = $_FILES['uso_pdf'] ?? null;
        $hasPdfUpload = is_array($pdfFile)
            && (int) ($pdfFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $newPdfPath   = null;
        $finalPdf     = $existingPdf;

        if ($hasPdfUpload) {
            try {
                $newPdfPath = Upload::pdf($pdfFile);
                $finalPdf   = $newPdfPath;
            } catch (RuntimeException $e) {
                $errors['uso_pdf'] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            foreach ($newUploads as $path) {
                Upload::deleteImage($path);
            }
            if ($newPdfPath !== null) {
                Upload::deletePdf($newPdfPath);
            }
            View::render('admin/client/edit', [
                'page'   => $page,
                'errors' => $errors,
                'flash'  => null,
            ]);
            return;
        }

        // Borrar archivos anteriores reemplazados
        foreach ($imageFields as $inputName => $colName) {
            $existing = (string) ($page[$colName] ?? '');
            if (isset($newUploads[$colName]) && $existing !== '' && $existing !== $newUploads[$colName]) {
                Upload::deleteImage($existing);
            }
        }
        if ($newPdfPath !== null && $existingPdf !== '' && $existingPdf !== $newPdfPath) {
            Upload::deletePdf($existingPdf);
        }

        $stmt = $pdo->prepare(
            'UPDATE client_page SET
                welcome_image_url         = :wi,
                uso_texto                 = :ut,
                uso_pdf_url               = :up,
                activar_texto             = :at,
                activar_video_url         = :av,
                desactivar_texto          = :dt,
                desactivar_video_url      = :dv,
                preferente_texto          = :pt,
                preferente_video_url      = :pv,
                beneficios_autoenvio_url  = :ba,
                beneficios_preferente_url = :bp,
                texto_libre               = :tl,
                updated_at                = NOW()
             WHERE id = 1'
        );
        $stmt->execute([
            ':wi' => $finalImages['welcome_image_url']         !== '' ? $finalImages['welcome_image_url']         : null,
            ':ut' => $uso_texto           !== '' ? $uso_texto           : null,
            ':up' => $finalPdf            !== '' ? $finalPdf            : null,
            ':at' => $activar_texto       !== '' ? $activar_texto       : null,
            ':av' => $activar_video_url   !== '' ? $activar_video_url   : null,
            ':dt' => $desactivar_texto    !== '' ? $desactivar_texto    : null,
            ':dv' => $desactivar_video_url !== '' ? $desactivar_video_url : null,
            ':pt' => $preferente_texto    !== '' ? $preferente_texto    : null,
            ':pv' => $preferente_video_url !== '' ? $preferente_video_url : null,
            ':ba' => $finalImages['beneficios_autoenvio_url']  !== '' ? $finalImages['beneficios_autoenvio_url']  : null,
            ':bp' => $finalImages['beneficios_preferente_url'] !== '' ? $finalImages['beneficios_preferente_url'] : null,
            ':tl' => $texto_libre !== '' ? $texto_libre : null,
        ]);

        self::setFlash('Página Soy Cliente actualizada.');
        View::redirect('/admin/cliente');
    }

    private static function setFlash(string $msg, string $type = 'success'): void
    {
        $_SESSION['_flash'] = ['type' => $type, 'msg' => $msg];
    }

    /** @return array{type:string,msg:string}|null */
    private static function popFlash(): ?array
    {
        if (!isset($_SESSION['_flash'])) {
            return null;
        }
        $flash = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return is_array($flash) ? $flash : null;
    }
}
