<?php
/**
 * ملف إعدادات الاتصال بقاعدة البيانات
 * Database Configuration File
 */

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'my_tech_store');
define('DB_CHARSET', 'utf8mb4');

// إعدادات النظام
define('SITE_URL', 'http://localhost/my-tech-store');
define('ADMIN_URL', SITE_URL . '/admin');
define('ASSETS_URL', SITE_URL . '/assets');
define('UPLOADS_URL', SITE_URL . '/uploads');

// مسارات المجلدات
define('BASE_PATH', dirname(__DIR__));
define('UPLOADS_PATH', BASE_PATH . '/uploads');

// إعدادات الأمان
define('HASH_ALGO', 'sha256');
define('SESSION_LIFETIME', 3600); // ساعة واحدة

// الاتصال بقاعدة البيانات
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // في حالة الخطأ
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

/**
 * دالة مساعدة للحصول على الاتصال بقاعدة البيانات
 */
function getDB() {
    global $pdo;
    return $pdo;
}

/**
 * دالة لتنفيذ استعلام
 */
function query($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        return false;
    }
}

/**
 * دالة للحصول على صف واحد
 */
function fetchOne($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt ? $stmt->fetch() : null;
}

/**
 * دالة للحصول على عدة صفوف
 */
function fetchAll($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

/**
 * دالة للحصول على آخر ID مُدرج
 */
function lastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}
