<?php

namespace app\model;

use support\Database;
use PDO;

class User
{
    /**
     * 根据ID查找用户
     */
    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * 根据用户名查找用户
     */
    public static function findByUsername(string $username): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * 根据邮箱查找用户
     */
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * 创建新用户
     */
    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (username, password, email, created_at, updated_at)
             VALUES (?, ?, ?, datetime("now"), datetime("now"))'
        );

        $stmt->execute([
            $data['username'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['email'] ?? null
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * 更新用户信息
     */
    public static function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            if (in_array($key, ['username', 'email'])) {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }

        if (isset($data['password'])) {
            $fields[] = "password = ?";
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $fields[] = "updated_at = datetime('now')";
        $values[] = $id;

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = Database::connection()->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * 删除用户
     */
    public static function delete(int $id): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM users WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * 验证密码
     */
    public static function verifyPassword(array $user, string $password): bool
    {
        return password_verify($password, $user['password']);
    }
}
