<?php
// db_connect.php — اتصال قاعدة البيانات باستخدام PDO
require_once __DIR__ . '/../config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // في بيئة الإنتاج، لا تعرض رسالة الخطأ للمستخدم
    error_log("Database connection failed: " . $e->getMessage());
    die("خطأ في الاتصال بقاعدة البيانات. يرجى المحاولة لاحقاً.");
}

