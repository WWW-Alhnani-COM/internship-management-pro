<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// تضمين AuthController
require_once __DIR__ . '/controllers/AuthController.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من تطابق كلمتي المرور
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $message = 'Passwords do not match';
        $messageType = 'error';
    } else {
        $auth = new AuthController();
        $data = [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'username' => $_POST['username'],
            'email' => $_POST['email'],
            'user_type' => $_POST['user_type'],
            'password' => $_POST['password'],
            'phone' => $_POST['phone'] ?? ''
        ];

        $result = $auth->register($data);

        if ($result['success']) {
            $message = 'Your account has been created successfully! You can now login.';
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register New Account - Internship Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .register-header p {
            color: #7f8c8d;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
        }
        
        .links a {
            color: #3498db;
            text-decoration: none;
        }
        
        .message {
            padding: 12px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
            font-weight: 600;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Create New Account</h1>
            <p>Join the Internship Management System and start your journey</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="on">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required autocomplete="given-name" value="<?= $_POST['first_name'] ?? '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required autocomplete="family-name" value="<?= $_POST['last_name'] ?? '' ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username" value="<?= $_POST['username'] ?? '' ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required autocomplete="email" value="<?= $_POST['email'] ?? '' ?>">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" autocomplete="tel" value="<?= $_POST['phone'] ?? '' ?>">
            </div>
            
            <div class="form-group">
                <label for="user_type">Account Type</label>
                <select id="user_type" name="user_type" required>
                    <option value="">Select Account Type</option>
                    <option value="student" <?= ($_POST['user_type'] ?? '') == 'student' ? 'selected' : '' ?>>Student</option>
                    <option value="teacher" <?= ($_POST['user_type'] ?? '') == 'teacher' ? 'selected' : '' ?>>Academic Supervisor</option>
                    <option value="supervisor" <?= ($_POST['user_type'] ?? '') == 'supervisor' ? 'selected' : '' ?>>Field Supervisor</option>
                    <option value="admin" <?= ($_POST['user_type'] ?? '') == 'admin' ? 'selected' : '' ?>>System Admin</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
            </div>
            
            <button type="submit" class="btn">Create Account</button>
        </form>
        
        <div class="links">
            <a href="login.php">Already have an account? Login</a>
        </div>
    </div>
</body>
</html>