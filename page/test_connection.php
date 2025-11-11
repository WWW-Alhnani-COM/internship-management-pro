<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if($db) {
    echo "✅ الاتصال بقاعدة البيانات ناجح!";
    
    // اختبار جلب المستخدمين
    $query = "SELECT COUNT(*) as user_count FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<br>عدد المستخدمين: " . $result['user_count'];
} else {
    echo "❌ فشل الاتصال بقاعدة البيانات";
}
?>