<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Database\Connection;
use App\View;

final class AdminPagesController
{
    private const SLUG = 'normas';

    /** @param array<string,string> $params */
    public function edit(array $params): void
    {
        Auth::requireAdmin();

        $page = self::findPage();
        if (!$page) {
            self::redirect404();
            return;
        }

        View::render('admin/pages/form', [
            'page'   => $page,
            'errors' => [],
            'flash'  => self::popFlash(),
            'old'    => [
                'title' => (string) $page['title'],
                'body'  => (string) ($page['body'] ?? ''),
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $page = self::findPage();
        if (!$page) {
            self::redirect404();
            return;
        }

        $data = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'body'  => trim((string) ($_POST['body']  ?? '')),
        ];

        $errors = [];
        if ($data['title'] === '' || mb_strlen($data['title']) > 160) {
            $errors['title'] = 'Título obligatorio (máx. 160 caracteres).';
        }
        if ($data['body'] !== '' && mb_strlen($data['body']) > 8000) {
            $errors['body'] = 'El contenido no puede exceder 8000 caracteres.';
        }

        if ($errors !== []) {
            View::render('admin/pages/form', [
                'page'   => $page,
                'errors' => $errors,
                'flash'  => null,
                'old'    => $data,
            ]);
            return;
        }

        $stmt = Connection::get()->prepare(
            'UPDATE pages SET title = :t, body = :b WHERE slug = :s'
        );
        $stmt->execute([
            ':t' => $data['title'],
            ':b' => $data['body'] !== '' ? $data['body'] : null,
            ':s' => self::SLUG,
        ]);

        self::setFlash('Normas y reglamentos actualizados.');
        View::redirect('/admin/normas');
    }

    // ----------------------------------------------------------------

    /** @return array<string,mixed>|null */
    private static function findPage(): ?array
    {
        $stmt = Connection::get()->prepare(
            'SELECT id, slug, title, body, updated_at FROM pages WHERE slug = :s LIMIT 1'
        );
        $stmt->execute([':s' => self::SLUG]);
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
