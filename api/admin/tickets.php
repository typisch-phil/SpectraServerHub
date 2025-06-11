<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';

// Admin-Authentifizierung überprüfen
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Einzelnes Ticket mit Nachrichten abrufen
                $ticketId = (int)$_GET['id'];
                
                $ticket = $db->fetchOne("
                    SELECT st.*, 
                           CONCAT(u.first_name, ' ', u.last_name) as user_name,
                           u.email as user_email
                    FROM support_tickets st 
                    LEFT JOIN users u ON st.user_id = u.id 
                    WHERE st.id = ?
                ", [$ticketId]);
                
                if (!$ticket) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Ticket not found']);
                    exit;
                }
                
                // Nachrichten laden
                $messages = $db->fetchAll("
                    SELECT tm.*, 
                           CASE 
                               WHEN tm.is_admin_reply = 1 THEN CONCAT(au.first_name, ' ', au.last_name)
                               ELSE CONCAT(u.first_name, ' ', u.last_name)
                           END as author_name,
                           CASE 
                               WHEN tm.is_admin_reply = 1 THEN au.email
                               ELSE u.email
                           END as author_email
                    FROM ticket_messages tm
                    LEFT JOIN users u ON tm.user_id = u.id
                    LEFT JOIN users au ON tm.admin_id = au.id
                    WHERE tm.ticket_id = ?
                    ORDER BY tm.created_at ASC
                ", [$ticketId]);
                
                $ticket['messages'] = $messages;
                echo json_encode($ticket);
                
            } else {
                // Alle Tickets abrufen
                $status = $_GET['status'] ?? '';
                $priority = $_GET['priority'] ?? '';
                $category = $_GET['category'] ?? '';
                
                $query = "
                    SELECT st.*, 
                           CONCAT(u.first_name, ' ', u.last_name) as user_name,
                           u.email as user_email,
                           (SELECT COUNT(*) FROM ticket_messages tm WHERE tm.ticket_id = st.id) as message_count,
                           (SELECT tm.created_at FROM ticket_messages tm WHERE tm.ticket_id = st.id ORDER BY tm.created_at DESC LIMIT 1) as last_message_at
                    FROM support_tickets st 
                    LEFT JOIN users u ON st.user_id = u.id 
                    WHERE 1=1
                ";
                
                $params = [];
                
                if ($status !== '') {
                    $query .= " AND st.status = ?";
                    $params[] = $status;
                }
                
                if ($priority !== '') {
                    $query .= " AND st.priority = ?";
                    $params[] = $priority;
                }
                
                if ($category !== '') {
                    $query .= " AND st.category = ?";
                    $params[] = $category;
                }
                
                $query .= " ORDER BY 
                    CASE st.priority 
                        WHEN 'urgent' THEN 1 
                        WHEN 'high' THEN 2 
                        WHEN 'medium' THEN 3 
                        WHEN 'low' THEN 4 
                    END,
                    st.created_at DESC
                ";
                
                $tickets = $db->fetchAll($query, $params);
                echo json_encode($tickets);
            }
            break;
            
        case 'POST':
            // Neue Nachricht zu Ticket hinzufügen
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['ticket_id']) || !isset($input['message'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                exit;
            }
            
            $ticketId = (int)$input['ticket_id'];
            $message = trim($input['message']);
            
            if (empty($message)) {
                http_response_code(400);
                echo json_encode(['error' => 'Message cannot be empty']);
                exit;
            }
            
            // Überprüfen ob Ticket existiert
            $ticket = $db->fetchOne("SELECT id FROM support_tickets WHERE id = ?", [$ticketId]);
            if (!$ticket) {
                http_response_code(404);
                echo json_encode(['error' => 'Ticket not found']);
                exit;
            }
            
            // Nachricht hinzufügen
            $db->execute("
                INSERT INTO ticket_messages (ticket_id, admin_id, message, is_admin_reply, created_at) 
                VALUES (?, ?, ?, 1, NOW())
            ", [$ticketId, $_SESSION['user_id'], $message]);
            
            // Ticket-Status aktualisieren falls gewünscht
            if (isset($input['status'])) {
                $db->execute("
                    UPDATE support_tickets 
                    SET status = ?, updated_at = NOW() 
                    WHERE id = ?
                ", [$input['status'], $ticketId]);
            }
            
            $messageId = $db->lastInsertId();
            $newMessage = $db->fetchOne("
                SELECT tm.*, 
                       CONCAT(u.first_name, ' ', u.last_name) as author_name,
                       u.email as author_email
                FROM ticket_messages tm
                LEFT JOIN users u ON tm.admin_id = u.id
                WHERE tm.id = ?
            ", [$messageId]);
            
            echo json_encode(['success' => true, 'message' => $newMessage]);
            break;
            
        case 'PUT':
            // Ticket-Status oder andere Eigenschaften aktualisieren
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing ticket ID']);
                exit;
            }
            
            $ticketId = (int)$input['id'];
            
            // Überprüfen ob Ticket existiert
            $ticket = $db->fetchOne("SELECT id FROM support_tickets WHERE id = ?", [$ticketId]);
            if (!$ticket) {
                http_response_code(404);
                echo json_encode(['error' => 'Ticket not found']);
                exit;
            }
            
            $updateFields = [];
            $params = [];
            
            if (isset($input['status'])) {
                $allowedStatuses = ['open', 'in_progress', 'waiting_customer', 'resolved', 'closed'];
                if (in_array($input['status'], $allowedStatuses)) {
                    $updateFields[] = "status = ?";
                    $params[] = $input['status'];
                }
            }
            
            if (isset($input['priority'])) {
                $allowedPriorities = ['low', 'medium', 'high', 'urgent'];
                if (in_array($input['priority'], $allowedPriorities)) {
                    $updateFields[] = "priority = ?";
                    $params[] = $input['priority'];
                }
            }
            
            if (isset($input['category'])) {
                $allowedCategories = ['technical', 'billing', 'general', 'abuse'];
                if (in_array($input['category'], $allowedCategories)) {
                    $updateFields[] = "category = ?";
                    $params[] = $input['category'];
                }
            }
            
            if (empty($updateFields)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update']);
                exit;
            }
            
            $updateFields[] = "updated_at = NOW()";
            $params[] = $ticketId;
            
            $query = "UPDATE support_tickets SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $db->execute($query, $params);
            
            // Aktualisiertes Ticket zurückgeben
            $updatedTicket = $db->fetchOne("
                SELECT st.*, 
                       CONCAT(u.first_name, ' ', u.last_name) as user_name,
                       u.email as user_email
                FROM support_tickets st 
                LEFT JOIN users u ON st.user_id = u.id 
                WHERE st.id = ?
            ", [$ticketId]);
            
            echo json_encode(['success' => true, 'ticket' => $updatedTicket]);
            break;
            
        case 'DELETE':
            // Ticket löschen (nur in besonderen Fällen)
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing ticket ID']);
                exit;
            }
            
            $ticketId = (int)$input['id'];
            
            // Überprüfen ob Ticket existiert
            $ticket = $db->fetchOne("SELECT id FROM support_tickets WHERE id = ?", [$ticketId]);
            if (!$ticket) {
                http_response_code(404);
                echo json_encode(['error' => 'Ticket not found']);
                exit;
            }
            
            // Erst alle Nachrichten löschen
            $db->execute("DELETE FROM ticket_messages WHERE ticket_id = ?", [$ticketId]);
            
            // Dann das Ticket löschen
            $db->execute("DELETE FROM support_tickets WHERE id = ?", [$ticketId]);
            
            echo json_encode(['success' => true, 'message' => 'Ticket deleted']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Tickets API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>