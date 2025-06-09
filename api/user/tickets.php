<?php
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'User not found']);
    exit;
}

try {
    // Get user's tickets
    $stmt = $pdo->prepare("
        SELECT 
            id,
            subject,
            status,
            priority,
            created_at,
            updated_at,
            CASE 
                WHEN status IN ('open', 'waiting_customer') THEN 1 
                ELSE 0 
            END as is_open
        FROM tickets 
        WHERE user_id = ? 
        ORDER BY 
            is_open DESC,
            created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get counts for statistics
    $open_tickets = array_filter($tickets, function($ticket) {
        return $ticket['status'] === 'open' || $ticket['status'] === 'waiting_customer';
    });

    echo json_encode([
        'success' => true,
        'tickets' => $tickets,
        'open_count' => count($open_tickets),
        'total_count' => count($tickets)
    ]);

} catch (Exception $e) {
    error_log("Error in user tickets API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>