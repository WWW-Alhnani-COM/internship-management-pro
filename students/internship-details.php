<?php
define('APP_LOADED', true);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
checkUserType(['student']);

if (!isset($_GET['id'])) {
    header('Location: my-internships.php');
    exit();
}

$internshipId = (int)$_GET['id'];
$studentId = $_SESSION['user_id'];

// التحقق من أن التدريب يخص الطالب
$checkStmt = $pdo->prepare("SELECT internship_id FROM internships WHERE internship_id = ? AND student_id = ?");
$checkStmt->execute([$internshipId, $studentId]);
if (!$checkStmt->fetch()) {
    $_SESSION['error'] = "You don't have permission to view this internship.";
    header('Location: my-internships.php');
    exit();
}

// جلب تفاصيل التدريب
$stmt = $pdo->prepare("
    SELECT i.*,
           su.first_name as supervisor_first, 
           su.last_name as supervisor_last,
           su.email as supervisor_email,
           su.phone as supervisor_phone,
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
    WHERE i.internship_id = ?
");
$stmt->execute([$internshipId]);
$internship = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$internship) {
    $_SESSION['error'] = "Internship not found.";
    header('Location: my-internships.php');
    exit();
}

$pageTitle = "Internship Details: " . htmlspecialchars($internship['internship_title'] ?? 'N/A');
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="page-header">
    <h1>Internship Details</h1>
    <div class="breadcrumbs">
        <a href="dashboard.php">Dashboard</a> / 
        <a href="my-internships.php">My Internships</a> / 
        Details
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h3><?php echo htmlspecialchars($internship['internship_title']); ?></h3>
        <p class="text-muted"><?php echo htmlspecialchars($internship['internship_description'] ?? 'No description provided'); ?></p>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <h5>Company Information</h5>
                <ul class="list-unstyled">
                    <li><strong>Company Name:</strong> <?php echo htmlspecialchars($internship['company_name']); ?></li>
                    <li><strong>Period:</strong> <?php echo date('M d, Y', strtotime($internship['start_date'])); ?> → <?php echo date('M d, Y', strtotime($internship['end_date'])); ?></li>
                    <li><strong>Duration:</strong> <?php echo $internship['duration_weeks']; ?> weeks</li>
                    <li><strong>Status:</strong> 
                        <span class="badge bg-<?php 
                            echo $internship['status'] === 'active' ? 'success' : 
                                 ($internship['status'] === 'completed' ? 'primary' : 
                                 ($internship['status'] === 'pending' ? 'warning' : 'danger'));
                        ?>">
                            <?php echo ucfirst($internship['status']); ?>
                        </span>
                    </li>
                </ul>
            </div>
            <div class="col-md-6">
                <h5>Supervisors</h5>
                <h6>Field Supervisor</h6>
                <ul class="list-unstyled">
                    <li><strong>Name:</strong> <?php echo htmlspecialchars($internship['supervisor_first'] . ' ' . $internship['supervisor_last']); ?></li>
                    <li><strong>Position:</strong> <?php echo htmlspecialchars($internship['supervisor_position']); ?></li>
                    <li><strong>Company:</strong> <?php echo htmlspecialchars($internship['supervisor_company']); ?></li>
                    <li><strong>Email:</strong> <?php echo htmlspecialchars($internship['supervisor_email']); ?></li>
                    <li><strong>Phone:</strong> <?php echo htmlspecialchars($internship['supervisor_phone'] ?? 'N/A'); ?></li>
                </ul>
                <h6>Academic Supervisor</h6>
                <ul class="list-unstyled">
                    <li><strong>Name:</strong> <?php echo htmlspecialchars($internship['teacher_first'] . ' ' . $internship['teacher_last']); ?></li>
                    <li><strong>Department:</strong> <?php echo htmlspecialchars($internship['teacher_dept']); ?></li>
                </ul>
            </div>
        </div>
        
    <div class="mt-4">
<!-- ... باقي الكود ... -->

<?php if (!empty($internship['agreement_file'])): ?>
    <div class="mt-4 p-3 bg-light rounded">
        <h5>Internship Agreement</h5>
        <p>
            <i class="fas fa-file-pdf text-danger"></i> 
            <strong><?php echo htmlspecialchars($internship['agreement_file_name']); ?></strong>
        </p>
        <a href="../uploads/agreements/<?php echo urlencode($internship['agreement_file']); ?>" 
           class="btn btn-success" 
           target="_blank"
           download="<?php echo htmlspecialchars($internship['agreement_file_name']); ?>">
            <i class="fas fa-download"></i> Download Agreement
        </a>
        <small class="d-block mt-2 text-muted">
            File size: <?php echo round(filesize(__DIR__ . '/../uploads/agreements/' . $internship['agreement_file']) / 1024, 1); ?> KB
        </small>
    </div>
<?php elseif ($internship['status'] === 'pending'): ?>
    <div class="mt-4 p-3 bg-warning bg-opacity-25 rounded">
        <p class="mb-0">
            <i class="fas fa-info-circle text-warning"></i> 
            No agreement file uploaded yet.
        </p>
    </div>
<?php endif; ?>

<div class="mt-4">
 
</div>
   <a href="my-internships.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to My Internships
    </a>
  
    <?php if ($internship['status'] === 'pending'): ?>
        <a href="edit-internship.php?id=<?php echo $internshipId; ?>" class="btn btn-warning">
            <i class="fas fa-edit"></i> Edit Application
        </a>
    <?php endif; ?>
    <?php if (in_array($internship['status'], ['pending', 'rejected', 'needs_revision'])): ?>
        <a href="delete-internship.php?id=<?php echo $internshipId; ?>" class="btn btn-danger">
            <i class="fas fa-trash"></i> Delete Internship
        </a>
    <?php endif; ?>
</div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>