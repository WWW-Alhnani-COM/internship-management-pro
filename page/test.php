<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>✅ PHP يعمل بشكل صحيح!</h1>";
echo "<p>إذا ترى هذه الرسالة، فـ PHP يعمل بشكل صحيح.</p>";
echo "<p>الإصدار: " . phpversion() . "</p>";

// اختبار الاتصال بقاعدة البيانات
try {
    $pdo = new PDO("mysql:host=localhost;dbname=internshipmanagemen", "root", "");
    echo "<p style='color: green;'>✅ الاتصال بقاعدة البيانات ناجح</p>";
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage() . "</p>";
}
?>