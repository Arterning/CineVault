<?php

namespace support;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    /**
     * 获取数据库连接实例
     */
    public static function connection(): PDO
    {
        if (self::$instance === null) {
            try {
                $dbPath = runtime_path() . '/database.sqlite';
                $dbDir = dirname($dbPath);

                // 确保目录存在
                if (!is_dir($dbDir)) {
                    mkdir($dbDir, 0755, true);
                }

                self::$instance = new PDO('sqlite:' . $dbPath);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                // 初始化数据库表
                self::initDatabase();
            } catch (PDOException $e) {
                throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    /**
     * 初始化数据库表
     */
    private static function initDatabase(): void
    {
        $sql = file_get_contents(base_path() . '/database/init.sql');
        self::$instance->exec($sql);
    }

    /**
     * 开始事务
     */
    public static function beginTransaction(): bool
    {
        return self::connection()->beginTransaction();
    }

    /**
     * 提交事务
     */
    public static function commit(): bool
    {
        return self::connection()->commit();
    }

    /**
     * 回滚事务
     */
    public static function rollBack(): bool
    {
        return self::connection()->rollBack();
    }
}
