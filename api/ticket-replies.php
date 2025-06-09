<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Get database instance
$database = Database::getInstance();
$db = $database->getConnection();

// Get user ID from session
$user = getCurrentUser();
$user_id = $user ? $user['id'] : null;

try {
    switch ($method) {
        case 'GET':
            // Get replies for a ticket
            if (!isset($_GET['ticket_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Ticket ID required']);
                exit;
            }
            
            $ticket_id = intval($_GET['ticket_id']);
            $user = getCurrentUser();
            
            // Check if user has access to this ticket
            $ticketCheck = $db->prepare("SELECT user_id FROM tickets WHERE id = ?");
            $ticketCheck->execute([$ticket_id]);
            $ticket = $ticketCheck->fetch();
            
            if (!$ticket || ($user['role'] !== 'admin' && $ticket['user_id'] != $user_id)) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            $stmt = $db->prepare("
                SELECT r.*, u.first_name, u.last_name, u.role,
                       GROUP_CONCAT(a.filename) as attachments
                FROM ticket_replies r 
                LEFT JOIN users u ON r.user_id = u.id 
                LEFT JOIN ticket_attachments a ON r.id = a.reply_id
                WHERE r.ticket_id = ?
                GROUP BY r.id
                ORDER BY r.created_at ASC
            ");
            $stmt->execute([$ticket_id]);
            $replies = $stmt->fetchAll();
            
            // Process attachments
            foreach ($replies as &$reply) {
                if ($reply['attachments']) {
                    $reply['attachments'] = explode(',', $reply['attachments']);
                } else {
                    $reply['attachments'] = [];
                }
            }
            
            echo json_encode($replies);
            break;
            
        case 'POST':
            // Create new reply - support both JSON and FormData
            $input = json_decode(file_get_contents('php://input'), true);
            
            // If JSON parsing failed, try $_POST (FormData)
            if (!$input) {
                $input = $_POST;
            }
            
            if (!$input || !isset($input['ticket_id']) || !isset($input['message'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Ticket ID and message are required']);
                exit;
            }
            
            $ticket_id = intval($input['ticket_id']);
            $message = trim($input['message']);
            
            if (empty($message)) {
                http_response_code(400);
                echo json_encode(['error' => 'Message cannot be empty']);
                exit;
            }
            
            $user = getCurrentUser();
            
            // Check if user has access to this ticket
            $ticketCheck = $db->prepare("SELECT user_id, status FROM tickets WHERE id = ?");
            $ticketCheck->execute([$ticket_id]);
            $ticket = $ticketCheck->fetch();
            
            if (!$ticket || ($user['role'] !== 'admin' && $ticket['user_id'] != $user_id)) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            if ($ticket['status'] === 'closed') {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot reply to closed ticket']);
                exit;
            }
            
            // Insert reply
            $stmt = $db->prepare("
                INSERT INTO ticket_replies (ticket_id, user_id, message) 
                VALUES (?, ?, ?)
            ");
            
            if ($stmt->execute([$ticket_id, $user_id, $message])) {
                $reply_id = $db->lastInsertId();
                
                // Update ticket status and timestamp
                $new_status = ($user['role'] === 'admin') ? 'waiting_customer' : 'open';
                $updateStmt = $db->prepare("
                    UPDATE tickets 
                    SET status = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $updateStmt->execute([$new_status, $ticket_id]);
                
                echo json_encode([
                    'success' => true, 
                    'reply_id' => $reply_id,
                    'message' => 'Reply created successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create reply']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>