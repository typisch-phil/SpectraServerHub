<?php
header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/database.php';

// Authentifizierung prüfen
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}

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
    
    // Prüfen ob Ticket dem Benutzer gehört
    $ticket = $db->fetchOne("
        SELECT id, status FROM support_tickets 
        WHERE id = ? AND user_id = ?
    ", [$ticket_id, $user_id]);
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket nicht gefunden']);
        exit;
    }
    
    // Status aktualisieren
    $stmt = $db->prepare("
        UPDATE support_tickets 
        SET status = ?, updated_at = NOW() 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$status, $ticket_id, $user_id]);
    
    // Bei Schließung: Closed-Zeitstempel setzen
    if ($status === 'closed') {
        $stmt = $db->prepare("
            UPDATE support_tickets 
            SET closed_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$ticket_id, $user_id]);
        
        // System-Nachricht hinzufügen
        $stmt = $db->prepare("
            INSERT INTO ticket_messages (ticket_id, user_id, message, is_staff, created_at) 
            VALUES (?, ?, ?, 0, NOW())
        ");
        $stmt->execute([
            $ticket_id, 
            $user_id, 
            'Ticket wurde vom Kunden geschlossen.'
        ]);
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log('Update Ticket Status Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Interner Server-Fehler']);
}
?>