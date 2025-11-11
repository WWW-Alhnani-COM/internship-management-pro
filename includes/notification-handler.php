<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/auth.php';

if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($action === 'mark_read') {
        $notificationId = $_GET['id'] ?? 0;
        $userId = $_SESSION['user_id'];
        
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE notification_id = ? AND user_id = ?
        ");
        
        $stmt->execute([$notificationId, $userId]);
        
        echo json_encode(['success' => true]);
    } 
    elseif ($action === 'mark_all_read') {
        $userId = $_SESSION['user_id'];
        
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE user_id = ? AND is_read = 0
        ");
        
        $stmt->execute([$userId]);
        
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'get_count') {
        $userId = $_SESSION['user_id'];
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        
        $stmt->execute([$userId]);
        $count = $stmt->fetchColumn();
        
        echo json_encode(['success' => true, 'count' => (int)$count]);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>