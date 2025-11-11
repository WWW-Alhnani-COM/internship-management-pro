<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/header.php';

checkUserType(['teacher']);

$pageTitle = "التقييم الأكاديمي";

// جلب بيانات التدريب إذا كان هناك internship_id
$internship_id = $_GET['internship_id'] ?? null;
$internship = null;
$student = null;

if ($internship_id) {
    $stmt = $pdo->prepare("
        SELECT i.*, u.first_name, u.last_name, s.student_number, s.major, ss.company_name 
        FROM internships i 
        JOIN users u ON i.student_id = u.user_id 
        JOIN students s ON i.student_id = s.student_id 
        JOIN site_supervisors ss ON i.supervisor_id = ss.supervisor_id 
        WHERE i.internship_id = ? AND i.teacher_id = ?
    ");
    $stmt->execute([$internship_id, $_SESSION['user_id']]);
    $internship = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($internship) {
        $student = $internship;
    }
}

// جلب التقارير الخاصة بالتدريب
$reports = [];
if ($internship_id) {
    $reports_stmt = $pdo->prepare("
        SELECT * FROM reports 
        WHERE internship_id = ? 
        ORDER BY submission_date DESC
    ");
    $reports_stmt->execute([$internship_id]);
    $reports = $reports_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// إضافة تقييم أكاديمي
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_evaluation'])) {
    $report_id = $_POST['report_id'];
    $feedback = $_POST['feedback'];
    $grade = $_POST['grade'];
    
    try {
        $stmt = $pdo->prepare("UPDATE reports SET teacher_feedback = ?, grade = ?, teacher_feedback_date = NOW(), status = 'approved' WHERE report_id = ?");
        $stmt->execute([$feedback, $grade, $report_id]);
        
        $success = "تم إضافة التقييم بنجاح";
    } catch(PDOException $e) {
        $error = "حدث خطأ: " . $e->getMessage();
    }
}
?>

<div class="page-header">
    <h1>التقييم الأكاديمي</h1>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (!$internship_id || !$internship): ?>
<div class="selection-panel">
    <h3>اختر طالباً للتقييم</h3>
    <p>يرجى اختيار طالب من صفحة <a href="student-tracking.php">متابعة الطلاب</a> لتقييم تقاريره.</p>
</div>
<?php else: ?>

<div class="student-info-card">
    <h3>معلومات الطالب</h3>
    <div class="info-grid">
        <div class="info-item">
            <label>اسم الطالب:</label>
            <span><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></span>
        </div>
        <div class="info-item">
            <label>الرقم الجامعي:</label>
            <span><?php echo $student['student_number']; ?></span>
        </div>
        <div class="info-item">
            <label>التخصص:</label>
            <span><?php echo $student['major']; ?></span>
        </div>
        <div class="info-item">
            <label>الشركة:</label>
            <span><?php echo $student['company_name']; ?></span>
        </div>
        <div class="info-item">
            <label>فترة التدريب:</label>
            <span><?php echo $student['start_date'] . ' إلى ' . $student['end_date']; ?></span>
        </div>
    </div>
</div>

<div class="reports-evaluation">
    <h3>التقارير المقدمة</h3>
    
    <?php if (empty($reports)): ?>
    <div class="empty-state">
        <p>لا توجد تقارير مرفوعة حتى الآن</p>
    </div>
    <?php else: ?>
        <?php foreach($reports as $report): ?>
        <div class="report-evaluation-item">
            <div class="report-header">
                <h4><?php echo $report['report_title']; ?></h4>
                <div class="report-meta">
                    <span class="report-type"><?php echo $report['report_type']; ?></span>
                    <span class="report-date"><?php echo $report['submission_date']; ?></span>
                    <span class="report-status <?php echo $report['status']; ?>"><?php echo $report['status']; ?></span>
                </div>
            </div>
            
            <?php if ($report['report_description']): ?>
            <p class="report-description"><?php echo $report['report_description']; ?></p>
            <?php endif; ?>
            
            <?php if ($report['report_file_name']): ?>
            <div class="report-file">
                <a href="../uploads/reports/<?php echo $report['report_file_name']; ?>" target="_blank" class="btn btn-sm btn-outline">
                    <i class="fas fa-download"></i> تحميل التقرير
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ($report['teacher_feedback']): ?>
            <div class="existing-feedback">
                <h5>تقييم سابق:</h5>
                <div class="feedback-content">
                    <p><strong>الدرجة:</strong> <?php echo $report['grade']; ?>/100</p>
                    <p><strong>التغذية الراجعة:</strong> <?php echo $report['teacher_feedback']; ?></p>
                    <p><strong>تاريخ التقييم:</strong> <?php echo $report['teacher_feedback_date']; ?></p>
                </div>
            </div>
            <?php else: ?>
            <div class="evaluation-form">
                <h5>إضافة تقييم جديد</h5>
                <form method="POST">
                    <input type="hidden" name="submit_evaluation" value="1">
                    <input type="hidden" name="report_id" value="<?php echo $report['report_id']; ?>">
                    
                    <div class="form-group">
                        <label for="grade_<?php echo $report['report_id']; ?>">الدرجة (من 100)</label>
                        <input type="number" id="grade_<?php echo $report['report_id']; ?>" name="grade" min="0" max="100" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="feedback_<?php echo $report['report_id']; ?>">التغذية الراجعة</label>
                        <textarea id="feedback_<?php echo $report['report_id']; ?>" name="feedback" rows="4" required placeholder="اكتب ملاحظاتك حول التقرير..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">حفظ التقييم</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php endif; ?>

<style>
.selection-panel {
    background: white;
    padding: 40px;
    text-align: center;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.student-info-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #ecf0f1;
}

.info-item label {
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

.reports-evaluation {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.report-evaluation-item {
    border: 1px solid #ecf0f1;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.report-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 10px;
}

.report-meta {
    display: flex;
    gap: 15px;
    align-items: center;
}

.report-type {
    background: #e8f4fd;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
}

.report-date {
    color: #7f8c8d;
    font-size: 0.9rem;
}

.report-description {
    color: #5a6c7d;
    margin-bottom: 15px;
}

.report-file {
    margin-bottom: 15px;
}

.btn-outline {
    background: transparent;
    border: 1px solid #3498db;
    color: #3498db;
}

.btn-outline:hover {
    background: #3498db;
    color: white;
}

.existing-feedback {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    border-right: 3px solid #27ae60;
}

.evaluation-form {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 4px;
    border-right: 3px solid #3498db;
}

.evaluation-form h5 {
    margin-bottom: 15px;
    color: #2c3e50;
}
</style>

<?php include '../includes/footer.php'; ?>