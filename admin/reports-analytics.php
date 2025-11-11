<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/header.php';

checkUserType(['admin']);

$pageTitle = "التقارير والإحصائيات";

// إحصائيات عامة
$stats_stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM users WHERE user_type = 'student') as total_students,
        (SELECT COUNT(*) FROM users WHERE user_type = 'teacher') as total_teachers,
        (SELECT COUNT(*) FROM users WHERE user_type = 'supervisor') as total_supervisors,
        (SELECT COUNT(*) FROM internships) as total_internships,
        (SELECT COUNT(*) FROM internships WHERE status = 'active') as active_internships,
        (SELECT COUNT(*) FROM internships WHERE status = 'completed') as completed_internships,
        (SELECT COUNT(*) FROM reports) as total_reports,
        (SELECT COUNT(*) FROM tasks) as total_tasks
");
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// إحصائيات حسب الشهر
$monthly_stats_stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count,
        SUM(CASE WHEN user_type = 'student' THEN 1 ELSE 0 END) as students,
        SUM(CASE WHEN user_type = 'teacher' THEN 1 ELSE 0 END) as teachers,
        SUM(CASE WHEN user_type = 'supervisor' THEN 1 ELSE 0 END) as supervisors
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6
");
$monthly_stats_stmt->execute();
$monthly_stats = $monthly_stats_stmt->fetchAll(PDO::FETCH_ASSOC);

// أفضل الطلاب تقييماً
$top_students_stmt = $pdo->prepare("
    SELECT 
        u.first_name, u.last_name, s.student_number, s.major,
        AVG(se.overall_score) as avg_score,
        COUNT(se.evaluation_id) as evaluation_count
    FROM site_evaluations se
    JOIN internships i ON se.internship_id = i.internship_id
    JOIN users u ON i.student_id = u.user_id
    JOIN students s ON i.student_id = s.student_id
    WHERE se.is_final = TRUE
    GROUP BY i.student_id
    HAVING evaluation_count >= 1
    ORDER BY avg_score DESC
    LIMIT 10
");
$top_students_stmt->execute();
$top_students = $top_students_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h1>التقارير والإحصائيات</h1>
</div>

<div class="stats-overview">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3>إجمالي المستخدمين</h3>
            <span class="stat-number"><?php echo $stats['total_users']; ?></span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="stat-info">
            <h3>الطلاب</h3>
            <span class="stat-number"><?php echo $stats['total_students']; ?></span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-chalkboard-teacher"></i>
        </div>
        <div class="stat-info">
            <h3>المشرفين الأكاديميين</h3>
            <span class="stat-number"><?php echo $stats['total_teachers']; ?></span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-briefcase"></i>
        </div>
        <div class="stat-info">
            <h3>المشرفين الميدانيين</h3>
            <span class="stat-number"><?php echo $stats['total_supervisors']; ?></span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-building"></i>
        </div>
        <div class="stat-info">
            <h3>التدريبات</h3>
            <span class="stat-number"><?php echo $stats['total_internships']; ?></span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-file-alt"></i>
        </div>
        <div class="stat-info">
            <h3>التقارير</h3>
            <span class="stat-number"><?php echo $stats['total_reports']; ?></span>
        </div>
    </div>
</div>

<div class="analytics-grid">
    <div class="analytics-card">
        <h3>التدريبات النشطة والمنتهية</h3>
        <div class="progress-bars">
            <div class="progress-item">
                <label>التدريبات النشطة</label>
                <div class="progress-bar">
                    <div class="progress" style="width: <?php echo ($stats['active_internships'] / max($stats['total_internships'], 1)) * 100; ?>%"></div>
                </div>
                <span><?php echo $stats['active_internships']; ?> (<?php echo round(($stats['active_internships'] / max($stats['total_internships'], 1)) * 100, 1); ?>%)</span>
            </div>
            
            <div class="progress-item">
                <label>التدريبات المنتهية</label>
                <div class="progress-bar">
                    <div class="progress" style="width: <?php echo ($stats['completed_internships'] / max($stats['total_internships'], 1)) * 100; ?>%"></div>
                </div>
                <span><?php echo $stats['completed_internships']; ?> (<?php echo round(($stats['completed_internships'] / max($stats['total_internships'], 1)) * 100, 1); ?>%)</span>
            </div>
        </div>
    </div>
    
    <div class="analytics-card">
        <h3>تسجيلات المستخدمين (آخر 6 أشهر)</h3>
        <div class="monthly-stats">
            <?php foreach(array_reverse($monthly_stats) as $month): ?>
            <div class="month-item">
                <span class="month-name"><?php echo date('M Y', strtotime($month['month'] . '-01')); ?></span>
                <div class="month-bars">
                    <div class="user-bar student-bar" style="width: <?php echo ($month['students'] / max($month['count'], 1)) * 100; ?>%" title="طلاب: <?php echo $month['students']; ?>"></div>
                    <div class="user-bar teacher-bar" style="width: <?php echo ($month['teachers'] / max($month['count'], 1)) * 100; ?>%" title="مشرفين: <?php echo $month['teachers']; ?>"></div>
                    <div class="user-bar supervisor-bar" style="width: <?php echo ($month['supervisors'] / max($month['count'], 1)) * 100; ?>%" title="مشرفين ميدانيين: <?php echo $month['supervisors']; ?>"></div>
                </div>
                <span class="month-total"><?php echo $month['count']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="top-students-section">
    <h2>أفضل الطلاب تقييماً</h2>
    <div class="top-students-list">
        <?php foreach($top_students as $index => $student): ?>
        <div class="student-rank-item">
            <div class="rank-number"><?php echo $index + 1; ?></div>
            <div class="student-info">
                <h4><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></h4>
                <p><?php echo $student['student_number']; ?> - <?php echo $student['major']; ?></p>
            </div>
            <div class="student-score">
                <span class="score"><?php echo number_format($student['avg_score'], 1); ?>/10</span>
                <span class="evaluations"><?php echo $student['evaluation_count']; ?> تقييم</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="reports-actions">
    <h2>تقارير مفصلة</h2>
    <div class="report-buttons">
        <button class="btn btn-primary" onclick="generateReport('students')">
            <i class="fas fa-download"></i> تقرير الطلاب
        </button>
        <button class="btn btn-primary" onclick="generateReport('internships')">
            <i class="fas fa-download"></i> تقرير التدريبات
        </button>
        <button class="btn btn-primary" onclick="generateReport('evaluations')">
            <i class="fas fa-download"></i> تقرير التقييمات
        </button>
        <button class="btn btn-primary" onclick="generateReport('reports')">
            <i class="fas fa-download"></i> تقرير التقارير
        </button>
    </div>
</div>

<style>
.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: #3498db;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.stat-info h3 {
    margin: 0 0 5px 0;
    font-size: 0.9rem;
    color: #7f8c8d;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #2c3e50;
}

