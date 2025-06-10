<?php
session_start();
require_once '../includes/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Ticket ID erforderlich']);
    exit;
}

$ticket_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    $db = Database::getInstance();
    
    // Ticket-Details abrufen
    $ticket = $db->fetchOne("
        SELECT t.*, u.first_name, u.last_name, u.email
        FROM support_tickets t
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ? AND t.user_id = ?
    ", [$ticket_id, $user_id]);
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket nicht gefunden']);
        exit;
    }
    
    // Nachrichten abrufen
    $messages = $db->fetchAll("
        SELECT m.*, u.first_name, u.last_name
        FROM support_messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.ticket_id = ?
        ORDER BY m.created_at ASC
    ", [$ticket_id]);
    
    echo json_encode([
        'ticket' => $ticket,
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    error_log("Ticket details error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Serverfehler']);
}
?>