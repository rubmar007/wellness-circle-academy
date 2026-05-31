<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Database\Connection;
use App\Upload;
use App\View;
use RuntimeException;

/**
 * Feed de Inicio: muro global donde los miembros publican texto + foto opcional.
 * Visibilidad global (todos los miembros se ven entre sí). Moderación: un admin
 * puede eliminar cualquier publicación; un miembro puede eliminar la suya.
 *
 * Seguridad: CSRF en cada POST, imágenes validadas por Upload::image (MIME real,
 * whitelist, renombrado UUID), texto escapado en la vista, rate-limit por usuario.
 */
final class FeedController
{
    private const MAX_BODY = 4000;
    private const RATE_MAX_PER_HOUR = 20;

    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireLogin();

        $stmt = Connection::get()->query(
            'SELECT p.id, p.user_id, p.body, p.image_url, p.created_at,
                    u.name AS author_name, u.photo_url AS author_photo
               FROM posts p
               JOIN users u ON u.id = p.user_id
              WHERE p.is_hidden = FALSE
              ORDER BY p.created_at DESC
              LIMIT 100'
        );
        $posts = $stmt !== false ? $stmt->fetchAll() : [];

        View::render('feed/index', [
            'posts'    => $posts,
            'is_admin' => Auth::isAdmin(),
            'flash'    => self::popFlash(),
        ]);
    }

    /** @param array<string,string> $params */
    public function store(array $params): void
    {
        Auth::requireLogin();
        Csrf::requireValid();

        $user = Auth::user();
        $uid  = (int) $user['id'];
        $body = trim((string) ($_POST['body'] ?? ''));

        $error = null;
        if ($body === '') {
            $error = 'Escribe algo para publicar.';
        } elseif (mb_strlen($body) > self::MAX_BODY) {
            $error = 'La publicación es demasiado larga (máx. ' . self::MAX_BODY . ' caracteres).';
        } elseif (self::isRateLimited($uid)) {
            $error = 'Has publicado demasiado seguido. Espera un poco antes de volver a publicar.';
        }

        $imageUrl = '';
        if ($error === null) {
            try {
                $imageUrl = Upload::image($_FILES['image'] ?? null);
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        if ($error !== null) {
            self::setFlash($error, 'error');
            View::redirect('/inicio');
            return;
        }

        $stmt = Connection::get()->prepare(
            'INSERT INTO posts (user_id, body, image_url) VALUES (:u, :b, :img)'
        );
        $stmt->execute([
            ':u'   => $uid,
            ':b'   => $body,
            ':img' => $imageUrl !== '' ? $imageUrl : null,
        ]);

        self::setFlash('Publicado.');
        View::redirect('/inicio');
    }

    /** @param array<string,string> $params */
    public function destroy(array $params): void
    {
        Auth::requireLogin();
        Csrf::requireValid();

        $id = (preg_match('/^[1-9][0-9]{0,18}$/', $params['id'] ?? '') === 1) ? (int) $params['id'] : 0;
        if ($id <= 0) {
            View::redirect('/inicio');
            return;
        }

        $stmt = Connection::get()->prepare('SELECT id, user_id, image_url FROM posts WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $post = $stmt->fetch();

        if (!$post) {
            View::redirect('/inicio');
            return;
        }

        $user = Auth::user();
        $isOwner = (int) $post['user_id'] === (int) $user['id'];

        // Solo el autor o un admin pueden eliminar.
        if (!$isOwner && !Auth::isAdmin()) {
            http_response_code(403);
            require dirname(__DIR__, 2) . '/templates/errors/403.php';
            return;
        }

        Upload::deleteImage(is_string($post['image_url']) ? $post['image_url'] : null);

        $del = Connection::get()->prepare('DELETE FROM posts WHERE id = :id');
        $del->execute([':id' => $id]);

        self::setFlash('Publicación eliminada.');
        View::redirect('/inicio');
    }

    // ----------------------------------------------------------------

    private static function isRateLimited(int $userId): bool
    {
        $stmt = Connection::get()->prepare(
            "SELECT COUNT(*) FROM posts
              WHERE user_id = :u AND created_at > NOW() - interval '1 hour'"
        );
        $stmt->execute([':u' => $userId]);
        return (int) $stmt->fetchColumn() >= self::RATE_MAX_PER_HOUR;
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
