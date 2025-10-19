# 电影收藏系统 - CineVault

一个基于 Webman 框架的现代化电影收藏管理系统，具有 Dark 科技风格的界面设计。

## ✨ 功能特性

- 👤 **用户系统**
  - 用户注册/登录
  - 安全的密码加密存储
  - Session 会话管理

- 🎬 **电影管理**
  - 添加电影（手动或自动）
  - 智能URL解析（自动提取电影标题、描述、海报）
  - 编辑电影信息
  - 删除电影
  - 搜索电影

- 🎨 **界面设计**
  - Tailwind CSS 实现的 Dark 科技风格
  - 渐变色和发光效果
  - 响应式设计，支持移动端
  - 流畅的动画效果

## 📋 系统要求

- PHP >= 8.1
- SQLite3 扩展
- Composer

## 🚀 快速开始

### 1. 安装依赖

```bash
composer install
```

### 2. 启动服务器

**Windows:**
```bash
php windows.php start
```

**Linux/Mac:**
```bash
php start.php start
```

### 3. 访问系统

打开浏览器访问: `http://127.0.0.1:8787`

## 📁 项目结构

```
webman/
├── app/
│   ├── controller/          # 控制器
│   │   ├── AuthController.php      # 认证控制器
│   │   └── MovieController.php     # 电影控制器
│   ├── middleware/          # 中间件
│   │   └── AuthMiddleware.php      # 认证中间件
│   ├── model/               # 模型
│   │   ├── User.php                # 用户模型
│   │   └── Movie.php               # 电影模型
│   └── view/                # 视图
│       ├── auth/                   # 认证页面
│       │   ├── login.html
│       │   └── register.html
│       ├── movies/                 # 电影页面
│       │   ├── index.html
│       │   ├── create.html
│       │   └── edit.html
│       └── errors/                 # 错误页面
│           └── 404.html
├── config/
│   └── route.php            # 路由配置
├── database/
│   └── init.sql             # 数据库初始化脚本
├── support/
│   ├── Database.php         # 数据库连接类
│   └── MovieParser.php      # URL解析器
└── runtime/
    └── database.sqlite      # SQLite 数据库文件（自动创建）
```

## 🎯 使用说明

### 注册账号

1. 访问 `http://127.0.0.1:8787/register`
2. 填写用户名（3-50个字符）和密码（至少6个字符）
3. 邮箱为可选项
4. 注册成功后自动登录

### 添加电影

**方式一：自动解析（推荐）**

1. 点击"添加电影"按钮
2. 在"自动获取电影信息"区域输入电影页面的URL
3. 点击"解析"按钮
4. 系统会自动提取电影名称、描述和海报
5. 检查信息无误后点击"保存"

**方式二：手动填写**

1. 点击"添加电影"按钮
2. 手动填写电影URL、名称、描述和海报URL
3. 点击"保存"

### 管理电影

- **查看**: 在列表页面浏览所有收藏的电影
- **搜索**: 使用搜索框按标题或描述搜索电影
- **播放**: 点击"播放"按钮跳转到电影URL
- **编辑**: 点击"编辑"按钮修改电影信息
- **删除**: 点击"删除"按钮移除电影（需确认）

## 🔧 技术栈

- **后端框架**: Webman (高性能 PHP 框架)
- **数据库**: SQLite
- **前端**: HTML5 + Tailwind CSS + JavaScript
- **认证**: Session-based 认证
- **密码加密**: PHP password_hash

## 🎨 界面设计特点

- **Dark 科技风格**: 深色背景配合霓虹蓝紫渐变
- **发光效果**: 边框和按钮的发光动画
- **流畅动画**: hover 和 transition 效果
- **响应式设计**: 适配各种屏幕尺寸

## 🔐 安全特性

- 密码加密存储（bcrypt）
- SQL 注入防护（PDO 预处理语句）
- XSS 防护（htmlspecialchars）
- Session 会话管理
- 路由中间件权限控制

## 📝 数据库结构

### users 表

| 字段 | 类型 | 说明 |
|------|------|------|
| id | INTEGER | 主键 |
| username | VARCHAR(50) | 用户名（唯一） |
| password | VARCHAR(255) | 密码（加密） |
| email | VARCHAR(100) | 邮箱（可选） |
| created_at | DATETIME | 创建时间 |
| updated_at | DATETIME | 更新时间 |

### movies 表

| 字段 | 类型 | 说明 |
|------|------|------|
| id | INTEGER | 主键 |
| user_id | INTEGER | 用户ID（外键） |
| title | VARCHAR(255) | 电影名称 |
| url | TEXT | 电影URL |
| description | TEXT | 电影描述 |
| poster_url | TEXT | 海报URL |
| created_at | DATETIME | 创建时间 |
| updated_at | DATETIME | 更新时间 |

## 🛠️ 开发调试

查看日志：
```bash
tail -f runtime/logs/workerman.log
```

停止服务器：
```bash
php windows.php stop    # Windows
php start.php stop      # Linux/Mac
```

重启服务器：
```bash
php windows.php restart    # Windows
php start.php restart      # Linux/Mac
```

## 📄 许可证

MIT License
