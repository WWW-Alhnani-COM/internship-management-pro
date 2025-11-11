<?php
// includes/sidebar.php

// التأكد من تحميل ملفات التهيئة
if (!defined('APP_LOADED')) {
    require_once __DIR__ . '/init.php';
    require_once __DIR__ . '/auth.php';
}

// تحديد نوع المستخدم الحالي
$userType = $_SESSION['user_type'] ?? null;

// تحديد عناصر القائمة بناءً على نوع المستخدم
$menuItems = [];

// القائمة الأساسية المشتركة
$commonMenu = [
    [
        'title' => 'Dashboard',
        'icon' => 'fas fa-tachometer-alt',
        'url' => $userType === 'student' ? 'dashboard.php' :
                 ($userType === 'teacher' ? 'dashboard.php' :
                 ($userType === 'supervisor' ? 'dashboard.php' : 'dashboard.php')),
        'active' => basename($_SERVER['PHP_SELF']) == 'dashboard.php'
    ],
    [
        'title' => 'Messages',
        'icon' => 'fas fa-envelope',
        'url' => '../communication/messaging.php',
        'active' => strpos($_SERVER['PHP_SELF'], 'messaging.php') !== false
    ],
    [
        'title' => 'Notifications',
        'icon' => 'fas fa-bell',
        'url' => ($userType === 'student' ? 'notifications.php' :
                  ($userType === 'teacher' ? 'notifications.php' :
                  ($userType === 'supervisor' ? 'notifications.php' : 'notifications.php'))),
        'active' => basename($_SERVER['PHP_SELF']) == 'notifications.php'
    ]
];

// قائمة الطالب
if ($userType === 'student') {
    $menuItems = array_merge($commonMenu, [
        [
            'title' => 'Internships',
            'icon' => 'fas fa-briefcase',
            'url' => '#',
            'hasSubmenu' => true,
            'active' => in_array(basename($_SERVER['PHP_SELF']), ['internship-registration.php', 'my-internships.php']),
            'submenu' => [
                [
                    'title' => 'Register Internship',
                    'url' => 'internship-registration.php',
                    'active' => basename($_SERVER['PHP_SELF']) == 'internship-registration.php'
                ],
                [
                    'title' => 'My Internships',
                    'url' => 'my-internships.php',
                    'active' => basename($_SERVER['PHP_SELF']) == 'my-internships.php'
                ]
            ]
        ],
        [
            'title' => 'Tasks & Reports',
            'icon' => 'fas fa-tasks',
            'url' => 'tasks-reports.php',
            'active' => basename($_SERVER['PHP_SELF']) == 'tasks-reports.php'
        ],
        [
            'title' => 'Evaluations',
            'icon' => 'fas fa-star',
            'url' => 'evaluation-feedback.php',
            'active' => basename($_SERVER['PHP_SELF']) == 'evaluation-feedback.php'
        ],
        [
            'title' => 'Profile',
            'icon' => 'fas fa-user',
            'url' => 'profile.php',
            'active' => basename($_SERVER['PHP_SELF']) == 'profile.php'
        ]
    ]);
}
// قائمة المشرف الأكاديمي
elseif ($userType === 'teacher') {
    $menuItems = array_merge($commonMenu, [
        [
            'title' => 'Student Tracking',
            'icon' => 'fas fa-user-graduate',
            'url' => 'student-tracking.php',
            'active' => basename($_SERVER['PHP_SELF']) == 'student-tracking.php'
        ],
        [
            'title' => 'Academic Evaluation',
            'icon' => 'fas fa-clipboard-check',
            'url' => 'academic-evaluation.php',
            'active' => basename($_SERVER['PHP_SELF']) == 'academic-evaluation.php'
        ],
        [
            'title' => 'Site Visits',
            'icon' => 'fas fa-map-marker-alt',
            'url' => 'site-visits.php',
            'active' => basename($_SERVER['PHP_SELF']) == 'site-visits.php'
        ],
        [
            'title' => 'Profile',
            'icon' => 'fas fa-user',
            'url' => 'profile.php',
            'active' => basename($_SERVER['PHP_SELF']) == 'profile.php'
        ]
    ]);
}
// قائمة المشرف الميداني
elseif ($userType === 'supervisor') {
    $menuItems = array_merge($commonMenu, [
        [
            'title' => 'Internships',
            'icon' => 'fas fa-briefcase',
            'url' => '#',
            'hasSubmenu' => true,
            'active' => in_array(basename($_SERVER['PHP_SELF']), ['agreement-approval.php', 'weekly-tasks.php', 'final-evaluation.php']),
            'submenu' => [
                [
                    'title' => 'Agreement Approval',
                    'url' => 'agreement-approval.php',
                    'active' => basename($_SERVER['PHP_SELF']) == 'agreement-approval.php'
                ],
                [
                    'title' => 'Weekly Tasks',
                    'url' => 'weekly-tasks.php',
                    'active' => basename($_SERVER['PHP_SELF']) == 'weekly-tasks.php'
                ],
                [
                    'title' => 'Final Evaluation',
                    'url' => 'final-evaluation.php',
                    'active' => basename($_SERVER['PHP_SELF']) == 'final-evaluation.php'
                ]
            ]
        ],
        [
            'title' => 'Profile',
            'icon' => 'fas fa-user',
            'url' => 'profile.php',
            'active' => basename($_SERVER['PHP_SELF']) == 'profile.php'
        ]
    ]);
}
// قائمة المدير
elseif ($userType === 'admin') {
    $menuItems = array_merge($commonMenu, [
        [
            'title' => 'User Management',
            'icon' => 'fas fa-users',
            'url' => '../admin/user-management.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'user-management.php') !== false
        ],
        [
            'title' => 'Reports & Analytics',
            'icon' => 'fas fa-chart-bar',
            'url' => '../admin/reports-analytics.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'reports-analytics.php') !== false
        ],
        [
            'title' => 'System Settings',
            'icon' => 'fas fa-cog',
            'url' => '../admin/system-settings.php',
            'active' => strpos($_SERVER['PHP_SELF'], 'system-settings.php') !== false
        ]
    ]);
}
?>

