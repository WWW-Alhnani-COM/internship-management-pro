<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/header.php';

checkUserType(['admin']);

$pageTitle = "إدارة المستخدمين";

// جلب جميع المستخدمين
$users_stmt = $pdo->prepare("
    SELECT u.*, 
           s.student_number, s.major,
           t.employee_id, t.department as teacher_dept,
           ss.company_name, ss.position
    FROM users u
    LEFT JOIN students s ON u.user_id = s.student_id
    LEFT JOIN teachers t ON u.user_id = t.teacher_id
    LEFT JOIN site_supervisors ss ON u.user_id = ss.supervisor_id
    ORDER BY u.created_at DESC
");
$users_stmt->execute();
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// إضافة مستخدم جديد
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user_type = $_POST['user_type'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone = $_POST['phone'];
    
    try {
        $pdo->beginTransaction();
        
        // إضافة المستخدم الأساسي
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, user_type, first_name, last_name, phone) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$username, $email, $password, $user_type, $first_name, $last_name, $phone]);
        $user_id = $pdo->lastInsertId();
        
        // إضافة بيانات إضافية حسب نوع المستخدم
        if ($user_type == 'student') {
            $student_number = $_POST['student_number'];
            $major = $_POST['major'];
            $academic_year = $_POST['academic_year'];
            
            $stmt = $pdo->prepare("
                INSERT INTO students (student_id, student_number, major, academic_year) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $student_number, $major, $academic_year]);
        } elseif ($user_type == 'teacher') {
            $employee_id = $_POST['employee_id'];
            $specialization = $_POST['specialization'];
            $department = $_POST['department'];
            
            $stmt = $pdo->prepare("
                INSERT INTO teachers (teacher_id, employee_id, specialization, department) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $employee_id, $specialization, $department]);
        } elseif ($user_type == 'supervisor') {
            $company_name = $_POST['company_name'];
            $position = $_POST['position'];
            $department = $_POST['supervisor_department'];
            
            $stmt = $pdo->prepare("
                INSERT INTO site_supervisors (supervisor_id, company_name, position, department) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $company_name, $position, $department]);
        }
        
        $pdo->commit();
        $success = "تم إضافة المستخدم بنجاح";
        header("Location: user-management.php");
        exit();
    } catch(PDOException $e) {
        $pdo->rollBack();
        $error = "حدث خطأ: " . $e->getMessage();
    }
}

// حذف مستخدم
if (isset($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $success = "تم حذف المستخدم بنجاح";
        header("Location: user-management.php");
        exit();
    } catch(PDOException $e) {
        $error = "حدث خطأ أثناء الحذف: " . $e->getMessage();
    }
}
?>

<div class="page-header">
    <h1>إدارة المستخدمين</h1>
    <button class="btn btn-primary" onclick="toggleUserForm()">إضافة مستخدم جديد</button>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div id="userForm" class="user-form-container" style="display: none;">
    <h3>إضافة مستخدم جديد</h3>
    <form method="POST">
        <input type="hidden" name="add_user" value="1">
        
        <div class="form-row">
            <div class="form-group">
                <label for="user_type">نوع المستخدم</label>
                <select id="user_type" name="user_type" required onchange="toggleUserFields()">
                    <option value="">اختر نوع المستخدم</option>
                    <option value="student">طالب</option>
                    <option value="teacher">مشرف أكاديمي</option>
                    <option value="supervisor">مشرف ميداني</option>
                    <option value="admin">مدير نظام</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="username">اسم المستخدم</label>
                <input type="text" id="username" name="username" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <input type="password" id="password" name="password" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">الاسم الأول</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            
            <div class="form-group">
                <label for="last_name">الاسم الأخير</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="phone">رقم الهاتف</label>
            <input type="text" id="phone" name="phone">
        </div>
        
        <!-- حقول الطالب -->
        <div id="student_fields" class="user-type-fields" style="display: none;">
            <h4>معلومات الطالب</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="student_number">الرقم الجامعي</label>
                    <input type="text" id="student_number" name="student_number">
                </div>
                
                <div class="form-group">
                    <label for="major">التخصص</label>
                    <input type="text" id="major" name="major">
                </div>
                
                <div class="form-group">
                    <label for="academic_year">السنة الدراسية</label>
                    <input type="text" id="academic_year" name="academic_year" placeholder="مثال: 4">
                </div>
            </div>
        </div>
        
        <!-- حقول المشرف الأكاديمي -->
        <div id="teacher_fields" class="user-type-fields" style="display: none;">
            <h4>معلومات المشرف الأكاديمي</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="employee_id">رقم الموظف</label>
                    <input type="text" id="employee_id" name="employee_id">
                </div>
                
                <div class="form-group">
                    <label for="specialization">التخصص</label>
                    <input type="text" id="specialization" name="specialization">
                </div>
                
                <div class="form-group">
                    <label for="department">القسم</label>
                    <input type="text" id="department" name="department">
                </div>
            </div>
        </div>
        
        <!-- حقول المشرف الميداني -->
        <div id="supervisor_fields" class="user-type-fields" style="display: none;">
            <h4>معلومات المشرف الميداني</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="company_name">اسم الشركة</label>
                    <input type="text" id="company_name" name="company_name">
                </div>
                
                <div class="form-group">
                    <label for="position">المنصب</label>
                    <input type="text" id="position" name="position">
                </div>
                
                <div class="form-group">
                    <label for="supervisor_department">القسم</label>
                    <input type="text" id="supervisor_department" name="supervisor_department">
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">إضافة المستخدم</button>
            <button type="button" class="btn btn-secondary" onclick="toggleUserForm()">إلغاء</button>
        </div>
    </form>
