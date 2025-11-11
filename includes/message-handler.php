<?php
include 'config.php';
include 'auth.php';

if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

if (isset($_POST['action']) && $_POST['action'] === 'send') {
    $receiver_id   = $_POST['receiver_id'] ?? null;
    $message_body  = $_POST['message'] ?? '';
    $internship_id = $_POST['internship_id'] ?? null;

    if (!$receiver_id || !$message_body) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, internship_id, message_body) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $receiver_id, $internship_id, $message_body]);

        echo json_encode([
            'success'    => true,
            'message_id' => $pdo->lastInsertId(),
            'message'    => $message_body,
            'receiver_id'=> $receiver_id
        ]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

} elseif (isset($_GET['action']) && $_GET['action'] === 'load') {
    $conversation_id = $_GET['conversation_id'] ?? null;

    if ($conversation_id) {
        $stmt = $pdo->prepare("SELECT m.*, u.first_name, u.last_name 
                               FROM messages m 
                               JOIN users u ON m.sender_id = u.user_id 
                               WHERE conversation_id = ? 
                               ORDER BY sent_date ASC");
        $stmt->execute([$conversation_id]);
    } else {
        $stmt = $pdo->prepare("SELECT m.*, u.first_name, u.last_name 
                               FROM messages m 
                               JOIN users u ON m.sender_id = u.user_id 
                               WHERE m.receiver_id = ? OR m.sender_id = ? 
                               ORDER BY sent_date ASC");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    }

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'messages' => $messages]);
}
?>
