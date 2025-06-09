<?php
session_start();
require_once '../includes/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$user_id = $_SESSION['user_id'];

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
            $ticketCheck = $database->prepare("SELECT user_id FROM tickets WHERE id = ?");
            $ticketCheck->execute([$ticket_id]);
            $ticket = $ticketCheck->fetch();
            
            if (!$ticket || ($user['role'] !== 'admin' && $ticket['user_id'] != $user_id)) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            $stmt = $database->prepare("
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
            // Create new reply
            $input = json_decode(file_get_contents('php://input'), true);
            
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
            $ticketCheck = $database->prepare("SELECT user_id, status FROM tickets WHERE id = ?");
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
            $stmt = $database->prepare("
                INSERT INTO ticket_replies (ticket_id, user_id, message, created_at) 
                VALUES (?, ?, ?, datetime('now'))
            ");
            
            if ($stmt->execute([$ticket_id, $user_id, $message])) {
                $reply_id = $database->lastInsertId();
                
                // Update ticket status and timestamp
                $new_status = ($user['role'] === 'admin') ? 'waiting_customer' : 'open';
                $updateStmt = $database->prepare("
                    UPDATE tickets 
                    SET status = ?, updated_at = datetime('now') 
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