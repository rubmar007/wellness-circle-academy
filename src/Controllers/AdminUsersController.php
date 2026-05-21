<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Database\Connection;
use App\View;
use PDO;

final class AdminUsersController
{
    /** @param array<string,string> $params */
    public function index(array $params): void
    {
        Auth::requireAdmin();

        $users = Connection::get()->query(
            'SELECT id, name, email, role, is_active, created_at
               FROM users
              ORDER BY created_at DESC, id DESC'
        )->fetchAll();

        View::render('admin/users/index', [
            'users' => $users,
            'flash' => self::popFlash(),
        ]);
    }

    /** @param array<string,string> $params */
    public function create(array $params): void
    {
        Auth::requireAdmin();

        View::render('admin/users/form', [
            'mode'   => 'create',
            'user'   => null,
            'errors' => [],
            'old'    => ['name' => '', 'email' => '', 'role' => 'member'],
        ]);
    }

    /** @param array<string,string> $params */
    public function store(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $data = self::extractInput();
        $errors = self::validate($data, isCreate: true, currentId: null);

        if ($errors !== []) {
            View::render('admin/users/form', [
                'mode'   => 'create',
                'user'   => null,
                'errors' => $errors,
                'old'    => $data,
            ]);
            return;
        }

        Auth::createUser($data['name'], $data['email'], $data['password'], $data['role']);
        self::setFlash('Usuario creado correctamente.');
        View::redirect('/admin/usuarios');
    }

    /** @param array<string,string> $params */
    public function edit(array $params): void
    {
        Auth::requireAdmin();

        $id = self::parseId($params['id'] ?? '');
        $user = self::findUser($id);
        if (!$user) {
            self::redirect404();
            return;
        }

        View::render('admin/users/form', [
            'mode'   => 'edit',
            'user'   => $user,
            'errors' => [],
            'old'    => [
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ],
        ]);
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $id = self::parseId($params['id'] ?? '');
        $user = self::findUser($id);
        if (!$user) {
            self::redirect404();
            return;
        }

        $data = self::extractInput();
        $errors = self::validate($data, isCreate: false, currentId: $id);

        // No permitir que un admin se quite el rol a sí mismo si es el último admin.
        $self = Auth::user();
        if ($self !== null && (int) $self['id'] === $id && $data['role'] !== 'admin') {
            if (self::countAdmins() <= 1) {
                $errors['role'] = 'Eres el último administrador; no puedes degradarte.';
            }
        }

        if ($errors !== []) {
            View::render('admin/users/form', [
                'mode'   => 'edit',
                'user'   => $user,
                'errors' => $errors,
                'old'    => $data,
            ]);
            return;
        }

        $pdo = Connection::get();

        if ($data['password'] !== '') {
            $stmt = $pdo->prepare(
                'UPDATE users SET name = :n, email = :e, role = :r, password_hash = :h WHERE id = :id'
            );
            $stmt->execute([
                ':n'  => $data['name'],
                ':e'  => $data['email'],
                ':r'  => $data['role'],
                ':h'  => Auth::hashPassword($data['password']),
                ':id' => $id,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE users SET name = :n, email = :e, role = :r WHERE id = :id'
            );
            $stmt->execute([
                ':n'  => $data['name'],
                ':e'  => $data['email'],
                ':r'  => $data['role'],
                ':id' => $id,
            ]);
        }

        self::setFlash('Usuario actualizado.');
        View::redirect('/admin/usuarios');
    }

    /** @param array<string,string> $params */
    public function toggleActive(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $id = self::parseId($params['id'] ?? '');
        $user = self::findUser($id);
        if (!$user) {
            self::redirect404();
            return;
        }

        $self = Auth::user();
        if ($self !== null && (int) $self['id'] === $id) {
            self::setFlash('No puedes desactivar tu propia cuenta.', 'error');
            View::redirect('/admin/usuarios');
            return;
        }

        $newState = $user['is_active'] ? false : true;
        $stmt = Connection::get()->prepare('UPDATE users SET is_active = :a WHERE id = :id');
        $stmt->bindValue(':a', $newState, PDO::PARAM_BOOL);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        self::setFlash($newState ? 'Usuario activado.' : 'Usuario desactivado.');
        View::redirect('/admin/usuarios');
    }

    // ---------------------------------------------------------------

    /** @return array{name:string,email:string,role:string,password:string} */
    private static function extractInput(): array
    {
        $name     = trim((string) ($_POST['name']  ?? ''));
        $email    = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $role     = (string) ($_POST['role'] ?? 'member');
        $password = (string) ($_POST['password'] ?? '');

        if (!in_array($role, ['admin', 'member'], true)) {
            $role = 'member';
        }
        return compact('name', 'email', 'role', 'password');
    }

    /**
     * @param array{name:string,email:string,role:string,password:string} $data
     * @return array<string,string>
     */
    private static function validate(array $data, bool $isCreate, ?int $currentId): array
    {
        $errors = [];

        if ($data['name'] === '' || mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 120) {
            $errors['name'] = 'El nombre debe tener entre 2 y 120 caracteres.';
        }
        if ($data['email'] === '' || filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false || mb_strlen($data['email']) > 190) {
            $errors['email'] = 'Email inválido.';
        } else {
            if (self::emailExistsExcept($data['email'], $currentId)) {
                $errors['email'] = 'Ese email ya está registrado por otro usuario.';
            }
        }
        if ($isCreate || $data['password'] !== '') {
            if (mb_strlen($data['password']) < 10) {
                $errors['password'] = 'La contraseña debe tener al menos 10 caracteres.';
            }
        }

        return $errors;
    }

    private static function emailExistsExcept(string $email, ?int $excludeId): bool
    {
        $sql  = 'SELECT 1 FROM users WHERE email = :e';
        $args = [':e' => $email];
        if ($excludeId !== null) {
            $sql .= ' AND id <> :id';
            $args[':id'] = $excludeId;
        }
        $stmt = Connection::get()->prepare($sql . ' LIMIT 1');
        $stmt->execute($args);
        return (bool) $stmt->fetchColumn();
    }

    private static function countAdmins(): int
    {
        return (int) Connection::get()->query(
            "SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = TRUE"
        )->fetchColumn();
    }

    private static function parseId(string $raw): int
    {
        return (preg_match('/^[1-9][0-9]{0,9}$/', $raw) === 1) ? (int) $raw : 0;
    }

    /** @return array<string,mixed>|null */
    private static function findUser(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = Connection::get()->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = :id');
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
