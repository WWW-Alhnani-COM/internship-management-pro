<?php
define('APP_LOADED', true);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
checkUserType(['student']);

$pageTitle = "Notifications";

// Get student ID
$studentId = $_SESSION['user_id'];

// Get notifications
$notificationsStmt = $pdo->prepare("
    SELECT * FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$notificationsStmt->execute([$studentId]);
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);

// Count unread notifications
$unreadCountStmt = $pdo->prepare("
    SELECT COUNT(*) as unread_count 
    FROM notifications 
    WHERE user_id = ? AND is_read = 0
");
$unreadCountStmt->execute([$studentId]);
$unreadCount = $unreadCountStmt->fetchColumn();

// Mark all notifications as read if the "Mark all as read" button was clicked
if (isset($_GET['mark_all_read'])) {
    $updateStmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE user_id = ? AND is_read = 0
    ");
    $updateStmt->execute([$studentId]);
    header("Location: notifications.php");
    exit();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="page-header">
    <h1>Notifications</h1>
    <div class="breadcrumbs">
        <a href="dashboard.php">Dashboard</a> / Notifications
    </div>
    <div class="actions">
        <?php if ($unreadCount > 0): ?>
            <a href="?mark_all_read=1" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-check-double"></i> Mark all as read
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($notifications)): ?>
    <div class="card text-center">
        <div class="card-body py-5">
            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
            <h4 class="mb-3">No notifications</h4>
            <p class="text-muted mb-4">You don't have any notifications yet.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Your Notifications</h5>
            <span class="badge bg-primary">
                <?php echo $unreadCount; ?> unread
            </span>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $notification): ?>
                    <a href="<?php echo $notification['action_url'] ?? '#'; ?>" 
                       class="list-group-item list-group-item-action notification-item 
                              <?php echo $notification['is_read'] == 0 ? 'unread' : ''; ?>">
                        <div class="notification-content">
                            <div class="notification-icon">
                                <i class="fas <?php 
                                    echo $notification['notification_type'] === 'task' ? 'fa-tasks' : 
                                         ($notification['notification_type'] === 'report' ? 'fa-file-alt' : 
                                         ($notification['notification_type'] === 'evaluation' ? 'fa-star' : 
                                         ($notification['notification_type'] === 'message' ? 'fa-envelope' : 
                                         ($notification['notification_type'] === 'system' ? 'fa-cog' : 'fa-bell')))); 
                                ?>"></i>
                            </div>
                            <div class="notification-details">
                                <div class="notification-title">
                                    <?php echo htmlspecialchars($notification['notification_title']); ?>
                                </div>
                                <div class="notification-message">
                                    <?php echo htmlspecialchars($notification['notification_message']); ?>
                                </div>
                                <div class="notification-time">
                                    <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?>
                                    <?php if ($notification['priority'] === 'high'): ?>
                                        <span class="badge bg-danger ms-2">High Priority</span>
                                    <?php elseif ($notification['priority'] === 'medium'): ?>
                                        <span class="badge bg-warning ms-2">Medium Priority</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto mark notifications as seen when clicked
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (this.classList.contains('unread')) {
                    // Mark as read in the database via AJAX
                    const notificationId = this.dataset.notificationId;
                }
            });
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>