<?php
session_start();

// استخدام المسار المطلق الصحيح
$root_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR;

require_once $root_dir . 'includes/init.php';
require_once $root_dir . 'models/User.php';

class AuthController {
    private $db;
    private $user;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
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

    // معالجة إنشاء الحساب
    public function register($data) {
        if(!$this->db) {
            return [
                'success' => false,
                'message' => 'خطأ في الاتصال بقاعدة البيانات'
            ];
        }

        $this->user->username = $data['username'];
        $this->user->email = $data['email'];
        $this->user->password = $data['password'];
        $this->user->user_type = $data['user_type'];
        $this->user->first_name = $data['first_name'];
        $this->user->last_name = $data['last_name'];
        $this->user->phone = $data['phone'] ?? null;

        $result = $this->user->register();

        switch($result) {
            case 'success':
                return [
                    'success' => true,
                    'message' => 'تم إنشاء الحساب بنجاح'
                ];
            case 'user_exists':
                return [
                    'success' => false,
                    'message' => 'اسم المستخدم أو البريد الإلكتروني موجود مسبقاً'
                ];
            default:
                return [
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إنشاء الحساب'
                ];
        }
    }

    // التحقق من تسجيل الدخول
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    // تسجيل الخروج
    public function logout() {
        session_unset();
        session_destroy();
        return [
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح'
        ];
    }
}

// معالجة طلبات AJAX
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $auth = new AuthController();
    $response = [];

    switch($_POST['action']) {
        case 'login':
            $response = $auth->login($_POST['username'], $_POST['password']);
            break;
        case 'register':
            $response = $auth->register($_POST);
            break;
        case 'logout':
            $response = $auth->logout();
            break;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>