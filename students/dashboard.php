<?php
define('APP_LOADED', true);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
checkUserType(['student']);

$pageTitle = "Student Dashboard";

// Get student ID
$studentId = $_SESSION['user_id'];

// Get student details
$stmt = $pdo->prepare("
    SELECT s.*, u.first_name, u.last_name, u.email, u.phone 
    FROM students s
    JOIN users u ON s.student_id = u.user_id
    WHERE s.student_id = ?
");
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent internships
$internshipsStmt = $pdo->prepare("
    SELECT * FROM internships 
    WHERE student_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$internshipsStmt->execute([$studentId]);
$recentInternships = $internshipsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent tasks
$tasksStmt = $pdo->prepare("
    SELECT t.*, i.company_name 
    FROM tasks t
    JOIN internships i ON t.internship_id = i.internship_id
    WHERE i.student_id = ?
    ORDER BY t.deadline ASC
    LIMIT 5
");
$tasksStmt->execute([$studentId]);
$recentTasks = $tasksStmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent reports
$reportsStmt = $pdo->prepare("
    SELECT r.*, i.company_name 
    FROM reports r
    JOIN internships i ON r.internship_id = i.internship_id
    WHERE i.student_id = ?
    ORDER BY r.submission_date DESC
    LIMIT 5
");
$reportsStmt->execute([$studentId]);
$recentReports = $reportsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread notifications count
$unreadNotificationsStmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM notifications 
    WHERE user_id = ? AND is_read = 0
");
$unreadNotificationsStmt->execute([$studentId]);
$unreadNotifications = $unreadNotificationsStmt->fetchColumn();

// Get unread messages count
$unreadMessagesStmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM messages 
    WHERE receiver_id = ? AND is_read = 0
");
$unreadMessagesStmt->execute([$studentId]);
$unreadMessages = $unreadMessagesStmt->fetchColumn();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="page-header">
    <h1>Welcome, <?php echo $_SESSION['first_name']; ?></h1>
    <div class="breadcrumbs">
        <a href="dashboard.php">Dashboard</a> / Overview
    </div>
    <div class="actions">
        <a href="internship-registration.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Register New Internship
        </a>
    </div>
</div>

<!-- Stats cards -->
<div class="stats-container">
    <div class="stat-card">
        <div class="stat-icon stat-icon-primary">
            <i class="fas fa-briefcase"></i>
        </div>
        <div class="stat-content">
            <h3>My Internships</h3>
            <div class="stat-value"><?php echo count($recentInternships); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon stat-icon-warning">
            <i class="fas fa-tasks"></i>
        </div>
        <div class="stat-content">
            <h3>Pending Tasks</h3>
            <div class="stat-value">
                <?php 
                $pendingTasks = array_filter($recentTasks, fn($t) => $t['status'] === 'pending' || $t['status'] === 'in_progress');
                echo count($pendingTasks);
                ?>
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon stat-icon-success">
            <i class="fas fa-file-alt"></i>
        </div>
        <div class="stat-content">
            <h3>Submitted Reports</h3>
            <div class="stat-value"><?php echo count($recentReports); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon stat-icon-danger">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-content">
            <h3>Average Score</h3>
            <div class="stat-value">8.5/10</div>
        </div>
    </div>
</div>

<!-- Recent internships -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="mb-0">My Internships</h3>
        <a href="my-internships.php" class="btn btn-sm btn-secondary">View All</a>
    </div>
    <div class="card-body">
        <?php if (empty($recentInternships)): ?>
            <div class="text-center py-4">
                <i class="fas fa-briefcase fa-2x text-muted mb-3"></i>
                <p class="text-muted">You don't have any internships registered yet</p>
                <a href="internship-registration.php" class="btn btn-primary mt-2">
                    <i class="fas fa-plus"></i> Register Internship
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Start Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentInternships as $internship): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $internship['company_name']; ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo $internship['internship_title']; ?></small>
                                </td>
                                <td><?php echo $internship['duration_weeks']; ?> weeks</td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $internship['status'] === 'active' ? 'success' : 
                                             ($internship['status'] === 'completed' ? 'primary' : 
                                             ($internship['status'] === 'pending' ? 'warning' : 'danger'));
                                    ?>">
                                        <?php 
                                        $statuses = [
                                            'pending' => 'Pending Approval',
                                            'approved' => 'Approved',
                                            'rejected' => 'Rejected',
                                            'active' => 'Active',
                                            'completed' => 'Completed',
                                            'cancelled' => 'Cancelled'
                                        ];
                                        echo $statuses[$internship['status']] ?? $internship['status'];
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($internship['start_date'])); ?></td>
                                <td>
                                    <a href="internship-details.php?id=<?php echo $internship['internship_id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($internship['status'] === 'pending'): ?>
                                        <a href="edit-internship.php?id=<?php echo $internship['internship_id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
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

<!-- Recent tasks -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="mb-0">Recent Tasks</h3>
        <a href="tasks-reports.php" class="btn btn-sm btn-secondary">View All</a>
    </div>
    <div class="card-body">
        <?php if (empty($recentTasks)): ?>
            <div class="text-center py-4">
                <i class="fas fa-tasks fa-2x text-muted mb-3"></i>
                <p class="text-muted">No tasks assigned yet</p>
            </div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($recentTasks as $task): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <h5 class="mb-1"><?php echo $task['task_title']; ?></h5>
                            <span class="badge bg-<?php 
                                echo $task['status'] === 'completed' ? 'success' : 
                                     ($task['status'] === 'overdue' ? 'danger' : 'warning');
                            ?>">
                                <?php 
                                $statuses = [
                                    'pending' => 'Pending',
                                    'in_progress' => 'In Progress',
                                    'completed' => 'Completed',
                                    'overdue' => 'Overdue'
                                ];
                                echo $statuses[$task['status']] ?? $task['status'];
                                ?>
                            </span>
                        </div>
                        <div class="mb-1">
                            <small class="text-muted">
                                <i class="fas fa-building"></i> <?php echo $task['company_name']; ?>
                            </small>
                        </div>
                        <p class="mb-1 text-muted"><?php echo substr($task['task_description'], 0, 100) . '...'; ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i> Due: <?php echo date('M d, Y', strtotime($task['deadline'])); ?>
                            </small>
                            <a href="tasks-reports.php?task_id=<?php echo $task['task_id']; ?>" class="btn btn-sm btn-primary">
                                Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent reports -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="mb-0">Recent Reports</h3>
        <a href="tasks-reports.php" class="btn btn-sm btn-secondary">View All</a>
    </div>
    <div class="card-body">
        <?php if (empty($recentReports)): ?>
            <div class="text-center py-4">
                <i class="fas fa-file-alt fa-2x text-muted mb-3"></i>
                <p class="text-muted">No reports submitted yet</p>
                <a href="tasks-reports.php" class="btn btn-primary mt-2">
                    <i class="fas fa-plus"></i> Submit Report
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
                        <?php foreach ($recentReports as $report): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $report['report_title']; ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo $report['report_period'] ?? 'N/A'; ?></small>
                                </td>
                                <td><?php echo $report['company_name']; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $report['report_type'] === 'weekly' ? 'primary' : 
                                             ($report['report_type'] === 'monthly' ? 'info' : 'success');
                                    ?>">
                                        <?php 
                                        $types = [
                                            'weekly' => 'Weekly',
                                            'monthly' => 'Monthly',
                                            'final' => 'Final',
                                            'task' => 'Task'
                                        ];
                                        echo $types[$report['report_type']] ?? ucfirst($report['report_type']);
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($report['submission_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $report['status'] === 'approved' ? 'success' : 
                                             ($report['status'] === 'needs_revision' ? 'warning' : 
                                             ($report['status'] === 'rejected' ? 'danger' : 'info'));
                                    ?>">
                                        <?php 
                                        $statuses = [
                                            'submitted' => 'Submitted',
                                            'under_review' => 'Under Review',
                                            'approved' => 'Approved',
                                            'needs_revision' => 'Needs Revision',
                                            'rejected' => 'Rejected'
                                        ];
                                        echo $statuses[$report['status']] ?? ucfirst(str_replace('_', ' ', $report['status']));
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
                                    <div class="d-flex gap-1">
                                        <a href="#" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-warning">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <?php if ($report['status'] === 'needs_revision'): ?>
                                            <a href="#" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto scroll to the notifications badge if there are unread notifications
        const notificationBadge = document.querySelector('.notification-badge');
        if (notificationBadge && notificationBadge.textContent > 0) {
            notificationBadge.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>