.analytics-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.analytics-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.progress-bars {
    margin-top: 20px;
}

.progress-item {
    margin-bottom: 15px;
}

.progress-item label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #2c3e50;
}

.progress-bar {
    background: #ecf0f1;
    border-radius: 10px;
    height: 10px;
    margin: 5px 0;
    overflow: hidden;
}

.progress {
    background: #3498db;
    height: 100%;
    border-radius: 10px;
    transition: width 0.3s;
}

.monthly-stats {
    margin-top: 20px;
}

.month-item {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #ecf0f1;
}

.month-name {
    min-width: 80px;
    font-weight: 600;
    color: #2c3e50;
}

.month-bars {
    flex: 1;
    display: flex;
    height: 20px;
    background: #ecf0f1;
    border-radius: 4px;
    overflow: hidden;
}

.user-bar {
    height: 100%;
    transition: width 0.3s;
}

.student-bar { background: #3498db; }
.teacher-bar { background: #f39c12; }
.supervisor-bar { background: #27ae60; }

.month-total {
    min-width: 30px;
    text-align: center;
    font-weight: bold;
    color: #2c3e50;
}

.top-students-section {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.top-students-list {
    margin-top: 20px;
}

.student-rank-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border: 1px solid #ecf0f1;
    border-radius: 8px;
    margin-bottom: 10px;
    transition: transform 0.2s;
}

.student-rank-item:hover {
    transform: translateX(-5px);
    border-color: #3498db;
}

.rank-number {
    width: 40px;
    height: 40px;
    background: #3498db;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
}

.student-info {
    flex: 1;
}

.student-info h4 {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.student-info p {
    margin: 0;
    color: #7f8c8d;
    font-size: 0.9rem;
}

.student-score {
    text-align: center;
}

.score {
    display: block;
    font-size: 1.5rem;
    font-weight: bold;
    color: #27ae60;
}

.evaluations {
    font-size: 0.8rem;
    color: #7f8c8d;
}

.reports-actions {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.report-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin-top: 15px;
}
</style>

<script>
function generateReport(type) {
    // في التطبيق الحقيقي، هذا سيرسل طلب AJAX لإنشاء التقرير
    alert(`جارٍ إنشاء تقرير ${type}...`);
    // window.location.href = `generate-report.php?type=${type}`;
}
</script>

<?php include '../includes/footer.php'; ?>