# 启用 SQLite 扩展指南

## 问题
系统检测到 PDO SQLite 驱动未安装。

## 解决方法

### 方法一：启用 SQLite 扩展（推荐）

1. **打开 php.ini 文件**
   ```
   文件位置: C:\EServer\core\software\php\php-8.2\php.ini
   ```

2. **查找并修改以下行**

   找到这些行（可能被注释掉）:
   ```ini
   ;extension=pdo_sqlite
   ;extension=sqlite3
   ```

   去掉前面的分号，修改为:
   ```ini
   extension=pdo_sqlite
   extension=sqlite3
   ```

3. **保存文件并重启服务器**
   ```bash
   php windows.php stop
   php windows.php start
   ```

### 方法二：使用 MySQL 数据库

如果不想启用 SQLite，可以使用 MySQL（你的系统已安装 MySQL PDO 驱动）。

需要修改以下文件：

#### 1. 修改 `support/Database.php`

将 SQLite 连接改为 MySQL 连接：

```php
// 原来的代码
self::$instance = new PDO('sqlite:' . $dbPath);

// 改为
self::$instance = new PDO(
    'mysql:host=localhost;dbname=movie_system;charset=utf8mb4',
    'root',        // MySQL 用户名
    'password'     // MySQL 密码
);
```

#### 2. 修改 `database/init.sql`

将 SQLite 语法改为 MySQL 语法：

```sql
-- 创建用户表
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建电影表
CREATE TABLE IF NOT EXISTS movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    url TEXT NOT NULL,
    description TEXT,
    poster_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 3. 创建数据库

在 MySQL 中执行：
```sql
CREATE DATABASE movie_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 推荐方案

**方法一（启用 SQLite）更简单**，只需修改配置文件即可。

如果你选择方法一，我可以帮你自动修改 php.ini 文件（需要管理员权限）。