</div>

<div class="users-table-container">
    <h2>قائمة المستخدمين</h2>
    
    <div class="table-responsive">
        <table class="users-table">
            <thead>
                <tr>
                    <th>اسم المستخدم</th>
                    <th>البريد الإلكتروني</th>
                    <th>الاسم الكامل</th>
                    <th>نوع المستخدم</th>
                    <th>الحالة</th>
                    <th>تاريخ التسجيل</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $user): ?>
                <tr>
                    <td>
                        <strong><?php echo $user['username']; ?></strong>
                        <?php if ($user['user_type'] == 'student' && $user['student_number']): ?>
                        <br><small><?php echo $user['student_number']; ?></small>
                        <?php elseif ($user['user_type'] == 'teacher' && $user['employee_id']): ?>
                        <br><small><?php echo $user['employee_id']; ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $user['email']; ?></td>
                    <td><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></td>
                    <td>
                        <span class="user-type-badge user-type-<?php echo $user['user_type']; ?>">
                            <?php echo $user['user_type']; ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $user['is_active'] ? 'نشط' : 'غير نشط'; ?>
                        </span>
                    </td>
                    <td><?php echo $user['created_at']; ?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="edit-user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-info" title="تعديل">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?delete_user=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-danger" title="حذف" onclick="return confirm('هل أنت متأكد من الحذف؟')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php if ($user['user_type'] != 'admin'): ?>
                            <a href="reset-password.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-warning" title="إعادة تعيين كلمة المرور">
                                <i class="fas fa-key"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.user-form-container {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.user-type-fields {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 15px 0;
    border-right: 3px solid #3498db;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.users-table-container {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.users-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.users-table th,
.users-table td {
    padding: 12px 15px;
    text-align: right;
    border-bottom: 1px solid #ecf0f1;
}

.users-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
}

.user-type-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: bold;
}

.user-type-student { background: #e8f4fd; color: #3498db; }
.user-type-teacher { background: #fff3cd; color: #856404; }
.user-type-supervisor { background: #d4edda; color: #155724; }
.user-type-admin { background: #f8d7da; color: #721c24; }

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: bold;
}

.status-active { background: #d4edda; color: #155724; }
.status-inactive { background: #f8d7da; color: #721c24; }

.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-warning {
    background: #ffc107;
    color: #212529;
}

.btn-warning:hover {
    background: #e0a800;
}
</style>

<script>
function toggleUserForm() {
    const form = document.getElementById('userForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function toggleUserFields() {
    const userType = document.getElementById('user_type').value;
    
    // إخفاء جميع الحقول أولاً
    document.querySelectorAll('.user-type-fields').forEach(field => {
        field.style.display = 'none';
    });
    
    // إظهار الحقول المناسبة
    if (userType === 'student') {
        document.getElementById('student_fields').style.display = 'block';
    } else if (userType === 'teacher') {
        document.getElementById('teacher_fields').style.display = 'block';
    } else if (userType === 'supervisor') {
        document.getElementById('supervisor_fields').style.display = 'block';
    }
}
</script>

<?php include '../includes/footer.php'; ?>