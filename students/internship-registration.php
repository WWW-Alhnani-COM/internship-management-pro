<?php
define('APP_LOADED', true);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
checkUserType(['student']);

require_once __DIR__ . '/../models/User.php';

$pageTitle = "Register New Internship";

// الحصول على معلومات الطالب
$studentId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// إذا لم يكن هناك سجل للطالب في جدول students، قم بإنشائه
if (!$student) {
    $createStudentStmt = $pdo->prepare("
        INSERT INTO students (student_id, student_number, major, academic_year)
        VALUES (?, NULL, ?, ?)
    ");
    if ($createStudentStmt->execute([$studentId, '', ''])) {
        // re-fetch after creation
        $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$message = '';
$messageType = '';

// الحصول على قائمة المشرفين الأكاديميين
$teachersStmt = $pdo->prepare("
    SELECT u.user_id, u.first_name, u.last_name, t.specialization, t.department
    FROM users u
    JOIN teachers t ON u.user_id = t.teacher_id
    WHERE u.is_active = 1
");
$teachersStmt->execute();
$academicSupervisors = $teachersStmt->fetchAll(PDO::FETCH_ASSOC);

// الحصول على قائمة المشرفين الميدانيين
$supervisorsStmt = $pdo->prepare("
    SELECT u.user_id, u.first_name, u.last_name, ss.company_name, ss.position, ss.supervisor_id
    FROM users u
    JOIN site_supervisors ss ON u.user_id = ss.supervisor_id
    WHERE u.is_active = 1
");
$supervisorsStmt->execute();
$fieldSupervisors = $supervisorsStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من وجود جميع الحقول المطلوبة
    if (empty($_POST['field_supervisor_id']) || $_POST['field_supervisor_id'] == '') {
        $message = 'يجب اختيار مشرف ميداني';
        $messageType = 'danger';
    } elseif (empty($_POST['academic_supervisor_id']) || $_POST['academic_supervisor_id'] == '') {
        $message = 'يجب اختيار مشرف أكاديمي';
        $messageType = 'danger';
    } else {
        // معالجة نموذج تسجيل التدريب
        $companyName = trim($_POST['company_name']);
        $supervisorName = trim($_POST['supervisor_name']);
        $supervisorEmail = trim($_POST['supervisor_email']);
        $supervisorPhone = trim($_POST['supervisor_phone'] ?? '');
        $supervisorPosition = trim($_POST['supervisor_position'] ?? '');
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $durationWeeks = $_POST['duration_weeks'];
        $internshipTitle = trim($_POST['internship_title']);
        $internshipDescription = trim($_POST['internship_description']);
        $academicSupervisorId = $_POST['academic_supervisor_id'];
        $fieldSupervisorId = $_POST['field_supervisor_id'];
        
        try {
            $pdo->beginTransaction();
            
            // التأكد من وجود السجلات في الجداول الفرعية
            $userModel = new User($pdo);
            $userModel->ensureStudentRecord($studentId);
            $userModel->ensureSupervisorRecord($fieldSupervisorId);
            
            // التحقق من صحة المشرفين
            $checkSupervisorStmt = $pdo->prepare("SELECT supervisor_id FROM site_supervisors WHERE supervisor_id = ?");
            $checkSupervisorStmt->execute([$fieldSupervisorId]);
            if ($checkSupervisorStmt->rowCount() == 0) {
                throw new Exception("المشرف الميداني المحدد غير موجود في النظام");
            }
            
            $checkTeacherStmt = $pdo->prepare("SELECT teacher_id FROM teachers WHERE teacher_id = ?");
            $checkTeacherStmt->execute([$academicSupervisorId]);
            if ($checkTeacherStmt->rowCount() == 0) {
                throw new Exception("المشرف الأكاديمي المحدد غير موجود في النظام");
            }
            
            // التحقق من تاريخ البدء والانتهاء
            if ($endDate <= $startDate) {
                throw new Exception("تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء");
            }
            
            // إذا لم يتم تحديد عدد الأسابيع، احسبه من التاريخين
            if (empty($durationWeeks) || $durationWeeks <= 0) {
                $start = new DateTime($startDate);
                $end = new DateTime($endDate);
                $interval = $start->diff($end);
                $durationWeeks = max(1, floor($interval->days / 7));
            }
            
            // إدخال بيانات التدريب
            $stmt = $pdo->prepare("
                INSERT INTO internships (
                    student_id, company_name, supervisor_id, teacher_id, 
                    start_date, end_date, duration_weeks, 
                    internship_title, internship_description, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([
                $studentId, 
                $companyName, 
                $fieldSupervisorId,
                $academicSupervisorId,
                $startDate,
                $endDate,
                $durationWeeks,
                $internshipTitle,
                $internshipDescription
            ]);
            
            $internshipId = $pdo->lastInsertId();
            
            // تحديث بيانات المشرف الميداني إذا لزم الأمر
            $updateSupervisorStmt = $pdo->prepare("
                UPDATE site_supervisors 
                SET company_name = COALESCE(NULLIF(company_name, ''), ?), 
                    position = COALESCE(NULLIF(position, ''), ?)
                WHERE supervisor_id = ?
            ");
            $updateSupervisorStmt->execute([$companyName, $supervisorPosition, $fieldSupervisorId]);
            
            // تحديث بيانات المستخدم للمشرف الميداني (البريد والهاتف)
            $updateUserStmt = $pdo->prepare("
                UPDATE users 
                SET email = COALESCE(NULLIF(email, ''), ?), 
                    phone = COALESCE(NULLIF(phone, ''), ?)
                WHERE user_id = ?
            ");
            $updateUserStmt->execute([$supervisorEmail, $supervisorPhone, $fieldSupervisorId]);
            
            // معالجة تحميل اتفاقية التدريب إذا تم تحميلها
            if (isset($_FILES['agreement_file']) && $_FILES['agreement_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/agreements/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $allowedTypes = ['application/pdf'];
                $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                $fileMimeType = finfo_file($fileInfo, $_FILES['agreement_file']['tmp_name']);
                finfo_close($fileInfo);
                
                if (!in_array($fileMimeType, $allowedTypes)) {
                    throw new Exception('يجب تحميل ملف PDF فقط');
                }
                
                if ($_FILES['agreement_file']['size'] > 5 * 1024 * 1024) { // 5MB
                    throw new Exception('حجم الملف كبير جداً. الحد الأقصى هو 5MB');
                }
                
                $originalFileName = basename($_FILES['agreement_file']['name']);
                $sanitizedFileName = preg_replace("/[^a-zA-Z0-9\.\-_]/", "", $originalFileName);
                $fileName = 'agreement_' . $internshipId . '_' . time() . '_' . $sanitizedFileName;
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['agreement_file']['tmp_name'], $filePath)) {
                    // حفظ معلومات الملف في قاعدة البيانات
                    $fileStmt = $pdo->prepare("
                        UPDATE internships 
                        SET agreement_file = ?, agreement_file_name = ?
                        WHERE internship_id = ?
                    ");
                    $fileStmt->execute([$fileName, $originalFileName, $internshipId]);
                } else {
                    throw new Exception('فشل في تحميل ملف الاتفاقية');
                }
            }
            
            $pdo->commit();
            
            $message = 'تم تسجيل التدريب بنجاح وسيتم مراجعته من قبل الإدارة';
            $messageType = 'success';
            
            // إعادة تعيين النموذج
            $_POST = [];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = $e->getMessage();
            $messageType = 'danger';
        }
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="page-header">
    <h1>Register New Internship</h1>
    <div class="breadcrumbs">
        <a href="dashboard.php">Dashboard</a> / <a href="my-internships.php">My Internships</a> / Register New Internship
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="mb-0">Internship Details</h3>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="company_name" class="form-label">Company/Organization Name</label>
                    <input type="text" id="company_name" name="company_name" class="form-control" value="<?php echo $_POST['company_name'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="internship_title" class="form-label">Internship Title</label>
                    <input type="text" id="internship_title" name="internship_title" class="form-control" value="<?php echo $_POST['internship_title'] ?? ''; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $_POST['start_date'] ?? date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $_POST['end_date'] ?? ''; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="duration_weeks" class="form-label">Internship Duration (Weeks)</label>
                    <input type="number" id="duration_weeks" name="duration_weeks" class="form-control" value="<?php echo $_POST['duration_weeks'] ?? ''; ?>" min="1" max="52">
                    <small class="form-text text-muted">If left blank, it will be calculated based on start and end dates</small>
                </div>
                
                <div class="form-group">
                    <label for="academic_supervisor_id" class="form-label">Academic Supervisor</label>
                    <select id="academic_supervisor_id" name="academic_supervisor_id" class="form-control" required>
                        <option value="">Select Academic Supervisor</option>
                        <?php foreach ($academicSupervisors as $supervisor): ?>
                            <option value="<?php echo $supervisor['user_id']; ?>" <?php echo (isset($_POST['academic_supervisor_id']) && $_POST['academic_supervisor_id'] == $supervisor['user_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($supervisor['first_name'] . ' ' . $supervisor['last_name']); ?> - 
                                <?php echo htmlspecialchars($supervisor['department']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="field_supervisor_id" class="form-label">Field Supervisor</label>
                <select id="field_supervisor_id" name="field_supervisor_id" class="form-control" required>
                    <option value="">Select Field Supervisor</option>
                    <?php foreach ($fieldSupervisors as $supervisor): ?>
                        <option value="<?php echo $supervisor['supervisor_id']; ?>" <?php echo (isset($_POST['field_supervisor_id']) && $_POST['field_supervisor_id'] == $supervisor['supervisor_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($supervisor['first_name'] . ' ' . $supervisor['last_name']); ?> - 
                            <?php echo htmlspecialchars($supervisor['company_name'] ?? 'No company'); ?> 
                            <?php if (!empty($supervisor['position'])): ?>(<?php echo htmlspecialchars($supervisor['position']); ?>)<?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="form-text text-muted">If your supervisor is not in the list, please contact the administration to add them first</small>
            </div>
            
            <div class="form-group">
                <label for="internship_description" class="form-label">Internship Description</label>
                <textarea id="internship_description" name="internship_description" class="form-control" rows="4" required><?php echo $_POST['internship_description'] ?? ''; ?></textarea>
                <small class="form-text text-muted">Describe in detail the tasks and responsibilities you will perform during the internship</small>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h4 class="mb-0">Field Supervisor Details</h4>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="supervisor_name" class="form-label">Supervisor Name</label>
                            <input type="text" id="supervisor_name" name="supervisor_name" class="form-control" value="<?php echo $_POST['supervisor_name'] ?? ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="supervisor_email" class="form-label">Email Address</label>
                            <input type="email" id="supervisor_email" name="supervisor_email" class="form-control" value="<?php echo $_POST['supervisor_email'] ?? ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="supervisor_phone" class="form-label">Phone Number</label>
                            <input type="tel" id="supervisor_phone" name="supervisor_phone" class="form-control" value="<?php echo $_POST['supervisor_phone'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="supervisor_position" class="form-label">Job Position</label>
                            <input type="text" id="supervisor_position" name="supervisor_position" class="form-control" value="<?php echo $_POST['supervisor_position'] ?? ''; ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h4 class="mb-0">Upload Internship Agreement</h4>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="agreement_file" class="form-label">Internship Agreement (PDF)</label>
                        <input type="file" id="agreement_file" name="agreement_file" class="form-control" accept=".pdf">
                        <small class="form-text text-muted">Please upload a signed internship agreement in PDF format (Max 5MB)</small>
                    </div>
                </div>
            </div>
            
            <div class="form-group mt-4">
                <div class="form-check">
                    <input type="checkbox" id="terms" name="terms" class="form-check-input" required>
                    <label for="terms" class="form-check-label">
                        I agree to all terms and conditions of the cooperative training system
                    </label>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-between">
                <a href="my-internships.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to My Internships
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Application
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // حساب عدد الأسابيع تلقائياً عند تغيير تاريخ البدء أو الانتهاء
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        const durationWeeks = document.getElementById('duration_weeks');
        
        function calculateDuration() {
            if (startDate.value && endDate.value) {
                const start = new Date(startDate.value);
                const end = new Date(endDate.value);
                
                if (end > start) {
                    const diffTime = Math.abs(end - start);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    const weeks = Math.ceil(diffDays / 7);
                    durationWeeks.value = weeks;
                } else {
                    alert('End date must be after start date');
                    endDate.value = '';
                }
            }
        }
        
        startDate.addEventListener('change', calculateDuration);
        endDate.addEventListener('change', calculateDuration);
        
        // التحقق من حجم الملف
        document.getElementById('agreement_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) { // 5MB
                    alert('File size is too large. Maximum size is 5MB');
                    e.target.value = '';
                }
                
                // التحقق من نوع الملف
                const allowedExtensions = /(\.pdf)$/i;
                if (!allowedExtensions.exec(file.name)) {
                    alert('Only PDF files are allowed');
                    e.target.value = '';
                }
            }
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>