<!-- القائمة الجانبية -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3>Main Menu</h3>
    </div>
    <ul class="sidebar-menu">
        <?php foreach ($menuItems as $item): ?>
            <li class="menu-item <?php echo ($item['hasSubmenu'] ?? false) ? 'has-submenu' : ''; ?>">
                <a href="<?php echo $item['url'] ?? '#'; ?>" 
                   class="menu-link <?php echo ($item['active'] ?? false) ? 'active' : ''; ?>"
                   <?php echo ($item['hasSubmenu'] ?? false) ? 'data-toggle="submenu"' : ''; ?>>
                    <i class="<?php echo $item['icon'] ?? 'fas fa-circle'; ?>"></i>
                    <span><?php echo $item['title'] ?? 'Untitled'; ?></span>
                    <?php if ($item['hasSubmenu'] ?? false): ?>
                        <i class="fas fa-chevron-left submenu-icon ms-auto"></i>
                    <?php endif; ?>
                </a>
                <?php if (($item['hasSubmenu'] ?? false) && !empty($item['submenu'])): ?>
                    <ul class="submenu <?php echo ($item['active'] ?? false) ? 'active' : ''; ?>">
                        <?php foreach ($item['submenu'] as $subItem): ?>
                            <li class="submenu-item">
                                <a href="<?php echo $subItem['url'] ?? '#'; ?>" 
                                   class="submenu-link <?php echo ($subItem['active'] ?? false) ? 'active' : ''; ?>">
                                    <?php echo $subItem['title'] ?? 'Untitled'; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</aside>

<!-- المحتوى الرئيسي -->
<main class="main-content" id="main-content">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // تبديل القائمة الجانبية على الجوال
            const toggleBtn = document.querySelector('.toggle-sidebar');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    mainContent.classList.toggle('sidebar-active');
                });
            }
            
            // التحكم في القوائم الفرعية
            const submenuToggles = document.querySelectorAll('[data-toggle="submenu"]');
            submenuToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const submenu = this.nextElementSibling;
                    const icon = this.querySelector('.submenu-icon');
                    
                    if (submenu && submenu.classList.contains('submenu')) {
                        submenu.classList.toggle('active');
                        if (icon) {
                            icon.classList.toggle('rotate-180');
                        }
                    }
                });
            });
        });
    </script>