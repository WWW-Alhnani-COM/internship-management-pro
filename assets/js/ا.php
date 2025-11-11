<?php
define('APP_LOADED', true);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = _("messages");

// الحصول على قائمة المستخدمين لاختيار المرسل إليه
$users_stmt = $pdo->prepare("
    SELECT u.user_id, u.first_name, u.last_name, u.user_type, u.profile_image
    FROM users u
    WHERE u.user_id != ? AND u.is_active = 1
    ORDER BY u.first_name, u.last_name
");
$users_stmt->execute([$_SESSION['user_id']]);
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب المحادثات الأخيرة
$recent_conversations_stmt = $pdo->prepare("
    SELECT DISTINCT
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END as user_id,
        u.first_name, u.last_name, u.user_type, u.profile_image,
        (SELECT message_body FROM messages 
         WHERE (sender_id = ? AND receiver_id = user_id) 
            OR (sender_id = user_id AND receiver_id = ?) 
         ORDER BY sent_date DESC LIMIT 1) as last_message,
        (SELECT sent_date FROM messages 
         WHERE (sender_id = ? AND receiver_id = user_id) 
            OR (sender_id = user_id AND receiver_id = ?) 
         ORDER BY sent_date DESC LIMIT 1) as last_message_date,
        (SELECT COUNT(*) FROM messages 
         WHERE sender_id = user_id AND receiver_id = ? AND is_read = 0) as unread_count
    FROM messages m
    JOIN users u ON u.user_id = CASE 
        WHEN m.sender_id = ? THEN m.receiver_id 
        ELSE m.sender_id 
    END
    WHERE ? IN (m.sender_id, m.receiver_id)
    ORDER BY last_message_date DESC
    LIMIT 10
");
$recent_conversations_stmt->execute([
    $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], 
    $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], 
    $_SESSION['user_id'], $_SESSION['user_id']
]);
$recent_conversations = $recent_conversations_stmt->fetchAll(PDO::FETCH_ASSOC);

// معالجة إرسال رسالة جديدة
$message_sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = $_POST['receiver_id'];
    $subject = $_POST['subject'];
    $message_body = $_POST['message_body'];
    $internship_id = $_POST['internship_id'] ?? null;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, internship_id, message_subject, message_body, sent_date, is_read)
            VALUES (?, ?, ?, ?, ?, NOW(), 0)
        ");
        $stmt->execute([$_SESSION['user_id'], $receiver_id, $internship_id, $subject, $message_body]);
        
        $message_sent = true;
        $success_message = ("message_sent");
    } catch(PDOException $e) {
        $error_message = ("error_occurred") . ": " . $e->getMessage();
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="page-header">
    <h1><?php echo ("messages"); ?></h1>
    <div class="breadcrumbs">
        <a href="../dashboard.php"><?php echo ("dashboard"); ?></a> / <?php echo ("messages"); ?>
    </div>
    <div class="actions">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#composeMessageModal">
            <i class="fas fa-plus"></i> <?php echo ("compose"); ?>
        </button>
    </div>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo ("conversations"); ?></h5>
                <button class="btn btn-sm btn-secondary" id="markAllRead">
                    <i class="fas fa-check-double"></i> <?php echo _("mark_all_as_read"); ?>
                </button>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent_conversations)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                        <p class="text-muted"><?php echo ("no_messages"); ?></p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_conversations as $conversation): ?>
                            <a href="#conversation-<?php echo $conversation['user_id']; ?>" class="list-group-item list-group-item-action conversation-item <?php echo ($conversation['unread_count'] > 0) ? 'unread' : ''; ?>">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar">
                                            <?php echo strtoupper(substr($conversation['first_name'], 0, 1)); ?>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-0"><?php echo $conversation['first_name'] . ' ' . $conversation['last_name']; ?></h6>
                                            <small class="text-muted"><?php echo date('M d', strtotime($conversation['last_message_date'])); ?></small>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p class="mb-1 text-muted small"><?php echo substr($conversation['last_message'], 0, 30) . (strlen($conversation['last_message']) > 30 ? '...' : ''); ?></p>
                                            <?php if ($conversation['unread_count'] > 0): ?>
                                                <span class="badge bg-primary rounded-pill"><?php echo $conversation['unread_count']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">
                                            <span class="user-type-badge user-type-<?php echo $conversation['user_type']; ?>">
                                                <?php 
                                                $types = [
                                                    'student' => ('student'),
                                                    'teacher' => ('teacher'),
                                                    'supervisor' => ('supervisor'),
                                                    'admin' => ('admin')
                                                ];
                                                echo $types[$conversation['user_type']] ?? $conversation['user_type'];
                                                ?>
                                            </span>
                                        </small>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo ("new_message"); ?></h5>
            </div>
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                    <p class="text-muted"><?php echo ("select_conversation"); ?></p>
                    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#composeMessageModal">
                        <i class="fas fa-plus"></i> <?php echo ("compose"); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مودال تكوين رسالة جديدة -->
