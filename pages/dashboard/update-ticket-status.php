<?php
header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/database.php';

// Basis-Authentifizierung über Session oder direkte User-ID
$authenticated = isset($_SESSION['user_id']) || isset($_POST['user_id']);

// Nur POST-Requests akzeptieren
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $ticket_id = $_POST['ticket_id'] ?? null;
    $status = $_POST['status'] ?? null;
    $user_id = $_POST['user_id'] ?? $_SESSION['user_id'] ?? null;
    
    if (!$ticket_id || !$status || !$user_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Ticket ID, Status und User ID erforderlich']);
        exit;
    }
    
    // Gültige Status prüfen
    $validStatuses = ['open', 'in_progress', 'waiting_customer', 'closed'];
    if (!in_array($status, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Ungültiger Status']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Ticket suchen (zunächst ohne User-Einschränkung für Entwicklung)
    $ticket = $db->fetchOne("
        SELECT id, status, user_id FROM support_tickets 
        WHERE id = ?
    ", [$ticket_id]);
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket nicht gefunden']);
        exit;
    }
    
    // Sicherheitsprüfung: Ticket gehört dem Benutzer
    if ($ticket['user_id'] != $user_id) {
        http_response_code(403);
        echo json_encode(['error' => 'Zugriff verweigert']);
        exit;
    }
    
    // Status aktualisieren
    $stmt = $db->prepare("
        UPDATE support_tickets 
        SET status = ?, updated_at = NOW() 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$status, $ticket_id, $user_id]);
    
    // Erfolgreiche Aktualisierung protokollieren
    error_log("Ticket {$ticket_id} Status zu '{$status}' geändert von User {$user_id}");
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log('Update Ticket Status Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Interner Server-Fehler']);
}
?>