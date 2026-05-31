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
 * Perfil del miembro: ver y editar su propio nombre y foto.
 * Cada miembro solo puede editar su propia cuenta (se usa el id de sesión).
 */
final class ProfileController
{
    /** @param array<string,string> $params */
    public function show(array $params): void
    {
        Auth::requireLogin();

        $user = self::fresh();

        View::render('profile/show', [
            'profile' => $user,
            'errors'  => [],
            'flash'   => self::popFlash(),
        ]);
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Auth::requireLogin();
        Csrf::requireValid();

        $user = self::fresh();
        $uid  = (int) $user['id'];

        $name   = trim((string) ($_POST['name'] ?? ''));
        $errors = [];

        if ($name === '' || mb_strlen($name) > 120) {
            $errors['name'] = 'El nombre es obligatorio (máx. 120 caracteres).';
        }

        $newPhoto = '';
        if ($errors === []) {
            try {
                $newPhoto = Upload::image($_FILES['photo'] ?? null);
            } catch (RuntimeException $e) {
                $errors['photo'] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            View::render('profile/show', [
                'profile' => array_merge($user, ['name' => $name]),
                'errors'  => $errors,
                'flash'   => null,
            ]);
            return;
        }

        if ($newPhoto !== '') {
            Upload::deleteImage(is_string($user['photo_url'] ?? null) ? $user['photo_url'] : null);
            $stmt = Connection::get()->prepare(
                'UPDATE users SET name = :n, photo_url = :p WHERE id = :id'
            );
            $stmt->execute([':n' => $name, ':p' => $newPhoto, ':id' => $uid]);
        } else {
            $stmt = Connection::get()->prepare('UPDATE users SET name = :n WHERE id = :id');
            $stmt->execute([':n' => $name, ':id' => $uid]);
        }

        self::setFlash('Perfil actualizado.');
        View::redirect('/perfil');
    }

    // ----------------------------------------------------------------

    /** @return array<string,mixed> Datos frescos del usuario (incluye photo_url). */
    private static function fresh(): array
    {
        $user = Auth::user();
        $stmt = Connection::get()->prepare(
            'SELECT id, name, email, role, photo_url FROM users WHERE id = :id'
        );
        $stmt->execute([':id' => (int) $user['id']]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : $user;
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
