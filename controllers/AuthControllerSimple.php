<?php
session_start();

// مسارات مباشرة
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class AuthControllerSimple {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        if($this->db) {
            $this->user = new User($this->db);
        }
    }

    // معالجة تسجيل الدخول
    public function login($username, $password) {
        if(!$this->db) {
            return [
                'success' => false,
                'message' => 'خطأ في الاتصال بقاعدة البيانات'
            ];
        }

        $this->user->username = $username;
        
        $stmt = $this->user->login();
        
        if($stmt && $stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // التحقق من كلمة المرور
            if(password_verify($password, $row['password_hash'])) {
                // تخزين بيانات المستخدم في الجلسة
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['user_type'] = $row['user_type'];
                $_SESSION['first_name'] = $row['first_name'];
                $_SESSION['last_name'] = $row['last_name'];
                $_SESSION['logged_in'] = true;

                // تحديث آخر تسجيل دخول
                $this->user->user_id = $row['user_id'];
                $this->user->updateLastLogin();

                return [
                    'success' => true,
                    'message' => 'تم تسجيل الدخول بنجاح',
                    'user_type' => $row['user_type']
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة'
        ];
    }

    // التحقق من تسجيل الدخول
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
}
?>