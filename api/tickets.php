<?php
session_start();
require_once '../includes/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

try {
    $db = Database::getInstance();
    $user_id = $_SESSION['user_id'];
    
    $tickets = $db->fetchAll("
        SELECT t.*, 
               (SELECT COUNT(*) FROM support_messages sm WHERE sm.ticket_id = t.id) as message_count
        FROM support_tickets t 
        WHERE t.user_id = ? 
        ORDER BY t.created_at DESC
    ", [$user_id]);
    
    echo json_encode(['tickets' => $tickets]);
    
} catch (Exception $e) {
    error_log("Tickets API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Serverfehler']);
}
?>