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

// التحقق من الملكية والصلاحية (يجب أن يكون الحالة pending)
$checkStmt = $pdo->prepare("SELECT * FROM internships WHERE internship_id = ? AND student_id = ? AND status = 'pending'");
$checkStmt->execute([$internshipId, $studentId]);
$internship = $checkStmt->fetch(PDO::FETCH_ASSOC);

if (!$internship) {
    $_SESSION['error'] = "You can only edit pending internships.";
    header('Location: my-internships.php');
    exit();
}

$message = '';
$messageType = '';

// الحصول على المشرفين
$teachersStmt = $pdo->prepare("
    SELECT u.user_id, u.first_name, u.last_name, t.department
    FROM users u
    JOIN teachers t ON u.user_id = t.teacher_id
    WHERE u.is_active = 1
");
$teachersStmt->execute();
$academicSupervisors = $teachersStmt->fetchAll(PDO::FETCH_ASSOC);

$supervisorsStmt = $pdo->prepare("
    SELECT u.user_id, u.first_name, u.last_name, ss.company_name, ss.position
    FROM users u
    JOIN site_supervisors ss ON u.user_id = ss.supervisor_id
    WHERE u.is_active = 1
");
$supervisorsStmt->execute();
$fieldSupervisors = $supervisorsStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // معالجة التعديل
    $companyName = trim($_POST['company_name']);
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $durationWeeks = $_POST['duration_weeks'];
    $internshipTitle = trim($_POST['internship_title']);
    $internshipDescription = trim($_POST['internship_description']);
    $academicSupervisorId = $_POST['academic_supervisor_id'];
    $fieldSupervisorId = $_POST['field_supervisor_id'];
    
    try {
        $updateStmt = $pdo->prepare("
            UPDATE internships 
            SET company_name = ?, supervisor_id = ?, teacher_id = ?, 
                start_date = ?, end_date = ?, duration_weeks = ?, 
                internship_title = ?, internship_description = ?
            WHERE internship_id = ? AND student_id = ?
        ");
        $updateStmt->execute([
            $companyName, $fieldSupervisorId, $academicSupervisorId,
            $startDate, $endDate, $durationWeeks,
            $internshipTitle, $internshipDescription,
            $internshipId, $studentId
        ]);
        
        $message = 'Internship updated successfully.';
        $messageType = 'success';
        
        // إعادة التوجيه بعد النجاح
        header("Location: internship-details.php?id=$internshipId");
        exit();
        
    } catch (PDOException $e) {
        $message = 'Error updating internship: ' . $e->getMessage();
        $messageType = 'danger';
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="page-header">
    <h1>Edit Internship Application</h1>
    <div class="breadcrumbs">
        <a href="dashboard.php">Dashboard</a> / 
        <a href="my-internships.php">My Internships</a> / 
        Edit
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Company Name</label>
                    <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($internship['company_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Internship Title</label>
                    <input type="text" name="internship_title" class="form-control" value="<?php echo htmlspecialchars($internship['internship_title']); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $internship['start_date']; ?>" required>
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $internship['end_date']; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Duration (Weeks)</label>
                    <input type="number" name="duration_weeks" class="form-control" value="<?php echo $internship['duration_weeks']; ?>" min="1" max="52" required>
                </div>
                <div class="form-group">
                    <label>Academic Supervisor</label>
                    <select name="academic_supervisor_id" class="form-control" required>
                        <option value="">Select...</option>
                        <?php foreach ($academicSupervisors as $sup): ?>
                            <option value="<?php echo $sup['user_id']; ?>" <?php echo $sup['user_id'] == $internship['teacher_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sup['first_name'] . ' ' . $sup['last_name'] . ' (' . $sup['department'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Field Supervisor</label>
                <select name="field_supervisor_id" class="form-control" required>
                    <option value="">Select...</option>
                    <?php foreach ($fieldSupervisors as $sup): ?>
                        <option value="<?php echo $sup['user_id']; ?>" <?php echo $sup['user_id'] == $internship['supervisor_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sup['first_name'] . ' ' . $sup['last_name'] . ' - ' . $sup['company_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="internship_description" class="form-control" rows="4" required><?php echo htmlspecialchars($internship['internship_description']); ?></textarea>
            </div>
            
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="internship-details.php?id=<?php echo $internshipId; ?>" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>