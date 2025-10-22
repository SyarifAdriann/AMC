<?php

namespace App\Repositories;

use App\Models\User;
use PDO;

class UserRepository extends Repository
{
    public function findForAuthentication(string $identifier): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, password_hash, role, status FROM users WHERE username = ? OR email = ?'
        );
        $stmt->execute([$identifier, $identifier]);

        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        return $record ? User::fromArray($record) : null;
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        return $record ? User::fromArray($record) : null;
    }

    public function findByUsername(string $username): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        return $record ? User::fromArray($record) : null;
    }

    public function ensureSystemUser(): User
    {
        $system = $this->findByUsername('system');

        if ($system) {
            return $system;
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO users (username, password_hash, role, status, full_name) VALUES ('system', '', 'admin', 'active', 'System User')"
        );
        $stmt->execute();

        $id = (int) $this->pdo->lastInsertId();

        return $this->findById($id) ?? User::fromArray([
            'id' => $id,
            'username' => 'system',
            'password_hash' => '',
            'role' => 'admin',
            'status' => 'active',
            'full_name' => 'System User',
        ]);
    }

    public function search(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = $this->buildFilters($filters);

        $sql = 'SELECT id, username, email, full_name, role, status, last_login_at, created_at '
            . 'FROM users ' . ($where ? 'WHERE ' . implode(' AND ', $where) . ' ' : '')
            . 'ORDER BY created_at DESC '
            . 'LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countByFilters(array $filters): int
    {
        [$where, $params] = $this->buildFilters($filters);

        $sql = 'SELECT COUNT(*) FROM users ' . ($where ? 'WHERE ' . implode(' AND ', $where) : '');
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE username = :username';
        $params = [':username' => $username];

        if ($excludeId !== null) {
            $sql .= ' AND id != :excludeId';
            $params[':excludeId'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE email = :email';
        $params = [':email' => $email];

        if ($excludeId !== null) {
            $sql .= ' AND id != :excludeId';
            $params[':excludeId'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function create(array $attributes): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, email, full_name, role, status, password_hash, must_change_password) '
            . 'VALUES (:username, :email, :full_name, :role, :status, :password_hash, :must_change_password)'
        );

        $stmt->execute([
            ':username' => $attributes['username'],
            ':email' => $attributes['email'],
            ':full_name' => $attributes['full_name'],
            ':role' => $attributes['role'],
            ':status' => $attributes['status'],
            ':password_hash' => $attributes['password_hash'],
            ':must_change_password' => (int) ($attributes['must_change_password'] ?? 0),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $attributes): void
    {
        if (empty($attributes)) {
            return;
        }

        $sets = [];
        $params = [];

        foreach ($attributes as $column => $value) {
            $sets[] = $column . ' = :' . $column;
            $params[':' . $column] = $value;
        }

        $params[':id'] = $id;

        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function updatePassword(int $id, string $hash, bool $mustChange): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET password_hash = :hash, must_change_password = :must_change WHERE id = :id'
        );
        $stmt->execute([
            ':hash' => $hash,
            ':must_change' => $mustChange ? 1 : 0,
            ':id' => $id,
        ]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET status = :status WHERE id = :id');
        $stmt->execute([
            ':status' => $status,
            ':id' => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function countActiveAdminsExcluding(?int $excludeId = null): int
    {
        $sql = "SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'";
        $params = [];
        if ($excludeId !== null) {
            $sql .= ' AND id != :excludeId';
            $params[':excludeId'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function fetchRawById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);

        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        return $record ?: null;
    }

    private function buildFilters(array $filters): array
    {
        $where = [];
        $params = [];

        $query = trim((string) ($filters['query'] ?? ''));
        if ($query !== '') {
            $where[] = '(username LIKE :query OR email LIKE :query OR full_name LIKE :query)';
            $params[':query'] = '%' . $query . '%';
        }

        $role = trim((string) ($filters['role'] ?? ''));
        if ($role !== '') {
            $where[] = 'role = :role';
            $params[':role'] = $role;
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }

        return [$where, $params];
    }

}
