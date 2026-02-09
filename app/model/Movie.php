<?php

namespace app\model;

use support\Database;
use PDO;

class Movie
{
    /**
     * 根据ID查找电影
     */
    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM movies WHERE id = ?');
        $stmt->execute([$id]);
        $movie = $stmt->fetch();
        return $movie ?: null;
    }

    /**
     * 获取用户的所有电影
     */
    public static function findByUserId(int $userId, int $page = 1, int $perPage = 20, ?string $category = null): array
    {
        $offset = ($page - 1) * $perPage;

        if ($category) {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM movies WHERE user_id = ? AND category = ? ORDER BY created_at DESC LIMIT ? OFFSET ?'
            );
            $stmt->execute([$userId, $category, $perPage, $offset]);
        } else {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM movies WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?'
            );
            $stmt->execute([$userId, $perPage, $offset]);
        }

        return $stmt->fetchAll();
    }

    /**
     * 获取用户电影总数
     */
    public static function countByUserId(int $userId, ?string $category = null): int
    {
        if ($category) {
            $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM movies WHERE user_id = ? AND category = ?');
            $stmt->execute([$userId, $category]);
        } else {
            $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM movies WHERE user_id = ?');
            $stmt->execute([$userId]);
        }
        return (int) $stmt->fetchColumn();
    }

    /**
     * 搜索电影
     */
    public static function search(int $userId, string $keyword): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM movies
             WHERE user_id = ? AND (title LIKE ? OR description LIKE ?)
             ORDER BY created_at DESC'
        );

        $searchTerm = '%' . $keyword . '%';
        $stmt->execute([$userId, $searchTerm, $searchTerm]);

        return $stmt->fetchAll();
    }

    /**
     * 创建电影
     */
    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO movies (user_id, title, url, description, poster_url, category, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, datetime("now"), datetime("now"))'
        );

        $stmt->execute([
            $data['user_id'],
            $data['title'],
            $data['url'],
            $data['description'] ?? null,
            $data['poster_url'] ?? null,
            $data['category'] ?? '未分类'
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * 更新电影
     */
    public static function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            if (in_array($key, ['title', 'url', 'description', 'poster_url', 'category'])) {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }

        $fields[] = "updated_at = datetime('now')";
        $values[] = $id;

        $sql = 'UPDATE movies SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = Database::connection()->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * 删除电影
     */
    public static function delete(int $id, int $userId): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM movies WHERE id = ? AND user_id = ?');
        return $stmt->execute([$id, $userId]);
    }

    /**
     * 检查电影是否属于用户
     */
    public static function belongsToUser(int $id, int $userId): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM movies WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * 获取用户的所有分类及其电影数量
     */
    public static function getCategoriesByUserId(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT category, COUNT(*) as count FROM movies WHERE user_id = ? GROUP BY category ORDER BY category'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * 获取所有电影（不分用户）
     */
    public static function findAll(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $stmt = Database::connection()->prepare(
            'SELECT * FROM movies ORDER BY created_at DESC LIMIT ? OFFSET ?'
        );
        $stmt->execute([$perPage, $offset]);
        return $stmt->fetchAll();
    }

    /**
     * 获取所有电影总数
     */
    public static function countAll(): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM movies');
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}
