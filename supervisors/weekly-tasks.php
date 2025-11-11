<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/header.php';

checkUserType(['supervisor']);

$pageTitle = "المهام الأسبوعية";

// جلب الطلاب تحت الإشراف
$students_stmt = $pdo->prepare("
    SELECT i.internship_id, u.user_id, u.first_name, u.last_name, s.student_number 
    FROM internships i 
    JOIN users u ON i.student_id = u.user_id 
    JOIN students s ON i.student_id = s.student_id 
    WHERE i.supervisor_id = ? AND i.status = 'active'
");
$students_stmt->execute([$_SESSION['user_id']]);
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب المهام
$tasks_stmt = $pdo->prepare("
    SELECT t.*, u.first_name, u.last_name 
    FROM tasks t 
    JOIN internships i ON t.internship_id = i.internship_id 
    JOIN users u ON i.student_id = u.user_id 
    WHERE t.supervisor_id = ? 
    ORDER BY t.deadline DESC
");
$tasks_stmt->execute([$_SESSION['user_id']]);
$tasks = $tasks_stmt->fetchAll(PDO::FETCH_ASSOC);

// إضافة مهمة جديدة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_task'])) {
    $internship_id = $_POST['internship_id'];
    $task_title = $_POST['task_title'];
    $task_description = $_POST['task_description'];
    $deadline = $_POST['deadline'];
    $priority = $_POST['priority'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO tasks (internship_id, supervisor_id, task_title, task_description, deadline, priority) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$internship_id, $_SESSION['user_id'], $task_title, $task_description, $deadline, $priority]);
        
        $success = "تم إضافة المهمة بنجاح";
        header("Location: weekly-tasks.php");
        exit();
    } catch(PDOException $e) {
        $error = "حدث خطأ: " . $e->getMessage();
    }
}
?>

<div class="page-header">
    <h1>المهام الأسبوعية</h1>
</div>

<div class="tasks-container">
    <div class="tasks-sidebar">
        <h3>إضافة مهمة جديدة</h3>
        
        <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="add_task" value="1">
            
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
            
            <div class="form-group">
                <label for="task_title">عنوان المهمة</label>
                <input type="text" id="task_title" name="task_title" required>
            </div>
            
            <div class="form-group">
                <label for="task_description">وصف المهمة</label>
                <textarea id="task_description" name="task_description" rows="4"></textarea>
            </div>
            
            <div class="form-group">
                <label for="deadline">الموعد النهائي</label>
                <input type="date" id="deadline" name="deadline" required>
            </div>
            
            <div class="form-group">
                <label for="priority">الأولوية</label>
                <select id="priority" name="priority" required>
                    <option value="low">منخفضة</option>
                    <option value="medium" selected>متوسطة</option>
                    <option value="high">عالية</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">إضافة المهمة</button>
        </form>
    </div>
    
    <div class="tasks-content">
        <h2>المهام الحالية</h2>
        
        <div class="tasks-list">
            <?php foreach($tasks as $task): ?>
            <div class="task-item">
                <div class="task-header">
                    <h4><?php echo $task['task_title']; ?></h4>
                    <div class="task-meta">
                        <span class="student-name"><?php echo $task['first_name'] . ' ' . $task['last_name']; ?></span>
                        <span class="task-priority <?php echo $task['priority']; ?>"><?php echo $task['priority']; ?></span>
                        <span class="task-status <?php echo $task['status']; ?>"><?php echo $task['status']; ?></span>
                    </div>
                </div>
                
                <?php if ($task['task_description']): ?>
                <p class="task-description"><?php echo $task['task_description']; ?></p>
                <?php endif; ?>
                
                <div class="task-footer">
                    <span class="deadline">الموعد النهائي: <?php echo $task['deadline']; ?></span>
                    <span class="assigned-date">تم التعيين: <?php echo $task['assigned_date']; ?></span>
                </div>
                
                <?php if ($task['student_notes']): ?>
                <div class="student-notes">
                    <strong>ملاحظات الطالب:</strong>
                    <p><?php echo $task['student_notes']; ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.tasks-container {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 30px;
}

.tasks-sidebar {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    height: fit-content;
}

.tasks-content {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.task-item {
    border: 1px solid #ecf0f1;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.task-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 5px;
}

.student-name {
    color: #7f8c8d;
    font-size: 0.9rem;
}

.task-priority, .task-status {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: bold;
}

.task-priority.low { background: #d4edda; color: #155724; }
.task-priority.medium { background: #fff3cd; color: #856404; }
.task-priority.high { background: #f8d7da; color: #721c24; }

.task-status.pending { background: #fff3cd; color: #856404; }
.task-status.in_progress { background: #d1ecf1; color: #0c5460; }
.task-status.completed { background: #d4edda; color: #155724; }
.task-status.overdue { background: #f8d7da; color: #721c24; }

.task-description {
    color: #5a6c7d;
    margin-bottom: 10px;
}

.task-footer {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
    color: #7f8c8d;
}

.student-notes {
    margin-top: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    border-right: 3px solid #3498db;
}
</style>

<?php include '../includes/footer.php'; ?>