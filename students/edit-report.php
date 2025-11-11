<?php
define('APP_LOADED', true);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
checkUserType(['student']);

if (!isset($_GET['id'])) {
    header('Location: tasks-reports.php');
    exit();
}

$reportId = (int)$_GET['id'];
$studentId = $_SESSION['user_id'];

// التحقق من أن التقرير تابع للطالب وحالته "needs_revision"
$reportStmt = $pdo->prepare("
    SELECT r.*, i.company_name, t.task_title
    FROM reports r
    JOIN internships i ON r.internship_id = i.internship_id
    LEFT JOIN tasks t ON r.task_id = t.task_id
    WHERE r.report_id = ? AND i.student_id = ? AND r.status = 'needs_revision'
");
$reportStmt->execute([$reportId, $studentId]);
$report = $reportStmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    $_SESSION['error'] = "You can only edit reports that need revision.";
    header('Location: tasks-reports.php');
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportTitle = trim($_POST['report_title']);
    $reportDescription = trim($_POST['report_description']);
    
    try {
        $updateStmt = $pdo->prepare("
            UPDATE reports 
            SET report_title = ?, report_description = ?, status = 'submitted'
            WHERE report_id = ?
        ");
        $updateStmt->execute([$reportTitle, $reportDescription, $reportId]);
        
        $_SESSION['success'] = "Report updated successfully and resubmitted for review.";
        header("Location: tasks-reports.php");
        exit();
        
    } catch (PDOException $e) {
        $message = "Error updating report: " . $e->getMessage();
        $messageType = 'danger';
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="page-header">
    <h1>Edit Report</h1>
    <div class="breadcrumbs">
        <a href="dashboard.php">Dashboard</a> / 
        <a href="tasks-reports.php">Tasks & Reports</a> / 
        Edit Report
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h4><?php echo htmlspecialchars($report['report_title']); ?></h4>
        <small>Company: <?php echo htmlspecialchars($report['company_name']); ?></small>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <strong>Feedback from supervisor/teacher:</strong><br>
            <?php echo nl2br(htmlspecialchars($report['teacher_feedback'] ?? $report['supervisor_feedback'] ?? 'No feedback provided')); ?>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>Report Title</label>
                <input type="text" name="report_title" class="form-control" value="<?php echo htmlspecialchars($report['report_title']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Report Description</label>
                <textarea name="report_description" class="form-control" rows="8" required><?php echo htmlspecialchars($report['report_description']); ?></textarea>
            </div>
            
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save and Resubmit
                </button>
                <a href="tasks-reports.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>