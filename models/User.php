<?php
class User {
    private $conn;
    private $table_name = "users";

    public $user_id;
    public $username;
    public $email;
    public $password;
    public $user_type;
    public $first_name;
    public $last_name;
    public $phone;

    public function __construct($db) {
        $this->conn = $db;
    }

    // تسجيل الدخول
    public function login() {
        $query = "SELECT user_id, username, email, password_hash, user_type, first_name, last_name 
                  FROM " . $this->table_name . " 
                  WHERE (username = :username OR email = :username) AND is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username);
        
        if($stmt->execute()) {
            return $stmt;
        }
        return false;
    }

    // تحديث آخر تسجيل دخول
    public function updateLastLogin() {
        $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        return $stmt->execute();
    }

    // دالة تسجيل حساب جديد مع دعم لإنشاء السجلات في الجداول الفرعية
    public function register() {
        try {
            // 1. التحقق من تكرار اسم المستخدم أو البريد
            $check = $this->conn->prepare("
                SELECT user_id FROM users 
                WHERE username = :username OR email = :email
            ");
            $check->bindParam(':username', $this->username);
            $check->bindParam(':email', $this->email);
            $check->execute();

            if ($check->rowCount() > 0) {
                return 'user_exists';
            }

            // 2. تشفير كلمة المرور
            $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);

            // 3. إدراج في جدول users
            $query = "
                INSERT INTO users (username, email, password_hash, user_type, first_name, last_name, phone)
                VALUES (:username, :email, :password_hash, :user_type, :first_name, :last_name, :phone)
            ";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $this->username);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':password_hash', $hashedPassword);
            $stmt->bindParam(':user_type', $this->user_type);
            $stmt->bindParam(':first_name', $this->first_name);
            $stmt->bindParam(':last_name', $this->last_name);
            $stmt->bindValue(':phone', $this->phone ?? null);

            if (!$stmt->execute()) {
                return 'db_error';
            }

            $user_id = $this->conn->lastInsertId();

            // 4. إنشاء سجل فرعي في الجدول المناسب
            $extraSuccess = true;
            
            if ($this->user_type === 'student') {
                $extraStmt = $this->conn->prepare("INSERT INTO students (student_id) VALUES (:id)");
            } elseif ($this->user_type === 'teacher') {
                $extraStmt = $this->conn->prepare("INSERT INTO teachers (teacher_id) VALUES (:id)");
            } elseif ($this->user_type === 'supervisor') {
                $extraStmt = $this->conn->prepare("INSERT INTO site_supervisors (supervisor_id) VALUES (:id)");
            }
            
            if (isset($extraStmt)) {
                $extraStmt->bindParam(':id', $user_id);
                $extraSuccess = $extraStmt->execute();
                
                if (!$extraSuccess) {
                    // في حال فشل الجدول الفرعي، نحذف المستخدم من users
                    $this->conn->prepare("DELETE FROM users WHERE user_id = :id")->execute(['id' => $user_id]);
                    return 'db_error';
                }
            }

            return 'success';

        } catch (PDOException $e) {
            error_log("Register error: " . $e->getMessage());
            return 'db_error';
        }
    }
    
    // التحقق من وجود سجل في الجدول الفرعي وإنشاؤه إذا لزم الأمر
    public function ensureStudentRecord($student_id) {
        try {
            $check = $this->conn->prepare("SELECT student_id FROM students WHERE student_id = :student_id");
            $check->bindParam(':student_id', $student_id);
            $check->execute();
            
            if ($check->rowCount() == 0) {
                $insert = $this->conn->prepare("INSERT INTO students (student_id) VALUES (:student_id)");
                $insert->bindParam(':student_id', $student_id);
                return $insert->execute();
            }
            return true;
        } catch (PDOException $e) {
            error_log("Error ensuring student record: " . $e->getMessage());
            return false;
        }
    }
    
    // التحقق من وجود سجل للمشرف الميداني
    public function ensureSupervisorRecord($supervisor_id) {
        try {
            $check = $this->conn->prepare("SELECT supervisor_id FROM site_supervisors WHERE supervisor_id = :supervisor_id");
            $check->bindParam(':supervisor_id', $supervisor_id);
            $check->execute();
            
            if ($check->rowCount() == 0) {
                $insert = $this->conn->prepare("INSERT INTO site_supervisors (supervisor_id) VALUES (:supervisor_id)");
                $insert->bindParam(':supervisor_id', $supervisor_id);
                return $insert->execute();
            }
            return true;
        } catch (PDOException $e) {
            error_log("Error ensuring supervisor record: " . $e->getMessage());
            return false;
        }
    }
}
?>