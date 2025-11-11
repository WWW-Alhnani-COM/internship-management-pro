<?php
define('APP_LOADED', true);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
checkUserType(['student']);

if (!isset($_GET['task_id'])) {
    header('Location: tasks-reports.php');
    exit();
}

$taskId = (int)$_GET['task_id'];
$studentId = $_SESSION['user_id'];

// التحقق من أن المهمة تخص الطالب
$taskStmt = $pdo->prepare("
    SELECT t.*, i.company_name, i.internship_id
    FROM tasks t
    JOIN internships i ON t.internship_id = i.internship_id
    WHERE t.task_id = ? AND i.student_id = ?
");
$taskStmt->execute([$taskId, $studentId]);
$task = $taskStmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    $_SESSION['error'] = "Task not found or you don't have permission.";
    header('Location: tasks-reports.php');
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportTitle = trim($_POST['report_title']);
    $reportDescription = trim($_POST['report_description']);
    
    try {
        $pdo->beginTransaction();
        
        // إدخال التقرير
        $insertStmt = $pdo->prepare("
            INSERT INTO reports (
                internship_id, task_id, report_title, report_description, 
                report_type, status
            ) VALUES (?, ?, ?, ?, 'task', 'submitted')
        ");
        $insertStmt->execute([
            $task['internship_id'],
            $taskId,
            $reportTitle,
            $reportDescription
        ]);
        
        $reportId = $pdo->lastInsertId();
        
        // رفع الملف إذا تم تحميله
        if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/reports/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $fileType = mime_content_type($_FILES['report_file']['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception('Only PDF or Word documents are allowed.');
            }
            
            if ($_FILES['report_file']['size'] > 10 * 1024 * 1024) { // 10MB
                throw new Exception('File size exceeds 10MB limit.');
            }
            
            $originalName = $_FILES['report_file']['name'];
            $sanitizedName = preg_replace("/[^a-zA-Z0-9\.\-_]/", "", $originalName);
            $fileName = 'report_' . $reportId . '_' . time() . '_' . $sanitizedName;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['report_file']['tmp_name'], $filePath)) {
                $updateFileStmt = $pdo->prepare("
                    UPDATE reports 
                    SET report_file_path = ?, report_file_name = ?, file_size = ?
                    WHERE report_id = ?
                ");
                $updateFileStmt->execute([$fileName, $originalName, $_FILES['report_file']['size'], $reportId]);
            }
        }
        
        // تحديث حالة المهمة
        $updateTaskStmt = $pdo->prepare("UPDATE tasks SET status = 'completed', completion_date = NOW() WHERE task_id = ?");
        $updateTaskStmt->execute([$taskId]);
        
        $pdo->commit();
        
        $_SESSION['success'] = "Report submitted successfully.";
        header("Location: tasks-reports.php");
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="page-header">
    <h1>Submit Task Report</h1>
    <div class="breadcrumbs">
        <a href="dashboard.php">Dashboard</a> / 
        <a href="tasks-reports.php">Tasks & Reports</a> / 
        Submit Report
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h4>Task: <?php echo htmlspecialchars($task['task_title']); ?></h4>
        <small>Company: <?php echo htmlspecialchars($task['company_name']); ?></small>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Report Title</label>
                <input type="text" name="report_title" class="form-control" value="<?php echo htmlspecialchars($task['task_title']); ?> Report" required>
            </div>
            
            <div class="form-group">
                <label>Report Description</label>
                <textarea name="report_description" class="form-control" rows="5" required><?php echo htmlspecialchars($task['task_description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Report File (Optional)</label>
                <input type="file" name="report_file" class="form-control" accept=".pdf,.doc,.docx">
                <small class="text-muted">PDF or Word document (Max 10MB)</small>
            </div>
            
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Submit Report
                </button>
                <a href="tasks-reports.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>