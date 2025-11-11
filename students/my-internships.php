<?php
define('APP_LOADED', true);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
checkUserType(['student']);

$pageTitle = "My Internships";
$studentId = $_SESSION['user_id'];

// جلب التدريبات مع المشرفين بشكل صحيح
$stmt = $pdo->prepare("
    SELECT i.*,
           su.first_name as supervisor_first, 
           su.last_name as supervisor_last,
           su.email as supervisor_email,
           ss.company_name as supervisor_company,
           ss.position as supervisor_position,
           tu.first_name as teacher_first, 
           tu.last_name as teacher_last,
           t.department as teacher_dept
    FROM internships i
    LEFT JOIN site_supervisors ss ON i.supervisor_id = ss.supervisor_id
    LEFT JOIN users su ON ss.supervisor_id = su.user_id
    LEFT JOIN teachers t ON i.teacher_id = t.teacher_id
    LEFT JOIN users tu ON t.teacher_id = tu.user_id
    WHERE i.student_id = ?
    ORDER BY i.created_at DESC
");
$stmt->execute([$studentId]);
$internships = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="page-header">
    <h1>My Internships</h1>
    <div class="breadcrumbs">
        <a href="dashboard.php">Dashboard</a> / My Internships
    </div>
    <div class="actions">
        <a href="internship-registration.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Register New Internship
        </a>
    </div>
</div>

<?php if (empty($internships)): ?>
    <div class="card text-center">
        <div class="card-body py-5">
            <i class="fas fa-briefcase fa-2x text-muted mb-3"></i>
            <h4 class="mb-3">No internships registered</h4>
            <p class="text-muted">You haven't registered any internships yet.</p>
            <a href="internship-registration.php" class="btn btn-primary mt-2">
                <i class="fas fa-plus"></i> Register Your First Internship
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Field Supervisor</th>
                            <th>Academic Supervisor</th>
                            <th>Period</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($internships as $internship): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($internship['company_name']); ?></strong>
                                    <br>
                                    <small><?php echo htmlspecialchars($internship['internship_title'] ?? 'N/A'); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($internship['supervisor_first'] . ' ' . $internship['supervisor_last']); ?>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($internship['supervisor_position'] ?? 'N/A'); ?> at 
                                        <?php echo htmlspecialchars($internship['supervisor_company'] ?? 'N/A'); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($internship['teacher_first'] . ' ' . $internship['teacher_last']); ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($internship['teacher_dept'] ?? 'N/A'); ?></small>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($internship['start_date'])); ?> →
                                    <?php echo date('M d, Y', strtotime($internship['end_date'])); ?>
                                    <br>
                                    <small class="text-muted"><?php echo $internship['duration_weeks']; ?> weeks</small>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $internship['status'] === 'active' ? 'success' : 
                                             ($internship['status'] === 'completed' ? 'primary' : 
                                             ($internship['status'] === 'pending' ? 'warning' : 'danger'));
                                    ?>">
                                        <?php 
                                        $statusMap = [
                                            'pending' => 'Pending',
                                            'approved' => 'Approved',
                                            'rejected' => 'Rejected',
                                            'active' => 'Active',
                                            'completed' => 'Completed',
                                            'cancelled' => 'Cancelled'
                                        ];
                                        echo $statusMap[$internship['status']] ?? ucfirst($internship['status']);
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="internship-details.php?id=<?php echo $internship['internship_id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if (in_array($internship['status'], ['pending', 'needs_revision'])): ?>
                                        <a href="edit-internship.php?id=<?php echo $internship['internship_id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>