<?php
/**
 * Authentication and session helpers (English)
 */

// prevent double include
if (defined('AUTH_INCLUDED')) {
    return;
}
define('AUTH_INCLUDED', true);

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Ensure user has one of the allowed types, otherwise redirect to login or dashboard
 */
function checkUserType($allowedTypes) {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: ../login.php');
        exit();
    }
    
    if (!in_array($_SESSION['user_type'], $allowedTypes)) {
        $_SESSION['error'] = "You do not have permission to access this page";
        header('Location: ../dashboard.php');
        exit();
    }
}

/**
 * Log in user by username/email + password
 */
function login($username, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, 
                   s.student_number, s.major, s.academic_year,
                   t.employee_id, t.department as teacher_dept,
                   ss.company_name, ss.position
            FROM users u
            LEFT JOIN students s ON u.user_id = s.student_id
            LEFT JOIN teachers t ON u.user_id = t.teacher_id
            LEFT JOIN site_supervisors ss ON u.user_id = ss.supervisor_id
            WHERE (u.username = ? OR u.email = ?) AND u.is_active = 1
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['phone'] = $user['phone'];
            $_SESSION['profile_image'] = $user['profile_image'];
            
            // extra info by user type
            if ($user['user_type'] == 'student') {
                $_SESSION['student_number'] = $user['student_number'];
                $_SESSION['major'] = $user['major'];
                $_SESSION['academic_year'] = $user['academic_year'];
            } elseif ($user['user_type'] == 'teacher') {
                $_SESSION['employee_id'] = $user['employee_id'];
                $_SESSION['department'] = $user['teacher_dept'];
            } elseif ($user['user_type'] == 'supervisor') {
                $_SESSION['company_name'] = $user['company_name'];
                $_SESSION['position'] = $user['position'];
            }
            
            // update last login
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $updateStmt->execute([$user['user_id']]);
            
            return true;
        }
        return false;
    } catch(PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log out user
 */
function logout() {
    session_unset();
    session_destroy();
    header('Location: ../login.php');
    exit();
}

/**
 * Redirect user to the appropriate dashboard based on role
 */
function redirectBasedOnUserType($userType) {
    switch($userType) {
        case 'student':
            header('Location: ./students/dashboard.php');
            break;
        case 'supervisor':
            header('Location: ../supervisors/dashboard.php');
            break;
        case 'teacher':
            header('Location: ../teachers/dashboard.php');
            break;
        case 'admin':
            header('Location: ../admin/user-management.php');
            break;
        default:
            header('Location: ../dashboard.php');
    }
    exit();
}

/**
 * Sanitize input (string or array)
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>