<div class="modal fade" id="composeMessageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo ("compose"); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="sendMessageForm">
                <div class="modal-body">
                    <input type="hidden" name="send_message" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo ("recipient"); ?></label>
                        <select class="form-select" name="receiver_id" required>
                            <option value=""><?php echo ("select_recipient"); ?></option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['user_id']; ?>">
                                    <?php echo $user['first_name'] . ' ' . $user['last_name']; ?> 
                                    (<?php 
                                        $types = [
                                            'student' => ('student'),
                                            'teacher' => ('teacher'),
                                            'supervisor' => ('supervisor'),
                                            'admin' => ('admin')
                                        ];
                                        echo $types[$user['user_type']] ?? $user['user_type'];
                                    ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo ("subject"); ?></label>
                        <input type="text" class="form-control" name="subject" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo ("message"); ?></label>
                        <textarea class="form-control" name="message_body" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo ("cancel"); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo ("send"); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // تبديل القائمة الجانبية على الجوال
        const toggleBtn = document.querySelector('.toggle-sidebar');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                mainContent.classList.toggle('sidebar-active');
            });
        }
        
        // التحكم في القوائم الفرعية
        const submenuToggles = document.querySelectorAll('[data-toggle="submenu"]');
        submenuToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                const isSubmenuLink = this.parentElement.classList.contains('has-submenu');
                if (isSubmenuLink) {
                    e.preventDefault();
                    const submenu = this.nextElementSibling;
                    const icon = this.querySelector('.submenu-icon');
                    
                    if (submenu && submenu.classList.contains('submenu')) {
                        submenu.classList.toggle('active');
                        if (icon) {
                            icon.classList.toggle('rotate-180');
                        }
                    }
                }
            });
        });
        
        // إغلاق القائمة الجانبية عند النقر على رابط
        const menuLinks = document.querySelectorAll('.menu-link, .submenu-link');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('active');
                    mainContent.classList.remove('sidebar-active');
                }
            });
        });
        
        // تعليم كل الرسائل كمقروءة
        document.getElementById('markAllRead').addEventListener('click', function() {
            fetch('includes/message-handler.php?action=mark_all_read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ user_id: <?php echo $_SESSION['user_id']; ?> })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.conversation-item.unread').forEach(item => {
                        item.classList.remove('unread');
                    });
                    document.querySelectorAll('.badge.bg-primary').forEach(badge => {
                        badge.remove();
                    });
                }
            });
        });
        
        // معالجة إرسال الرسالة
        document.getElementById('sendMessageForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('includes/message-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('composeMessageModal'));
                    modal.hide();
                    
                    // إعادة تحميل الصفحة أو تحديث القائمة
                    location.reload();
                } else {
                    alert(data.message || "<?php echo ("error_occurred"); ?>");
                }
            });
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>