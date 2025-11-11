<?php
define('APP_LOADED', true);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
checkUserType(['student']);

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Invalid report ID.";
    header('Location: tasks-reports.php');
    exit();
}

$reportId = (int)$_GET['id'];
$studentId = $_SESSION['user_id'];

// جلب تفاصيل التقرير مع التحقق من الملكية
$stmt = $pdo->prepare("
    SELECT r.*, i.company_name, i.internship_title,
           su.first_name as supervisor_first, su.last_name as supervisor_last,
           tu.first_name as teacher_first, tu.last_name as teacher_last,
           t.task_title, t.task_description
    FROM reports r
    JOIN internships i ON r.internship_id = i.internship_id
    LEFT JOIN tasks t ON r.task_id = t.task_id
    LEFT JOIN site_supervisors ss ON i.supervisor_id = ss.supervisor_id
    LEFT JOIN users su ON ss.supervisor_id = su.user_id
    LEFT JOIN teachers te ON i.teacher_id = te.teacher_id
    LEFT JOIN users tu ON te.teacher_id = tu.user_id
    WHERE r.report_id = ? AND i.student_id = ?
");
$stmt->execute([$reportId, $studentId]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    $_SESSION['error'] = "Report not found or you don't have permission to view it.";
    header('Location: tasks-reports.php');
    exit();
}

$pageTitle = "Report Details: " . htmlspecialchars($report['report_title']);

// تنزيل الملف إذا طلب المستخدم
if (isset($_GET['download'])) {
    $filePath = __DIR__ . '/../uploads/reports/' . $report['report_file_path'];
    
    if (file_exists($filePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($report['report_file_name']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        $_SESSION['error'] = "File not found.";
        header('Location: report-details.php?id=' . $reportId);
        exit();
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="page-header">
    <h1>Report Details</h1>
    <div class="breadcrumbs">
        <a href="dashboard.php">Dashboard</a> / 
        <a href="tasks-reports.php">Tasks & Reports</a> / 
        Details
    </div>
    <div class="actions">
        <?php if ($report['report_file_path']): ?>
            <a href="?id=<?php echo $reportId; ?>&download=1" class="btn btn-sm btn-success" title="Download Report File">
                <i class="fas fa-download"></i> Download File
            </a>
        <?php endif; ?>
        <a href="tasks-reports.php" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Reports
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <h3><?php echo htmlspecialchars($report['report_title']); ?></h3>
                <p class="text-muted"><?php echo htmlspecialchars($report['report_description'] ?? 'No description provided'); ?></p>
                
                <?php if ($report['task_id']): ?>
                    <div class="mt-4">
                        <h5>Related Task</h5>
                        <p><strong><?php echo htmlspecialchars($report['task_title']); ?></strong></p>
                        <p><?php echo nl2br(htmlspecialchars($report['task_description'] ?? 'No task description')); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <h5>Report Status</h5>
                    <span class="badge bg-<?php 
                        echo $report['status'] === 'approved' ? 'success' : 
                             ($report['status'] === 'needs_revision' ? 'warning' : 
                             ($report['status'] === 'rejected' ? 'danger' : 'info'));
                    ?>">
                        <?php 
                        $statusMap = [
                            'submitted' => 'Submitted',
                            'under_review' => 'Under Review',
                            'approved' => 'Approved',
                            'needs_revision' => 'Needs Revision',
                            'rejected' => 'Rejected'
                        ];
                        echo $statusMap[$report['status']] ?? ucfirst(str_replace('_', ' ', $report['status']));
                        ?>
                    </span>
                    <?php if ($report['grade']): ?>
                        <span class="badge bg-primary ms-2">Grade: <?php echo $report['grade']; ?>/10</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($report['status'] !== 'submitted'): ?>
                    <div class="mt-4">
                        <div class="row">
                            <div class="col-md-6">
                                <?php if ($report['supervisor_feedback']): ?>
                                    <div class="card mt-3">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0">Field Supervisor Feedback</h6>
                                        </div>
                                        <div class="card-body">
                                            <p><?php echo nl2br(htmlspecialchars($report['supervisor_feedback'])); ?></p>
                                            <?php if ($report['supervisor_feedback_date']): ?>
                                                <small class="text-muted">Feedback given on: <?php echo date('M d, Y H:i', strtotime($report['supervisor_feedback_date'])); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <?php if ($report['teacher_feedback']): ?>
                                    <div class="card mt-3">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0">Academic Supervisor Feedback</h6>
                                        </div>
                                        <div class="card-body">
                                            <p><?php echo nl2br(htmlspecialchars($report['teacher_feedback'])); ?></p>
                                            <?php if ($report['teacher_feedback_date']): ?>
                                                <small class="text-muted">Feedback given on: <?php echo date('M d, Y H:i', strtotime($report['teacher_feedback_date'])); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Report Information</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <strong>Company:</strong> 
                                <?php echo htmlspecialchars($report['company_name']); ?>
                            </li>
                            <li class="mb-2">
                                <strong>Type:</strong> 
                                <span class="badge bg-<?php echo $report['report_type'] === 'weekly' ? 'primary' : ($report['report_type'] === 'monthly' ? 'info' : 'success'); ?>">
                                    <?php echo ucfirst($report['report_type']); ?>
                                </span>
                            </li>
                            <li class="mb-2">
                                <strong>Submitted:</strong> 
                                <?php echo date('M d, Y H:i', strtotime($report['submission_date'])); ?>
                            </li>
                            <li class="mb-2">
                                <strong>Period:</strong> 
                                <?php echo htmlspecialchars($report['report_period'] ?? 'N/A'); ?>
                            </li>
                            <?php if ($report['report_file_path']): ?>
                                <li class="mb-2">
                                    <strong>File:</strong> 
                                    <span class="text-primary"><?php echo htmlspecialchars($report['report_file_name']); ?></span>
                                    <br>
                                    <small class="text-muted"><?php echo round($report['file_size'] / 1024, 1); ?> KB</small>
                                </li>
                            <?php endif; ?>
                            <li class="mb-2">
                                <strong>Internship:</strong> 
                                <?php echo htmlspecialchars($report['internship_title']); ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="tasks-reports.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Reports List
            </a>
            <?php if ($report['status'] === 'needs_revision'): ?>
                <a href="edit-report.php?id=<?php echo $reportId; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Report
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>