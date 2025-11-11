<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'controllers/AuthControllerSimple.php';
$auth = new AuthControllerSimple();

// إذا لم يكن المستخدم مسجل دخول، توجيهه
if(!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// بيانات المستخدم
$user_type = $_SESSION['user_type'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - نظام إدارة التدريب</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: #f8f9fa;
        }
        
        .navbar {
            background: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .welcome {
            color: #2c3e50;
        }
        
        .user-type {
            background: #3498db;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        
        .btn {
            padding: 8px 15px;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .dashboard {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .welcome-section {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 30px;
        }
        
        .welcome-section h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .welcome-section p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-weight: 600;
        }
        
        .quick-actions {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .quick-actions h2 {
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .action-btn {
            background: #3498db;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: transform 0.3s;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <!-- شريط التنقل -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <i class="fas fa-graduation-cap"></i>
                <span>نظام التدريب</span>
            </div>
            <div class="user-info">
                <span class="welcome">مرحباً، <?php echo $first_name . ' ' . $last_name; ?></span>
                <span class="user-type"><?php echo $user_type; ?></span>
                <a href="logout.php" class="btn">تسجيل الخروج</a>
            </div>
        </div>
    </nav>

    <!-- لوحة التحكم -->
    <div class="dashboard">
        <div class="welcome-section">
            <h1>مرحباً بك في لوحة التحكم</h1>
            <p>يمكنك من هنا إدارة جميع جوانب التدريب الخاص بك</p>
            <p><strong>اسم المستخدم:</strong> <?php echo $username; ?></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">5</div>
                <div class="stat-label">المهام المعلقة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">3</div>
                <div class="stat-label">التقارير المطلوبة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">85%</div>
                <div class="stat-label">معدل الإنجاز</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">12</div>
                <div class="stat-label">الرسائل الجديدة</div>
            </div>
        </div>

        <div class="quick-actions">
            <h2>الإجراءات السريعة</h2>
            <div class="actions-grid">
                <a href="#" class="action-btn">عرض المهام</a>
                <a href="#" class="action-btn">رفع تقرير</a>
                <a href="#" class="action-btn">التواصل</a>
                <a href="#" class="action-btn">التقييمات</a>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>