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

final class AdminMaterialsController
{
    /** @var array<string,string> Tipos permitidos => etiqueta. */
    public const TYPES = [
        'pdf'   => 'PDF',
        'image' => 'Imagen',
        'link'  => 'Enlace',
    ];

    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireAdmin();

        $materials = Connection::get()->query(
            'SELECT id, type, title, url, image_url, display_order, is_published
               FROM materials
              ORDER BY display_order ASC, id ASC'
        )->fetchAll();

        View::render('admin/materials/index', [
            'materials' => $materials,
            'flash'     => self::popFlash(),
        ]);
    }

    /** @param array<string,string> $params */
    public function create(array $params): void
    {
        Auth::requireAdmin();

        View::render('admin/materials/form', [
            'mode'     => 'create',
            'material' => null,
            'errors'   => [],
            'old'      => [
                'type'          => 'pdf',
                'title'         => '',
                'url'           => '',
                'display_order' => '0',
                'is_published'  => '1',
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function store(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $data   = self::extractInput();
        $hasFileUpload = isset($_FILES['image']) && is_array($_FILES['image'])
            && (int) ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        // Sube la imagen ANTES de validar/insertar, solo si type=image.
        $imageUrl = '';
        $uploadError = null;
        if ($data['type'] === 'image' && $hasFileUpload) {
            try {
                $imageUrl = Upload::image($_FILES['image'] ?? null);
            } catch (RuntimeException $e) {
                $uploadError = $e->getMessage();
            }
        }

        $errors = self::validate($data, $imageUrl, $uploadError);

        if ($errors !== []) {
            if ($imageUrl !== '') {
                Upload::deleteImage($imageUrl);
            }
            View::render('admin/materials/form', [
                'mode'     => 'create',
                'material' => null,
                'errors'   => $errors,
                'old'      => $data,
            ]);
            return;
        }

        // Normaliza columnas según tipo.
        $url      = $data['type'] === 'image' ? null : $data['url'];
        $imageCol = $data['type'] === 'image' ? $imageUrl : null;

        $folder = $data['folder'] !== '' ? $data['folder'] : null;

        $stmt = Connection::get()->prepare(
            'INSERT INTO materials (type, title, url, image_url, folder, display_order, is_published)
             VALUES (:ty, :ti, :u, :im, :fo, :o, :p)'
        );
        $stmt->execute([
            ':ty' => $data['type'],
            ':ti' => $data['title'],
            ':u'  => $url,
            ':im' => $imageCol,
            ':fo' => $folder,
            ':o'  => (int) $data['display_order'],
            ':p'  => $data['is_published'] === '1' ? 't' : 'f',
        ]);

        self::setFlash('Material creado.');
        View::redirect('/admin/materiales');
    }

    /** @param array<string,string> $params */
    public function edit(array $params): void
    {
        Auth::requireAdmin();
        $id = self::parseId($params['id'] ?? '');
        $material = self::findMaterial($id);
        if (!$material) {
            self::redirect404();
            return;
        }

        View::render('admin/materials/form', [
            'mode'     => 'edit',
            'material' => $material,
            'errors'   => [],
            'old'      => [
                'type'          => $material['type'],
                'title'         => $material['title'],
                'url'           => (string) ($material['url'] ?? ''),
                'folder'        => (string) ($material['folder'] ?? ''),
                'display_order' => (string) $material['display_order'],
                'is_published'  => $material['is_published'] ? '1' : '',
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $id = self::parseId($params['id'] ?? '');
        $material = self::findMaterial($id);
        if (!$material) {
            self::redirect404();
            return;
        }

        $data   = self::extractInput();
        $hasFileUpload = isset($_FILES['image']) && is_array($_FILES['image'])
            && (int) ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        $existingImage = (string) ($material['image_url'] ?? '');

        // Imagen efectiva tras esta edición. Por defecto conserva la existente.
        $imageUrl    = $existingImage;
        $newUpload   = '';
        $uploadError = null;
        if ($data['type'] === 'image' && $hasFileUpload) {
            try {
                $newUpload = Upload::image($_FILES['image'] ?? null);
                if ($newUpload !== '') {
                    $imageUrl = $newUpload;
                }
            } catch (RuntimeException $e) {
                $uploadError = $e->getMessage();
            }
        }

        $errors = self::validate($data, $imageUrl, $uploadError);

        if ($errors !== []) {
            if ($newUpload !== '') {
                Upload::deleteImage($newUpload);
            }
            View::render('admin/materials/form', [
                'mode'     => 'edit',
                'material' => $material,
                'errors'   => $errors,
                'old'      => $data,
            ]);
            return;
        }

        // Si quedó como imagen y se subió una nueva, borra la anterior.
        if ($data['type'] === 'image') {
            if ($newUpload !== '' && $existingImage !== '' && $existingImage !== $newUpload) {
                Upload::deleteImage($existingImage);
            }
            $url      = null;
            $imageCol = $imageUrl !== '' ? $imageUrl : null;
        } else {
            // Cambió de image a pdf/link: borra la imagen previa si existía.
            if ($existingImage !== '') {
                Upload::deleteImage($existingImage);
            }
            $url      = $data['url'];
            $imageCol = null;
        }

        $folder = $data['folder'] !== '' ? $data['folder'] : null;

        $stmt = Connection::get()->prepare(
            'UPDATE materials
                SET type = :ty, title = :ti, url = :u, image_url = :im,
                    folder = :fo, display_order = :o, is_published = :p
              WHERE id = :id'
        );
        $stmt->execute([
            ':ty' => $data['type'],
            ':ti' => $data['title'],
            ':u'  => $url,
            ':im' => $imageCol,
            ':fo' => $folder,
            ':o'  => (int) $data['display_order'],
            ':p'  => $data['is_published'] === '1' ? 't' : 'f',
            ':id' => $id,
        ]);

        self::setFlash('Material actualizado.');
        View::redirect('/admin/materiales');
    }

    /** @param array<string,string> $params */
    public function confirmDestroy(array $params): void
    {
        Auth::requireAdmin();

        $id = self::parseId($params['id'] ?? '');
        $material = self::findMaterial($id);
        if (!$material) {
            self::redirect404();
            return;
        }

        View::render('admin/materials/delete', [
            'material' => $material,
        ]);
    }

    /** @param array<string,string> $params */
    public function destroy(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $id = self::parseId($params['id'] ?? '');
        $material = self::findMaterial($id);
        if (!$material) {
            self::redirect404();
            return;
        }

        if ($material['type'] === 'image') {
            Upload::deleteImage(
                is_string($material['image_url']) ? $material['image_url'] : null
            );
        }

        $stmt = Connection::get()->prepare('DELETE FROM materials WHERE id = :id');
        $stmt->execute([':id' => $id]);

        self::setFlash('Material eliminado.');
        View::redirect('/admin/materiales');
    }

    // ----------------------------------------------------------------

    /** @return array{type:string,title:string,url:string,folder:string,display_order:string,is_published:string} */
    private static function extractInput(): array
    {
        return [
            'type'          => trim((string) ($_POST['type'] ?? '')),
            'title'         => trim((string) ($_POST['title'] ?? '')),
            'url'           => trim((string) ($_POST['url'] ?? '')),
            'folder'        => trim((string) ($_POST['folder'] ?? '')),
            'display_order' => trim((string) ($_POST['display_order'] ?? '0')),
            'is_published'  => isset($_POST['is_published']) ? '1' : '',
        ];
    }

    /**
     * @param array{type:string,title:string,url:string,display_order:string,is_published:string} $data
     * @param string      $imageUrl    Ruta efectiva de la imagen (subida o existente conservada).
     * @param string|null $uploadError Mensaje de error si la subida lanzó RuntimeException.
     * @return array<string,string>
     */
    private static function validate(array $data, string $imageUrl, ?string $uploadError): array
    {
        $errors = [];

        if (!isset(self::TYPES[$data['type']])) {
            $errors['type'] = 'Tipo de material no válido.';
        }

        if ($data['title'] === '' || mb_strlen($data['title']) > 200) {
            $errors['title'] = 'Título obligatorio (máx. 200 caracteres).';
        }

        if (preg_match('/^-?[0-9]{1,5}$/', $data['display_order']) !== 1) {
            $errors['display_order'] = 'El orden debe ser un número entero.';
        }

        // Validación específica por tipo (solo si el tipo es válido).
        if (!isset($errors['type'])) {
            switch ($data['type']) {
                case 'pdf':
                    if ($data['url'] === '' || Embed::sanitizeDownloadUrl($data['url']) === null) {
                        $errors['url'] = 'Para PDF usa un link de Google Drive válido.';
                    }
                    break;

                case 'link':
                    $scheme = is_string($data['url']) ? (parse_url($data['url'], PHP_URL_SCHEME) ?? '') : '';
                    if ($data['url'] === '' || $scheme !== 'https' || mb_strlen($data['url']) > 500) {
                        $errors['url'] = 'El enlace debe ser una URL https válida.';
                    }
                    break;

                case 'image':
                    if ($uploadError !== null) {
                        $errors['image'] = $uploadError;
                    } elseif ($imageUrl === '') {
                        $errors['image'] = 'Sube una imagen.';
                    }
                    break;
            }
        }

        return $errors;
    }

    private static function parseId(string $raw): int
    {
        return (preg_match('/^[1-9][0-9]{0,9}$/', $raw) === 1) ? (int) $raw : 0;
    }

    /** @return array<string,mixed>|null */
    private static function findMaterial(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = Connection::get()->prepare('SELECT * FROM materials WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static function redirect404(): void
    {
        http_response_code(404);
        require dirname(__DIR__, 2) . '/templates/errors/404.php';
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
