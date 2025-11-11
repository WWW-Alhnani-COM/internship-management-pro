<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/header.php';

checkUserType(['teacher']);

$pageTitle = "متابعة الطلاب";

// جلب الطلاب تحت الإشراف
$students_stmt = $pdo->prepare("
    SELECT i.*, u.first_name, u.last_name, s.student_number, s.major, s.academic_year 
    FROM internships i 
    JOIN users u ON i.student_id = u.user_id 
    JOIN students s ON i.student_id = s.student_id 
    WHERE i.teacher_id = ? 
    ORDER BY i.status, u.first_name
");
$students_stmt->execute([$_SESSION['user_id']]);
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب إحصائيات سريعة
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_students,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_students,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_students,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_students
    FROM internships 
    WHERE teacher_id = ?
");
$stats_stmt->execute([$_SESSION['user_id']]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h1>متابعة الطلاب</h1>
</div>

<div class="stats-cards">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3>إجمالي الطلاب</h3>
            <span class="stat-number"><?php echo $stats['total_students']; ?></span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-play-circle"></i>
        </div>
        <div class="stat-info">
            <h3>طلاب نشطين</h3>
            <span class="stat-number"><?php echo $stats['active_students']; ?></span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <h3>منتهي التدريب</h3>
            <span class="stat-number"><?php echo $stats['completed_students']; ?></span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
            <h3>في انتظار الموافقة</h3>
            <span class="stat-number"><?php echo $stats['pending_students']; ?></span>
        </div>
    </div>
</div>

<div class="students-table-container">
    <h2>قائمة الطلاب</h2>
    
    <div class="table-responsive">
        <table class="students-table">
            <thead>
                <tr>
                    <th>اسم الطالب</th>
                    <th>الرقم الجامعي</th>
                    <th>التخصص</th>
                    <th>الشركة</th>
                    <th>حالة التدريب</th>
                    <th>تاريخ البدء</th>
                    <th>تاريخ الانتهاء</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($students as $student): ?>
                <tr>
                    <td>
                        <strong><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></strong>
                    </td>
                    <td><?php echo $student['student_number']; ?></td>
                    <td><?php echo $student['major']; ?></td>
                    <td><?php echo $student['company_name']; ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $student['status']; ?>">
                            <?php echo $student['status']; ?>
                        </span>
                    </td>
                    <td><?php echo $student['start_date']; ?></td>
                    <td><?php echo $student['end_date']; ?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="student-details.php?id=<?php echo $student['internship_id']; ?>" class="btn btn-sm btn-info" title="عرض التفاصيل">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="../communication/messaging.php?student_id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-primary" title="إرسال رسالة">
                                <i class="fas fa-envelope"></i>
                            </a>
                            <a href="academic-evaluation.php?internship_id=<?php echo $student['internship_id']; ?>" class="btn btn-sm btn-success" title="تقييم أكاديمي">
                                <i class="fas fa-clipboard-check"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.stats-cards {
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

.students-table-container {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table-responsive {
    overflow-x: auto;
}

.students-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.students-table th,
.students-table td {
    padding: 12px 15px;
    text-align: right;
    border-bottom: 1px solid #ecf0f1;
}

.students-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
}

.status-badge {
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: bold;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-active { background: #d1ecf1; color: #0c5460; }
.status-completed { background: #d4edda; color: #155724; }
.status-approved { background: #d4edda; color: #155724; }
.status-rejected { background: #f8d7da; color: #721c24; }

.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-sm {
    padding: 6px 10px;
    font-size: 0.8rem;
}

.btn-info { background: #17a2b8; color: white; }
.btn-info:hover { background: #138496; }
</style>

<?php include '../includes/footer.php'; ?>