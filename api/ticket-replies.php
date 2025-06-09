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

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['ticket_id']) || !isset($input['message'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Ticket-ID und Nachricht sind erforderlich']);
        exit;
    }
    
    $ticketId = (int)$input['ticket_id'];
    $message = trim($input['message']);
    $isInternal = isset($input['is_internal']) ? (bool)$input['is_internal'] : false;
    
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Nachricht darf nicht leer sein']);
        exit;
    }
    
    // Check if user can reply to this ticket
    $stmt = $database->prepare("
        SELECT t.*, u.role 
        FROM tickets t, users u 
        WHERE t.id = ? AND u.id = ? AND (t.user_id = ? OR u.role IN ('admin', 'support'))
    ");
    $stmt->execute([$ticketId, $_SESSION['user_id'], $_SESSION['user_id']]);
    $access = $stmt->fetch();
    
    if (!$access) {
        http_response_code(403);
        echo json_encode(['error' => 'Keine Berechtigung für dieses Ticket']);
        exit;
    }
    
    // Only admin/support can create internal notes
    if ($isInternal && !in_array($access['role'], ['admin', 'support'])) {
        $isInternal = false;
    }
    
    // Insert reply
    $stmt = $database->prepare("
        INSERT INTO ticket_replies (ticket_id, user_id, message, is_internal) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$ticketId, $_SESSION['user_id'], $message, $isInternal ? 1 : 0]);
    
    // Update ticket timestamp and status
    $newStatus = $access['role'] === 'customer' ? 'open' : 'waiting_customer';
    $stmt = $database->prepare("
        UPDATE tickets 
        SET updated_at = CURRENT_TIMESTAMP, status = CASE WHEN status = 'closed' THEN 'open' ELSE ? END
        WHERE id = ?
    ");
    $stmt->execute([$newStatus, $ticketId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Antwort hinzugefügt'
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
}
?>