<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}

$user_id = $_SESSION['user_id'];
$ticket_id = $_GET['id'] ?? null;

if (!$ticket_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Ticket ID erforderlich']);
    exit;
}

try {
    // Ticket-Details abrufen
    $stmt = $db->prepare("
        SELECT t.* FROM support_tickets t 
        WHERE t.id = ? AND t.user_id = ?
    ");
    $stmt->bind_param("ii", $ticket_id, $user_id);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket nicht gefunden']);
        exit;
    }
    
    // Nachrichten abrufen
    $stmt = $db->prepare("
        SELECT tm.*, 
               CASE WHEN tm.is_admin_reply = 1 THEN 'Support' ELSE u.first_name END as sender_name
        FROM ticket_messages tm
        LEFT JOIN users u ON tm.user_id = u.id
        WHERE tm.ticket_id = ?
        ORDER BY tm.created_at ASC
    ");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $messages_result = $stmt->get_result();
    
    $messages = [];
    while ($row = $messages_result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    // Anhänge abrufen
    $stmt = $db->prepare("
        SELECT ta.*, u.first_name as uploaded_by_name
        FROM ticket_attachments ta
        LEFT JOIN users u ON ta.uploaded_by = u.id
        WHERE ta.ticket_id = ?
        ORDER BY ta.created_at ASC
    ");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $attachments_result = $stmt->get_result();
    
    $attachments = [];
    while ($row = $attachments_result->fetch_assoc()) {
        $attachments[] = $row;
    }
    
    echo json_encode([
        'ticket' => $ticket,
        'messages' => $messages,
        'attachments' => $attachments
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server-Fehler: ' . $e->getMessage()]);
}
?>