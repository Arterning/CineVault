<?php

echo "=== 系统环境检查 ===\n\n";

// 检查 PHP 版本
echo "PHP 版本: " . PHP_VERSION;
if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
    echo " ✓\n";
} else {
    echo " ✗ (需要 >= 8.1)\n";
}

// 检查 PDO
echo "PDO 扩展: ";
if (extension_loaded('pdo')) {
    echo "已安装 ✓\n";
} else {
    echo "未安装 ✗\n";
}

// 检查 PDO SQLite
echo "PDO SQLite 驱动: ";
if (extension_loaded('pdo_sqlite')) {
    echo "已安装 ✓\n";
} else {
    echo "未安装 ✗\n";
    echo "\n解决方法:\n";
    echo "1. 找到你的 php.ini 文件\n";
    echo "2. 搜索 'extension=pdo_sqlite' 或 ';extension=pdo_sqlite'\n";
    echo "3. 去掉前面的分号 ';' 启用该扩展\n";
    echo "4. 重启 PHP 服务\n";
    echo "\nphp.ini 位置: " . php_ini_loaded_file() . "\n";
}

// 检查可用的 PDO 驱动
echo "\n可用的 PDO 驱动: ";
if (extension_loaded('pdo')) {
    $drivers = PDO::getAvailableDrivers();
    if (!empty($drivers)) {
        echo implode(', ', $drivers) . "\n";
    } else {
        echo "无 ✗\n";
    }
} else {
    echo "PDO 未安装\n";
}

// 检查 SQLite3 扩展（备选方案）
echo "SQLite3 扩展: ";
if (extension_loaded('sqlite3')) {
    echo "已安装 ✓\n";
} else {
    echo "未安装 ✗\n";
}

// 检查运行时目录权限
echo "\n运行时目录: ";
$runtimeDir = __DIR__ . '/runtime';
if (!is_dir($runtimeDir)) {
    mkdir($runtimeDir, 0755, true);
}
if (is_writable($runtimeDir)) {
    echo "可写 ✓\n";
} else {
    echo "不可写 ✗\n";
}

echo "\n=== 检查完成 ===\n";
