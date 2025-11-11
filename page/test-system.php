<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 اختبار نظام إدارة التدريب</h1>";

// اختبار الاتصال بقاعدة البيانات
try {
    $pdo = new PDO("mysql:host=localhost;dbname=internshipmanagemen", "root", "");
    echo "<p style='color: green;'>✅ الاتصال بقاعدة البيانات ناجح</p>";
    
    // اختبار المستخدمين
    $stmt = $pdo->query("SELECT user_id, username, user_type FROM users WHERE is_active = 1");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>المستخدمون النشطون:</h3>";
    foreach($users as $user) {
        echo "<p>👤 {$user['username']} - {$user['user_type']} (ID: {$user['user_id']})</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ خطأ في الاتصال: " . $e->getMessage() . "</p>";
}

// اختبار الجلسات
echo "<h3>اختبار الجلسات:</h3>";
session_start();
$_SESSION['test'] = 'نجح';
echo "<p>✅ الجلسات تعمل: " . $_SESSION['test'] . "</p>";

// روابط النظام
echo "<h3>روابط النظام:</h3>";
echo "<p><a href='index.php'>🏠 الصفحة الرئيسية</a></p>";
echo "<p><a href='login.php'>🔐 تسجيل الدخول</a></p>";
echo "<p><a href='register.php'>👤 إنشاء حساب</a></p>";
echo "<p><a href='dashboard.php'>📊 لوحة التحكم</a></p>";
?>