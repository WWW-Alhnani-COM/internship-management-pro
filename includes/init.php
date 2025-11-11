<?php
/**
 * ملف تهيئة التطبيق
 * Application Initialization File
 */
// بدء الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'internshipmanagemen');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
// إعدادات التطبيق
define('APP_NAME', 'Internship Management System');
define('APP_VERSION', '1.0.0');

// الاتصال بقاعدة البيانات
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET NAMES utf8mb4");
} catch(PDOException $e) {
    die("Error: Could not connect to the database. " . $e->getMessage());
}
// تعيين المنطقة الزمنية
date_default_timezone_set('Asia/Riyadh');
// إعدادات التقرير عن الأخطاء
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
?>