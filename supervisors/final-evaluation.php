<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/header.php';

checkUserType(['supervisor']);

$pageTitle = "التقييم النهائي";

// جلب الطلاب النشطين
$students_stmt = $pdo->prepare("
    SELECT i.internship_id, u.user_id, u.first_name, u.last_name, s.student_number 
    FROM internships i 
    JOIN users u ON i.student_id = u.user_id 
    JOIN students s ON i.student_id = s.student_id 
    WHERE i.supervisor_id = ? AND i.status = 'active'
");
$students_stmt->execute([$_SESSION['user_id']]);
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

// إضافة تقييم جديد
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_evaluation'])) {
    $internship_id = $_POST['internship_id'];
    $professionalism_score = $_POST['professionalism_score'];
    $technical_skills_score = $_POST['technical_skills_score'];
    $communication_score = $_POST['communication_score'];
    $teamwork_score = $_POST['teamwork_score'];
    $problem_solving_score = $_POST['problem_solving_score'];
    $attendance_score = $_POST['attendance_score'];
    $strengths = $_POST['strengths'];
    $areas_for_improvement = $_POST['areas_for_improvement'];
    $final_comments = $_POST['final_comments'];
    $recommendation = $_POST['recommendation'];
    $is_final = isset($_POST['is_final']) ? 1 : 0;
    
    // حساب المتوسط
    $overall_score = (
        $professionalism_score + 
        $technical_skills_score + 
        $communication_score + 
        $teamwork_score + 
        $problem_solving_score + 
        $attendance_score
    ) / 6;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO site_evaluations 
            (internship_id, supervisor_id, professionalism_score, technical_skills_score, communication_score, teamwork_score, problem_solving_score, attendance_score, overall_score, strengths, areas_for_improvement, final_comments, recommendation, is_final) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $internship_id, $_SESSION['user_id'], $professionalism_score, $technical_skills_score, 
            $communication_score, $teamwork_score, $problem_solving_score, $attendance_score, 
            $overall_score, $strengths, $areas_for_improvement, $final_comments, $recommendation, $is_final
        ]);
        
        $success = "تم إضافة التقييم بنجاح";
        
        // إذا كان التقييم نهائياً، تحديث حالة التدريب
        if ($is_final) {
            $update_stmt = $pdo->prepare("UPDATE internships SET status = 'completed' WHERE internship_id = ?");
            $update_stmt->execute([$internship_id]);
        }
        
        header("Location: final-evaluation.php");
        exit();
    } catch(PDOException $e) {
        $error = "حدث خطأ: " . $e->getMessage();
    }
}
?>

<div class="page-header">
    <h1>التقييم النهائي</h1>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="evaluation-form-container">
    <form method="POST" class="evaluation-form">
        <input type="hidden" name="submit_evaluation" value="1">
        
        <div class="form-group">
            <label for="internship_id">الطالب</label>
            <select id="internship_id" name="internship_id" required>
                <option value="">اختر الطالب</option>
                <?php foreach($students as $student): ?>
                <option value="<?php echo $student['internship_id']; ?>">
                    <?php echo $student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_number'] . ')'; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="scores-section">
            <h3>التقييم بالدرجات (من 1 إلى 10)</h3>
            
            <div class="scores-grid">
                <div class="score-input">
                    <label for="professionalism_score">المهنية</label>
                    <input type="number" id="professionalism_score" name="professionalism_score" min="1" max="10" required>
                </div>
                
                <div class="score-input">
                    <label for="technical_skills_score">المهارات التقنية</label>
                    <input type="number" id="technical_skills_score" name="technical_skills_score" min="1" max="10" required>
                </div>
                
                <div class="score-input">
                    <label for="communication_score">التواصل</label>
                    <input type="number" id="communication_score" name="communication_score" min="1" max="10" required>
                </div>
                
                <div class="score-input">
                    <label for="teamwork_score">العمل الجماعي</label>
                    <input type="number" id="teamwork_score" name="teamwork_score" min="1" max="10" required>
                </div>
                
                <div class="score-input">
                    <label for="problem_solving_score">حل المشكلات</label>
                    <input type="number" id="problem_solving_score" name="problem_solving_score" min="1" max="10" required>
                </div>
                
                <div class="score-input">
                    <label for="attendance_score">الانتظام</label>
                    <input type="number" id="attendance_score" name="attendance_score" min="1" max="10" required>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="strengths">نقاط القوة</label>
            <textarea id="strengths" name="strengths" rows="3" placeholder="اذكر نقاط القوة الرئيسية للطالب..."></textarea>
        </div>
        
        <div class="form-group">
            <label for="areas_for_improvement">مجالات التحسين</label>
            <textarea id="areas_for_improvement" name="areas_for_improvement" rows="3" placeholder="اذكر المجالات التي يحتاج الطالب للتحسين فيها..."></textarea>
        </div>
        
        <div class="form-group">
            <label for="final_comments">ملاحظات عامة</label>
            <textarea id="final_comments" name="final_comments" rows="4" placeholder="ملاحظات إضافية حول أداء الطالب..."></textarea>
        </div>
        
        <div class="form-group">
            <label for="recommendation">التوصية</label>
            <select id="recommendation" name="recommendation" required>
                <option value="excellent">ممتاز</option>
                <option value="good">جيد</option>
                <option value="satisfactory">مقبول</option>
                <option value="needs_improvement">يحتاج تحسين</option>
            </select>
        </div>
        
        <div class="form-group checkbox-group">
            <input type="checkbox" id="is_final" name="is_final" value="1">
            <label for="is_final">هذا التقييم النهائي (سيتم إغلاق التدريب بعد الإرسال)</label>
        </div>
        
        <button type="submit" class="btn btn-primary btn-large">إرسال التقييم</button>
    </form>
</div>

<style>
.evaluation-form-container {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.scores-section {
    margin: 25px 0;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.scores-section h3 {
    margin-bottom: 20px;
    color: #2c3e50;
}

.scores-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.score-input {
    display: flex;
    flex-direction: column;
}

.score-input label {
    margin-bottom: 5px;
    font-weight: 600;
}

.score-input input {
    padding: 10px;
    border: 1px solid #bdc3c7;
    border-radius: 4px;
    font-size: 1rem;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.checkbox-group input[type="checkbox"] {
    width: auto;
}

.btn-large {
    padding: 15px 30px;
    font-size: 1.1rem;
}
</style>

<script>
// حساب المتوسط تلقائياً
const scoreInputs = document.querySelectorAll('input[type="number"]');
scoreInputs.forEach(input => {
    input.addEventListener('input', calculateAverage);
});

function calculateAverage() {
    const scores = Array.from(scoreInputs).map(input => parseFloat(input.value) || 0);
    const average = scores.reduce((sum, score) => sum + score, 0) / scores.length;
    
    // يمكن عرض المتوسط للمستخدم إذا أردت
    console.log('المتوسط:', average.toFixed(2));
}
</script>

<?php include '../includes/footer.php'; ?>