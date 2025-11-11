<?php
define('APP_LOADED', true);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
checkUserType(['student']);

$pageTitle = "Tasks & Reports";
$studentId = $_SESSION['user_id'];

// جلب المهام
$tasksStmt = $pdo->prepare("
    SELECT t.*, i.company_name, i.internship_title
    FROM tasks t
    JOIN internships i ON t.internship_id = i.internship_id
    WHERE i.student_id = ?
    ORDER BY t.deadline ASC
");
$tasksStmt->execute([$studentId]);
$tasks = $tasksStmt->fetchAll(PDO::FETCH_ASSOC);

// جلب التقارير
$reportsStmt = $pdo->prepare("
    SELECT r.*, i.company_name
    FROM reports r
    JOIN internships i ON r.internship_id = i.internship_id
    WHERE i.student_id = ?
    ORDER BY r.submission_date DESC
");
$reportsStmt->execute([$studentId]);
$reports = $reportsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="page-header">
    <h1>Tasks & Reports</h1>
    <div class="breadcrumbs">
        <a href="dashboard.php">Dashboard</a> / Tasks & Reports
    </div>
</div>

<!-- المهام -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="mb-0">Assigned Tasks</h3>
        <span class="badge bg-warning"><?php echo count($tasks); ?> tasks</span>
    </div>
    <div class="card-body">
        <?php if (empty($tasks)): ?>
            <div class="text-center py-4">
                <i class="fas fa-tasks fa-2x text-muted mb-3"></i>
                <p class="text-muted">No tasks assigned yet</p>
            </div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($tasks as $task): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <h5 class="mb-1"><?php echo htmlspecialchars($task['task_title']); ?></h5>
                            <span class="badge bg-<?php 
                                echo $task['status'] === 'completed' ? 'success' : 
                                     ($task['status'] === 'overdue' ? 'danger' : 'warning');
                            ?>">
                                <?php 
                                $statusMap = [
                                    'pending' => 'Pending',
                                    'in_progress' => 'In Progress',
                                    'completed' => 'Completed',
                                    'overdue' => 'Overdue'
                                ];
                                echo $statusMap[$task['status']] ?? ucfirst($task['status']);
                                ?>
                            </span>
                        </div>
                        <div class="mb-1">
                            <small class="text-muted">
                                <i class="fas fa-building"></i> <?php echo htmlspecialchars($task['company_name']); ?>
                                <br>
                                <i class="fas fa-clock"></i> Due: <?php echo date('M d, Y', strtotime($task['deadline'])); ?>
                            </small>
                        </div>
                        <p class="text-muted"><?php echo substr(htmlspecialchars($task['task_description'] ?? ''), 0, 100) . '...'; ?></p>
                        <div class="mt-2">
                            <a href="submit-task-report.php?task_id=<?php echo $task['task_id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-upload"></i> Submit Report
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- التقارير -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="mb-0">Submitted Reports</h3>
        <span class="badge bg-success"><?php echo count($reports); ?> reports</span>
    </div>
    <div class="card-body">
        <?php if (empty($reports)): ?>
            <div class="text-center py-4">
                <i class="fas fa-file-alt fa-2x text-muted mb-3"></i>
                <p class="text-muted">No reports submitted yet</p>
                <a href="#" class="btn btn-primary mt-2">
                    <i class="fas fa-plus"></i> Submit First Report
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Company</th>
                            <th>Type</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Grade</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($report['report_title']); ?></td>
                                <td><?php echo htmlspecialchars($report['company_name']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $report['report_type'] === 'weekly' ? 'primary' : 
                                             ($report['report_type'] === 'monthly' ? 'info' : 'success');
                                    ?>">
                                        <?php echo ucfirst($report['report_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($report['submission_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $report['status'] === 'approved' ? 'success' : 
                                             ($report['status'] === 'needs_revision' ? 'warning' : 
                                             ($report['status'] === 'rejected' ? 'danger' : 'secondary'));
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
                                </td>
                                <td>
                                    <?php if ($report['grade']): ?>
                                        <span class="badge bg-success"><?php echo $report['grade']; ?>/10</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                               <td>
    <a href="report-details.php?id=<?php echo $report['report_id']; ?>" class="btn btn-sm btn-info">
        <i class="fas fa-eye"></i> View
    </a>
    <?php if ($report['status'] === 'needs_revision'): ?>
        <a href="edit-report.php?id=<?php echo $report['report_id']; ?>" class="btn btn-sm btn-warning">
            <i class="fas fa-edit"></i> Edit
        </a>
    <?php endif; ?>
</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>