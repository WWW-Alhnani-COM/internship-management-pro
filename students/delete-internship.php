<?php
define('APP_LOADED', true);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
checkUserType(['student']);

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Invalid internship ID.";
    header('Location: my-internships.php');
    exit();
}

$internshipId = (int)$_GET['id'];
$studentId = $_SESSION['user_id'];

// جلب تفاصيل التدريب للتحقق من الملكية والحالة
$stmt = $pdo->prepare("
    SELECT internship_id, status, company_name 
    FROM internships 
    WHERE internship_id = ? AND student_id = ?
");
$stmt->execute([$internshipId, $studentId]);
$internship = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$internship) {
    $_SESSION['error'] = "Internship not found or you don't have permission to delete it.";
    header('Location: my-internships.php');
    exit();
}

// التحقق من أن التدريب يمكن حذفه (يجب أن يكون في حالة pending أو rejected)
$deletableStatuses = ['pending', 'rejected', 'needs_revision'];
if (!in_array($internship['status'], $deletableStatuses)) {
    $_SESSION['error'] = "This internship cannot be deleted because it's in \"" . 
        ucfirst($internship['status']) . "\" status. Only internships with \"Pending\" or \"Rejected\" status can be deleted.";
    header('Location: internship-details.php?id=' . $internshipId);
    exit();
}

// تأكيد الحذف
if (!isset($_GET['confirm'])) {
    $pageTitle = "Delete Internship Confirmation";
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/sidebar.php';
    ?>
    
    <div class="page-header">
        <h1>Confirm Deletion</h1>
        <div class="breadcrumbs">
            <a href="dashboard.php">Dashboard</a> / 
            <a href="my-internships.php">My Internships</a> / 
            Delete Confirmation
        </div>
    </div>
    
    <div class="card">
        <div class="card-body text-center">
            <div class="mb-4">
                <i class="fas fa-exclamation-triangle fa-3x text-warning"></i>
            </div>
            <h3 class="mb-3">Are you sure you want to delete this internship?</h3>
            <p class="lead mb-4">
                Company: <strong><?php echo htmlspecialchars($internship['company_name']); ?></strong><br>
                Status: <span class="badge bg-<?php echo $internship['status'] === 'rejected' ? 'danger' : 'warning'; ?>">
                    <?php echo ucfirst($internship['status']); ?>
                </span>
            </p>
            <p class="text-muted">
                <strong>Note:</strong> This action cannot be undone. All related data will be permanently deleted.
            </p>
            
            <div class="mt-4 d-flex justify-content-center gap-3">
                <a href="my-internships.php" class="btn btn-secondary px-4">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <a href="delete-internship.php?id=<?php echo $internshipId; ?>&confirm=1" class="btn btn-danger px-4">
                    <i class="fas fa-trash"></i> Delete Permanently
                </a>
            </div>
        </div>
    </div>
    
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit();
}

// تنفيذ الحذف
try {
    $pdo->beginTransaction();
    
    // حذف التقارير المرتبطة
    $deleteReports = $pdo->prepare("DELETE FROM reports WHERE internship_id = ?");
    $deleteReports->execute([$internshipId]);
    
    // حذف المهام المرتبطة
    $deleteTasks = $pdo->prepare("DELETE FROM tasks WHERE internship_id = ?");
    $deleteTasks->execute([$internshipId]);
    
    // حذف التقييمات المرتبطة
    $deleteEvaluations = $pdo->prepare("DELETE FROM site_evaluations WHERE internship_id = ?");
    $deleteEvaluations->execute([$internshipId]);
    
    // حذف الزيارات المرتبطة
    $deleteVisits = $pdo->prepare("DELETE FROM site_visits WHERE internship_id = ?");
    $deleteVisits->execute([$internshipId]);
    
    // حذف الرسائل المرتبطة بالتدريب
    $deleteMessages = $pdo->prepare("DELETE FROM messages WHERE internship_id = ?");
    $deleteMessages->execute([$internshipId]);
    
    // حذف التدريب نفسه
    $deleteInternship = $pdo->prepare("DELETE FROM internships WHERE internship_id = ? AND student_id = ?");
    $deleteInternship->execute([$internshipId, $studentId]);
    
    $pdo->commit();
    
    $_SESSION['success'] = "Internship deleted successfully.";
    header('Location: my-internships.php');
    exit();
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error deleting internship: " . $e->getMessage());
    $_SESSION['error'] = "Error deleting internship: " . $e->getMessage();
    header('Location: internship-details.php?id=' . $internshipId);
    exit();
}