<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 اختبار هيكل الملفات</h1>";

$files_to_check = [
    'config/database.php',
    'models/User.php',
    'controllers/AuthController.php',
    'login.php',
    'register.php',
    'dashboard.php'
];

foreach($files_to_check as $file) {
    if(file_exists($file)) {
        echo "<p style='color: green;'>✅ $file - موجود</p>";
    } else {
        echo "<p style='color: red;'>❌ $file - غير موجود</p>";
    }
}

// اختبار الاتصال بقاعدة البيانات
echo "<h2>اختبار الاتصال بقاعدة البيانات</h2>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=internshipmanagemen", "root", "");
    echo "<p style='color: green;'>✅ الاتصال بقاعدة البيانات ناجح</p>";
    
    // اختبار وجود المستخدمين
    $stmt = $pdo->query("SELECT username, user_type FROM users WHERE is_active = 1");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>عدد المستخدمين النشطين: " . count($users) . "</p>";
    foreach($users as $user) {
        echo "<p>👤 {$user['username']} - {$user['user_type']}</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ خطأ في الاتصال: " . $e->getMessage() . "</p>";
}
?>