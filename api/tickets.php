<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$database = Database::getInstance();

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Get single ticket with replies and attachments
            $ticketId = (int)$_GET['id'];
            
            // Check if user can access this ticket
            $stmt = $database->prepare("
                SELECT t.*, u.first_name, u.last_name, u.email, 
                       a.first_name as assigned_first_name, a.last_name as assigned_last_name
                FROM tickets t 
                JOIN users u ON t.user_id = u.id 
                LEFT JOIN users a ON t.assigned_to = a.id
                WHERE t.id = ? AND (t.user_id = ? OR ? IN (SELECT id FROM users WHERE role IN ('admin', 'support')))
            ");
            $stmt->execute([$ticketId, $_SESSION['user_id'], $_SESSION['user_id']]);
            $ticket = $stmt->fetch();
            
            if (!$ticket) {
                http_response_code(404);
                echo json_encode(['error' => 'Ticket nicht gefunden']);
                exit;
            }
            
            // Get replies
            $stmt = $database->prepare("
                SELECT r.*, u.first_name, u.last_name, u.role
                FROM ticket_replies r
                JOIN users u ON r.user_id = u.id
                WHERE r.ticket_id = ?
                ORDER BY r.created_at ASC
            ");
            $stmt->execute([$ticketId]);
            $replies = $stmt->fetchAll();
            
            // Get attachments for ticket and replies
            $stmt = $database->prepare("
                SELECT a.*, u.first_name, u.last_name
                FROM ticket_attachments a
                JOIN users u ON a.uploaded_by = u.id
                WHERE a.ticket_id = ?
                ORDER BY a.created_at ASC
            ");
            $stmt->execute([$ticketId]);
            $attachments = $stmt->fetchAll();
            
            $ticket['replies'] = $replies;
            $ticket['attachments'] = $attachments;
            
            echo json_encode($ticket);
        } else {
            // Get all tickets for user or admin
            $userRole = $database->prepare("SELECT role FROM users WHERE id = ?");
            $userRole->execute([$_SESSION['user_id']]);
            $role = $userRole->fetch()['role'];
            
            if (in_array($role, ['admin', 'support'])) {
                // Admin/Support can see all tickets
                $stmt = $database->prepare("
                    SELECT t.*, u.first_name, u.last_name, u.email,
                           a.first_name as assigned_first_name, a.last_name as assigned_last_name,
                           COUNT(r.id) as reply_count
                    FROM tickets t 
                    JOIN users u ON t.user_id = u.id 
                    LEFT JOIN users a ON t.assigned_to = a.id
                    LEFT JOIN ticket_replies r ON t.id = r.ticket_id
                    GROUP BY t.id
                    ORDER BY t.updated_at DESC
                ");
                $stmt->execute();
            } else {
                // Regular users only see their own tickets
                $stmt = $database->prepare("
                    SELECT t.*, COUNT(r.id) as reply_count
                    FROM tickets t 
                    LEFT JOIN ticket_replies r ON t.id = r.ticket_id
                    WHERE t.user_id = ?
                    GROUP BY t.id
                    ORDER BY t.updated_at DESC
                ");
                $stmt->execute([$_SESSION['user_id']]);
            }
            
            $tickets = $stmt->fetchAll();
            echo json_encode($tickets);
        }
        break;
        
    case 'POST':
        // Create new ticket
        if (!isset($input['subject']) || !isset($input['message'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Betreff und Nachricht sind erforderlich']);
            exit;
        }
        
        $subject = trim($input['subject']);
        $message = trim($input['message']);
        $category = $input['category'] ?? 'general';
        $priority = $input['priority'] ?? 'medium';
        
        if (empty($subject) || empty($message)) {
            http_response_code(400);
            echo json_encode(['error' => 'Betreff und Nachricht dürfen nicht leer sein']);
            exit;
        }
        
        $stmt = $database->prepare("
            INSERT INTO tickets (user_id, subject, message, category, priority) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $subject, $message, $category, $priority]);
        
        $ticketId = $database->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'ticket_id' => $ticketId,
            'message' => 'Ticket erfolgreich erstellt'
        ]);
        break;
        
    case 'PUT':
        // Update ticket (admin/support only)
        $userRole = $database->prepare("SELECT role FROM users WHERE id = ?");
        $userRole->execute([$_SESSION['user_id']]);
        $role = $userRole->fetch()['role'];
        
        if (!in_array($role, ['admin', 'support'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Keine Berechtigung']);
            exit;
        }
        
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Ticket-ID erforderlich']);
            exit;
        }
        
        $ticketId = (int)$_GET['id'];
        $updates = [];
        $params = [];
        
        if (isset($input['status'])) {
            $updates[] = "status = ?";
            $params[] = $input['status'];
        }
        
        if (isset($input['priority'])) {
            $updates[] = "priority = ?";
            $params[] = $input['priority'];
        }
        
        if (isset($input['assigned_to'])) {
            $updates[] = "assigned_to = ?";
            $params[] = $input['assigned_to'] ?: null;
        }
        
        if (!empty($updates)) {
            $updates[] = "updated_at = CURRENT_TIMESTAMP";
            $params[] = $ticketId;
            
            $sql = "UPDATE tickets SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $database->prepare($sql);
            $stmt->execute($params);
        }
        
        echo json_encode(['success' => true, 'message' => 'Ticket aktualisiert']);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Methode nicht erlaubt']);
        break;
}
?>