<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول البسيط</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 50px; }
        .login-box { background: white; padding: 30px; border-radius: 10px; max-width: 400px; margin: 0 auto; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; width: 100%; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>تسجيل الدخول البسيط</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="اسم المستخدم" required>
            <input type="password" name="password" placeholder="كلمة المرور" required>
            <button type="submit">تسجيل الدخول</button>
        </form>
        <?php
        if ($_POST) {
            echo "<p>تم استقبال البيانات: " . $_POST['username'] . "</p>";
        }
        ?>
    </div>
</body>
</html>