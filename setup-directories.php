<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$requiredDirs = [
    'uploads/reports',
    'uploads/agreements',
    'uploads/evaluations',
    'uploads/site-visits'
];

$messages = [];
$success = true;

foreach ($requiredDirs as $dir) {
    $fullPath = __DIR__ . '/' . $dir;
    
    if (!file_exists($fullPath)) {
        // محاولة إنشاء المجلد بكل المجلدات الفرعية
        if (mkdir($fullPath, 0777, true)) {
            $messages[] = "✓ تم إنشاء المجلد: $dir";
        } else {
            $messages[] = "✗ فشل إنشاء المجلد: $dir";
            $success = false;
        }
    } else {
        $messages[] = "✓ المجلد موجود: $dir";
    }
    
    // التحقق من صلاحية الكتابة
    if (is_writable($fullPath)) {
        $messages[] = "✓ صلاحيات الكتابة متاحة للمجلد: $dir";
    } else {
        // محاولة ضبط الصلاحيات
        if (chmod($fullPath, 0777)) {
            $messages[] = "✓ تم ضبط صلاحيات الكتابة للمجلد: $dir";
        } else {
            $messages[] = "✗ لا يمكن ضبط صلاحيات الكتابة للمجلد: $dir (يجب ضبطها يدويًا)";
            $success = false;
        }
    }
}

// إنشاء ملف .htaccess لحماية الملفات
$htaccess = <<<HTACCESS
Order deny,allow
Deny from all
<Files ~ "\.(pdf|doc|docx|jpg|jpeg|png|gif)$">
    Allow from all
</Files>
HTACCESS;
foreach ($requiredDirs as $dir) {
    $htaccessPath = __DIR__ . '/' . $dir . '/.htaccess';
    if (!file_exists($htaccessPath)) {
        file_put_contents($htaccessPath, $htaccess);
        $messages[] = "✓ تم إنشاء ملف حماية .htaccess للمجلد: $dir";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعداد مجلدات التحميل</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 600px;
            text-align: center;
        }
        .status {
            font-size: 1.5rem;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .success {
            color: #27ae60;
        }
        .error {
            color: #e74c3c;
        }
        .messages {
            text-align: right;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            max-height: 300px;
            overflow-y: auto;
        }
        .message {
            margin: 5px 0;
            padding: 3px 0;
            border-bottom: 1px solid #eee;
        }
        .message.success {
            color: #27ae60;
        }
        .message.error {
            color: #e74c3c;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            margin-top: 20px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>إعداد مجلدات التحميل</h1>
        <div class="status <?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $success ? 'تم الإعداد بنجاح' : 'يوجد أخطاء في الإعداد'; ?>
        </div>
        
        <div class="messages">
            <?php foreach ($messages as $msg): ?>
                <div class="message <?php echo strpos($msg, '✓') !== false ? 'success' : 'error'; ?>">
                    <?php echo $msg; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <a href="students/dashboard.php" class="btn">الانتقال إلى لوحة التحكم</a>
    </div>
</body>
</html>