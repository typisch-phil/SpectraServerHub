<?php
session_start();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$user_id = $_SESSION['user_id'];

// Get database instance
global $db;

try {
    switch ($method) {
        case 'GET':
            // Get tickets for current user or all tickets if admin
            $user = getCurrentUser();
            
            if (isset($_GET['id'])) {
                // Get specific ticket
                $ticket_id = intval($_GET['id']);
                
                $query = "SELECT t.*, u.first_name, u.last_name, u.email 
                         FROM tickets t 
                         LEFT JOIN users u ON t.user_id = u.id 
                         WHERE t.id = ?";
                
                if ($user['role'] !== 'admin') {
                    $query .= " AND t.user_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$ticket_id, $user_id]);
                } else {
                    $stmt = $db->prepare($query);
                    $stmt->execute([$ticket_id]);
                }
                
                $ticket = $stmt->fetch();
                if (!$ticket) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Ticket not found']);
                    exit;
                }
                
                echo json_encode($ticket);
            } else {
                // Get all tickets for user or all tickets if admin
                if ($user['role'] === 'admin') {
                    $stmt = $db->prepare("
                        SELECT t.*, u.first_name, u.last_name, u.email,
                               COUNT(r.id) as reply_count
                        FROM tickets t 
                        LEFT JOIN users u ON t.user_id = u.id 
                        LEFT JOIN ticket_replies r ON t.id = r.ticket_id
                        GROUP BY t.id
                        ORDER BY t.updated_at DESC
                    ");
                    $stmt->execute();
                } else {
                    $stmt = $db->prepare("
                        SELECT t.*, 
                               COUNT(r.id) as reply_count
                        FROM tickets t 
                        LEFT JOIN ticket_replies r ON t.id = r.ticket_id
                        WHERE t.user_id = ?
                        GROUP BY t.id
                        ORDER BY t.updated_at DESC
                    ");
                    $stmt->execute([$user_id]);
                }
                
                $tickets = $stmt->fetchAll();
                echo json_encode($tickets);
            }
            break;
            
        case 'POST':
            // Create new ticket
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['subject']) || !isset($input['message'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Subject and message are required']);
                exit;
            }
            
            $category = $input['category'] ?? 'general';
            $priority = $input['priority'] ?? 'medium';
            $subject = trim($input['subject']);
            $message = trim($input['message']);
            
            if (empty($subject) || empty($message)) {
                http_response_code(400);
                echo json_encode(['error' => 'Subject and message cannot be empty']);
                exit;
            }
            
            $stmt = $db->prepare("
                INSERT INTO tickets (user_id, category, priority, subject, message, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, 'open', datetime('now'), datetime('now'))
            ");
            
            if ($stmt->execute([$user_id, $category, $priority, $subject, $message])) {
                $ticket_id = $db->lastInsertId();
                echo json_encode([
                    'success' => true, 
                    'ticket_id' => $ticket_id,
                    'message' => 'Ticket created successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create ticket']);
            }
            break;
            
        case 'PUT':
            // Update ticket (admin only)
            $user = getCurrentUser();
            if ($user['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Ticket ID required']);
                exit;
            }
            
            $ticket_id = intval($_GET['id']);
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid input']);
                exit;
            }
            
            $updates = [];
            $params = [];
            
            if (isset($input['status'])) {
                $updates[] = 'status = ?';
                $params[] = $input['status'];
            }
            
            if (isset($input['priority'])) {
                $updates[] = 'priority = ?';
                $params[] = $input['priority'];
            }
            
            if (isset($input['assigned_to'])) {
                $updates[] = 'assigned_to = ?';
                $params[] = $input['assigned_to'];
            }
            
            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update']);
                exit;
            }
            
            $updates[] = 'updated_at = datetime(\'now\')';
            $params[] = $ticket_id;
            
            $sql = "UPDATE tickets SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            
            if ($stmt->execute($params)) {
                echo json_encode(['success' => true, 'message' => 'Ticket updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update ticket']);
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