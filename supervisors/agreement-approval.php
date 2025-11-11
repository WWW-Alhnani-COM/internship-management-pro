<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/header.php';

checkUserType(['supervisor']);

$pageTitle = "اعتماد اتفاقيات التدريب";

// جلب الطلبات المعلقة
$pending_stmt = $pdo->prepare("
    SELECT i.*, u.first_name, u.last_name, s.student_number, s.major 
    FROM internships i 
    JOIN users u ON i.student_id = u.user_id 
    JOIN students s ON i.student_id = s.student_id 
    WHERE i.supervisor_id = ? AND i.status = 'pending'
");
$pending_stmt->execute([$_SESSION['user_id']]);
$pending_requests = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);

// معالجة الموافقة أو الرفض
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $internship_id = $_POST['internship_id'];
    $action = $_POST['action'];
    
    try {
        $new_status = $action == 'approve' ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("UPDATE internships SET status = ? WHERE internship_id = ? AND supervisor_id = ?");
        $stmt->execute([$new_status, $internship_id, $_SESSION['user_id']]);
        
        $success = "تم $action الطلب بنجاح";
        
        // إعادة تحميل البيانات
        header("Location: agreement-approval.php");
        exit();
    } catch(PDOException $e) {
        $error = "حدث خطأ: " . $e->getMessage();
    }
}
?>

<div class="page-header">
    <h1>اعتماد اتفاقيات التدريب</h1>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="requests-list">
    <h2>طلبات الانتظار</h2>
    
    <?php if (empty($pending_requests)): ?>
    <div class="empty-state">
        <p>لا توجد طلبات في انتظار الموافقة</p>
    </div>
    <?php else: ?>
        <?php foreach($pending_requests as $request): ?>
        <div class="request-item">
            <div class="request-header">
                <h3><?php echo $request['first_name'] . ' ' . $request['last_name']; ?></h3>
                <span class="student-info">رقم الطالب: <?php echo $request['student_number']; ?> | التخصص: <?php echo $request['major']; ?></span>
            </div>
            
            <div class="request-details">
                <div class="detail-row">
                    <span><strong>الشركة:</strong> <?php echo $request['company_name']; ?></span>
                    <span><strong>عنوان التدريب:</strong> <?php echo $request['internship_title']; ?></span>
                </div>
                
                <div class="detail-row">
                    <span><strong>تاريخ البدء:</strong> <?php echo $request['start_date']; ?></span>
                    <span><strong>تاريخ الانتهاء:</strong> <?php echo $request['end_date']; ?></span>
                    <span><strong>المدة:</strong> <?php echo $request['duration_weeks']; ?> أسابيع</span>
                </div>
                
                <?php if ($request['internship_description']): ?>
                <div class="detail-row">
                    <strong>وصف التدريب:</strong>
                    <p><?php echo $request['internship_description']; ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="request-actions">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="internship_id" value="<?php echo $request['internship_id']; ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-success">موافقة</button>
                </form>
                
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="internship_id" value="<?php echo $request['internship_id']; ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn btn-danger">رفض</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
.requests-list {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: #7f8c8d;
}

.request-item {
    border: 1px solid #ecf0f1;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.request-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #ecf0f1;
}

.student-info {
    color: #7f8c8d;
    font-size: 0.9rem;
}

.detail-row {
    display: flex;
    gap: 30px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}

.request-actions {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #ecf0f1;
    display: flex;
    gap: 10px;
}

.btn-success {
    background: #27ae60;
    color: white;
}

.btn-danger {
    background: #e74c3c;
    color: white;
}

.btn-success:hover { background: #219a52; }
.btn-danger:hover { background: #c0392b; }
</style>

<?php include '../includes/footer.php'; ?>