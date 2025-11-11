<?php
define('APP_LOADED', true);
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
checkUserType(['student']);

$pageTitle = "Messages";

// Get student ID
$studentId = $_SESSION['user_id'];

// Get conversations
$conversationsStmt = $pdo->prepare("
    SELECT DISTINCT
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END as other_user_id,
        u.first_name, u.last_name, u.user_type,
        m.message_body as last_message,
        m.sent_date as last_message_date,
        m.is_read,
        (SELECT COUNT(*) FROM messages 
         WHERE (sender_id = other_user_id AND receiver_id = ?) 
         AND is_read = 0) as unread_count
    FROM messages m
    JOIN users u ON u.user_id = CASE 
        WHEN m.sender_id = ? THEN m.receiver_id 
        ELSE m.sender_id 
    END
    WHERE ? IN (m.sender_id, m.receiver_id)
    ORDER BY m.sent_date DESC
");
$conversationsStmt->execute([$studentId, $studentId, $studentId, $studentId]);
$conversations = $conversationsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected conversation messages if conversation ID is provided
$selectedConversation = null;
$conversationMessages = [];
if (isset($_GET['conversation_id'])) {
    $conversationId = $_GET['conversation_id'];
    
    // Get user details for the conversation
    $userStmt = $pdo->prepare("SELECT user_id, first_name, last_name, user_type FROM users WHERE user_id = ?");
    $userStmt->execute([$conversationId]);
    $selectedConversation = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selectedConversation) {
        // Mark messages as read
        $updateStmt = $pdo->prepare("
            UPDATE messages 
            SET is_read = 1 
            WHERE sender_id = ? AND receiver_id = ?
        ");
        $updateStmt->execute([$conversationId, $studentId]);
        
        // Get messages for this conversation
        $messagesStmt = $pdo->prepare("
            SELECT m.*, u.first_name, u.last_name, u.user_type
            FROM messages m
            JOIN users u ON m.sender_id = u.user_id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.sent_date ASC
        ");
        $messagesStmt->execute([$conversationId, $studentId, $studentId, $conversationId]);
        $conversationMessages = $messagesStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="page-header">
    <h1>Messages</h1>
    <div class="breadcrumbs">
        <a href="dashboard.php">Dashboard</a> / Messages
    </div>
</div>

<div class="row">
    <!-- Conversations list -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Conversations</h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <div class="card-body p-0">
                <?php if (empty($conversations)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                        <p class="text-muted">No conversations yet</p>
                        <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                            <i class="fas fa-plus"></i> Start a conversation
                        </button>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($conversations as $conversation): ?>
                            <a href="messaging.php?conversation_id=<?php echo $conversation['other_user_id']; ?>" 
                               class="list-group-item list-group-item-action 
                                      <?php echo isset($_GET['conversation_id']) && $_GET['conversation_id'] == $conversation['other_user_id'] ? 'active' : ''; ?>
                                      <?php echo $conversation['unread_count'] > 0 ? 'unread' : ''; ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2">
                                            <?php echo strtoupper(substr($conversation['first_name'], 0, 1) . substr($conversation['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo $conversation['first_name'] . ' ' . $conversation['last_name']; ?></h6>
                                            <small class="text-muted">
                                                <?php 
                                                $userTypes = [
                                                    'student' => 'Student',
                                                    'teacher' => 'Academic Supervisor',
                                                    'supervisor' => 'Field Supervisor',
                                                    'admin' => 'Administrator'
                                                ];
                                                echo $userTypes[$conversation['user_type']] ?? ucfirst($conversation['user_type']);
                                                ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php if ($conversation['unread_count'] > 0): ?>
                                        <span class="badge bg-primary rounded-pill"><?php echo $conversation['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-truncate" style="max-width: 200px;">
                                        <?php echo strlen($conversation['last_message']) > 30 ? 
                                            substr($conversation['last_message'], 0, 30) . '...' : 
                                            $conversation['last_message']; ?>
                                    </small>
                                    <small class="text-muted ms-2"><?php echo date('M d, h:i A', strtotime($conversation['last_message_date'])); ?></small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Messages area -->
    <div class="col-md-8">
        <?php if (isset($_GET['conversation_id']) && $selectedConversation): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="user-avatar me-2">
                            <?php echo strtoupper(substr($selectedConversation['first_name'], 0, 1) . substr($selectedConversation['last_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h5 class="mb-0"><?php echo $selectedConversation['first_name'] . ' ' . $selectedConversation['last_name']; ?></h5>
                            <small class="text-muted">
                                <?php 
                                $userTypes = [
                                    'student' => 'Student',
                                    'teacher' => 'Academic Supervisor',
                                    'supervisor' => 'Field Supervisor',
                                    'admin' => 'Administrator'
                                ];
                                echo $userTypes[$selectedConversation['user_type']] ?? ucfirst($selectedConversation['user_type']);
                                ?>
                            </small>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-info-circle"></i> Details
                    </button>
                </div>
                <div class="card-body message-area" style="height: 500px; overflow-y: auto;">
                    <?php if (empty($conversationMessages)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No messages yet. Start the conversation!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversationMessages as $message): ?>
                            <div class="message-container <?php echo $message['sender_id'] == $studentId ? 'sent' : 'received'; ?>">
                                <div class="message-avatar">
                                    <?php echo strtoupper(substr($message['first_name'], 0, 1) . substr($message['last_name'], 0, 1)); ?>
                                </div>
                                <div class="message-content">
                                    <div class="message-header">
                                        <div class="message-sender">
                                            <?php echo $message['sender_id'] == $studentId ? 'You' : $message['first_name'] . ' ' . $message['last_name']; ?>
                                        </div>
                                        <div class="message-time">
                                            <?php echo date('M d, h:i A', strtotime($message['sent_date'])); ?>
                                        </div>
                                    </div>
                                    <div class="message-text">
                                        <?php echo nl2br(htmlspecialchars($message['message_body'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="card-footer message-input-container">
                    <form id="messageForm">
                        <input type="hidden" name="action" value="send">
                        <input type="hidden" name="receiver_id" value="<?php echo $selectedConversation['user_id']; ?>">
                        <input type="hidden" name="conversation_id" value="<?php echo $selectedConversation['user_id']; ?>">
                        <div class="input-group">
                            <textarea class="form-control" name="message" placeholder="Type your message..." rows="2" required></textarea>
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="card text-center">
                <div class="card-body py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h4 class="mb-3">Select a conversation</h4>
                    <p class="text-muted mb-4">Choose a conversation from the left sidebar to view messages or start a new one.</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                        <i class="fas fa-plus"></i> Start New Conversation
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- New Message Modal -->
<div class="modal fade" id="newMessageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="newMessageForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Recipient</label>
                        <select class="form-select" name="receiver_id" required>
                            <option value="">Select a recipient</option>
                            <?php
                            // Get potential recipients (teachers and supervisors related to student's internships)
                            $recipientsStmt = $pdo->prepare("
                                SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.user_type
                                FROM users u
                                JOIN internships i ON (u.user_id = i.teacher_id OR u.user_id = i.supervisor_id)
                                WHERE i.student_id = ? AND u.user_id != ?
                            ");
                            $recipientsStmt->execute([$studentId, $studentId]);
                            $recipients = $recipientsStmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($recipients as $recipient): 
                                $userType = $recipient['user_type'] === 'teacher' ? 'Academic Supervisor' : 
                                           ($recipient['user_type'] === 'supervisor' ? 'Field Supervisor' : 'Other');
                            ?>
                                <option value="<?php echo $recipient['user_id']; ?>">
                                    <?php echo $recipient['first_name'] . ' ' . $recipient['last_name'] . ' (' . $userType . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" class="form-control" name="subject" placeholder="Message subject" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="message" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto scroll to bottom of message area
        const messageArea = document.querySelector('.message-area');
        if (messageArea) {
            messageArea.scrollTop = messageArea.scrollHeight;
        }
        
        // Send message form submission
        const messageForm = document.getElementById('messageForm');
        if (messageForm) {
            messageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('../includes/message-handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear the textarea
                        this.querySelector('textarea[name="message"]').value = '';
                        
                        // Append the new message to the conversation
                        if (messageArea) {
                            const newMessage = document.createElement('div');
                            newMessage.className = 'message-container sent';
                            newMessage.innerHTML = `
                                <div class="message-avatar">
                                    <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?>
                                </div>
                                <div class="message-content">
                                    <div class="message-header">
                                        <div class="message-sender">You</div>
                                        <div class="message-time"><?php echo date('M d, h:i A'); ?></div>
                                    </div>
                                    <div class="message-text">${formData.get('message')}</div>
                                </div>
                            `;
                            messageArea.appendChild(newMessage);
                            messageArea.scrollTop = messageArea.scrollHeight;
                        }
                    } else {
                        alert('Error sending message: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while sending the message');
                });
            });
        }
        
        // New message form submission
        const newMessageForm = document.getElementById('newMessageForm');
        if (newMessageForm) {
            newMessageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', 'send');
                
                fetch('../includes/message-handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close the modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('newMessageModal'));
                        modal.hide();
                        
                        // Redirect to the new conversation
                        window.location.href = 'messaging.php?conversation_id=' + formData.get('receiver_id');
                    } else {
                        alert('Error sending message: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while sending the message');
                });
            });
        }
    });
     src="assets/js/messages.js"

</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>