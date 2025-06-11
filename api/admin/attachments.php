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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_GET['ticket_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing ticket_id']);
    exit;
}

$ticketId = (int)$_GET['ticket_id'];

try {
    // Anhänge für das Ticket laden
    $attachments = $db->fetchAll("
        SELECT a.*, 
               CONCAT(u.first_name, ' ', u.last_name) as uploaded_by_name
        FROM ticket_attachments a
        LEFT JOIN users u ON a.uploaded_by = u.id
        WHERE a.ticket_id = ?
        ORDER BY a.created_at ASC
    ", [$ticketId]);
    
    echo json_encode(['attachments' => $attachments]);
    
} catch (Exception $e) {
    error_log("Admin attachments error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>