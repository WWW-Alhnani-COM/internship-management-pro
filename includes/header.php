<?php
// Make sure configuration and authentication files are included
if (!defined('APP_LOADED')) {
    require_once __DIR__ . '/init.php';
    require_once __DIR__ . '/auth.php';
    
    // Prevent access if user is not logged in
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Internship Management System'; ?> | <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 70px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 1001;
        }
        
        .logo img {
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px;
        }
        
        .logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 1001;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary-color);
            font-weight: bold;
            overflow: hidden;
        }
        
        .user-name {
            position: relative;
        }
        
        .user-type-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 3px;
        }
        
        .user-type-student { background: rgba(40, 167, 69, 0.2); color: #28a745; }
        .user-type-teacher { background: rgba(255, 193, 7, 0.2); color: #856404; }
        .user-type-supervisor { background: rgba(33, 150, 243, 0.2); color: #1976d2; }
        .user-type-admin { background: rgba(220, 53, 69, 0.2); color: #dc3545; }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: white;
            height: calc(100vh - 70px);
            position: fixed;
            top: 70px;
            left: 0;
            padding: 20px 0;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            z-index: 900;
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .menu-title {
            padding: 10px 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .menu-item {
            padding: 0;
        }
        
        .menu-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 20px;
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .menu-link:hover, .menu-link.active {
            background: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
            border-left-color: var(--primary-color);
        }
        
        .menu-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .submenu {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        
        .submenu.active {
            max-height: 300px;
        }
        
        .submenu-item {
            padding-left: 45px;
        }
        
        .submenu-link {
            display: block;
            padding: 8px 0;
            color: #7f8c8d;
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.3s;
        }
        
        .submenu-link:hover, .submenu-link.active {
            color: var(--primary-color);
        }
        
        /* Main content */
        .main-content {
            margin-left: 260px;
            margin-top: 85px;
            padding: 30px;
            min-height: calc(100vh - 150px);
            transition: margin-left 0.3s;
        }
        
        .sidebar.collapsed {
            transform: translateX(-260px);
        }
        
        .main-content.sidebar-collapsed {
            margin-left: 80px;
        }
        
        .page-header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .page-header h1 {
            font-size: 1.8rem;
            color: var(--secondary-color);
            margin-bottom: 10px;
        }
        
        .page-header .breadcrumbs {
            color: #7f8c8d;
            font-size: 0.95rem;
        }
        
        .page-header .breadcrumbs a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .page-header .actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        /* Buttons */
        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.85rem;
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        /* Alerts */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid #c3e6cb;
        }
        
        .alert-info {
            background: rgba(52, 152, 219, 0.2);
            color: #17a2b8;
            border: 1px solid #bee5eb;
        }
        
        .alert-warning {
            background: rgba(255, 193, 7, 0.2);
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Stats cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-icon-primary {
            background: rgba(52, 152, 219, 0.15);
            color: var(--primary-color);
        }
        
        .stat-icon-success {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }
        
        .stat-icon-warning {
            background: rgba(255, 193, 7, 0.15);
            color: var(--warning-color);
        }
        
        .stat-icon-danger {
            background: rgba(220, 53, 69, 0.15);
            color: var(--danger-color);
        }
        
        .stat-content h3 {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .stat-content .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--secondary-color);
        }
        
        /* Toggle sidebar button */
        .toggle-sidebar {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            z-index: 1001;
        }
        
        /* Responsive design */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-260px);
                left: -260px;
            }
            
            .sidebar.active {
                transform: translateX(0);
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                margin-top: 85px;
            }
            
            .main-content.sidebar-active {
                margin-left: 260px;
            }
            
            .toggle-sidebar {
                display: block;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 20px 15px;
            }
            
            .stat-card {
                flex-direction: column;
                text-align: center;
            }
            
            .stat-icon {
                margin: 0 auto;
            }
            
            .page-header .actions {
                flex-direction: column;
                gap: 10px;
            }
        }
        
        /* Notifications badge */
        .notification-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            font-size: 0.7rem;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2px;
        }
        
        /* Message elements */
        .message-container {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .received {
            background: #f8f9fa;
        }
        
        .sent {
            background: rgba(52, 152, 219, 0.1);
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary-color);
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .message-content {
            flex: 1;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .message-sender {
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        .message-time {
            font-size: 0.8rem;
            color: #7f8c8d;
        }
        
        .message-text {
            line-height: 1.5;
        }
        
        .message-input-container {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 15px 0;
            border-top: 1px solid #eee;
            margin-top: 20px;
        }
        
        /* Notification items */
        .notification-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background 0.3s;
        }
        
        .notification-item:hover {
            background: #f8f9fa;
        }
        
        .notification-item.unread {
            background: rgba(52, 152, 219, 0.05);
            border-left: 3px solid var(--primary-color);
        }
        
        .notification-content {
            display: flex;
            gap: 15px;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(52, 152, 219, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            flex-shrink: 0;
        }
        
        .notification-details {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .notification-message {
            color: #7f8c8d;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .notification-time {
            font-size: 0.8rem;
            color: #95a5a6;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="logo">
            <i class="fas fa-graduation-cap" style="font-size: 1.8rem;"></i>
            <h1><?php echo APP_NAME; ?></h1>
        </div>
        <div class="user-info">
            <div class="notification-bell">
                <a href="notifications.php" class="text-white position-relative" style="text-decoration: none;">
                    <i class="fas fa-bell fa-lg"></i>
                    <?php if (isset($unreadNotifications) && $unreadNotifications > 0): ?>
                        <span class="notification-badge"><?php echo $unreadNotifications; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <div class="messages-icon">
                <a href="../communication/messaging.php" class="text-white position-relative" style="text-decoration: none;">
                    <i class="fas fa-envelope fa-lg"></i>
                    <?php if (isset($unreadMessages) && $unreadMessages > 0): ?>
                        <span class="notification-badge"><?php echo $unreadMessages; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <div class="user-name">
                <div><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></div>
                <span class="user-type-badge user-type-<?php echo $_SESSION['user_type']; ?>">
                    <?php 
                    $userTypes = [
                        'student' => 'Student',
                        'teacher' => 'Academic Supervisor',
                        'supervisor' => 'Field Supervisor',
                        'admin' => 'System Admin'
                    ];
                    echo $userTypes[$_SESSION['user_type']] ?? ucfirst($_SESSION['user_type']);
                    ?>
                </span>
            </div>
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?>
            </div>
            <button class="toggle-sidebar">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <?php 
    // Determine the current user type
    $userType = $_SESSION['user_type'];
    ?>