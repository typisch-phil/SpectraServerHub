<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/database.php';

// Authentifizierung prüfen
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Nur POST-Requests akzeptieren
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /dashboard/support');
    exit;
}

try {
    $ticket_id = $_POST['ticket_id'] ?? null;
    $message = trim($_POST['message'] ?? '');
    
    if (!$ticket_id || empty($message)) {
        $_SESSION['error'] = 'Ticket ID und Nachricht sind erforderlich';
        header('Location: /dashboard/support/ticket-view?id=' . $ticket_id);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Prüfen ob Ticket dem Benutzer gehört und offen ist
    $ticket = $db->fetchOne("
        SELECT id, status FROM support_tickets 
        WHERE id = ? AND user_id = ?
    ", [$ticket_id, $_SESSION['user_id']]);
    
    if (!$ticket) {
        $_SESSION['error'] = 'Ticket nicht gefunden';
        header('Location: /dashboard/support');
        exit;
    }
    
    if ($ticket['status'] === 'closed') {
        $_SESSION['error'] = 'Geschlossene Tickets können nicht beantwortet werden';
        header('Location: /dashboard/support/ticket-view?id=' . $ticket_id);
        exit;
    }
    
    // Nachricht hinzufügen
    $stmt = $db->prepare("
        INSERT INTO ticket_messages (ticket_id, user_id, message, is_staff, created_at) 
        VALUES (?, ?, ?, 0, NOW())
    ");
    $stmt->execute([$ticket_id, $_SESSION['user_id'], $message]);
    
    // Ticket-Status auf "waiting_customer" setzen wenn es vom Support bearbeitet wurde
    if ($ticket['status'] === 'in_progress') {
        $stmt = $db->prepare("
            UPDATE support_tickets 
            SET status = 'waiting_customer', updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$ticket_id]);
    } else {
        // Andernfalls nur updated_at aktualisieren
        $stmt = $db->prepare("
            UPDATE support_tickets 
            SET updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$ticket_id]);
    }
    
    $_SESSION['success'] = 'Ihre Antwort wurde hinzugefügt';
    header('Location: /dashboard/support/ticket-view?id=' . $ticket_id);
    
} catch (Exception $e) {
    error_log('Add Ticket Reply Error: ' . $e->getMessage());
    $_SESSION['error'] = 'Fehler beim Hinzufügen der Antwort';
    header('Location: /dashboard/support/ticket-view?id=' . ($ticket_id ?? ''));
}
?>