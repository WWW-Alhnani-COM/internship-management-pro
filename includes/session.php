<?php
// بدء الجلسة إذا لم تكن بدأت
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// التحقق من تسجيل الدخول
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// الحصول على بيانات المستخدم
function getUserData() {
    if(isLoggedIn()) {
        return [
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'user_type' => $_SESSION['user_type'] ?? null,
            'first_name' => $_SESSION['first_name'] ?? null,
            'last_name' => $_SESSION['last_name'] ?? null,
            'phone' => $_SESSION['phone'] ?? null
        ];
    }
    return null;
}

// تسجيل الخروج
function logout() {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}
?>