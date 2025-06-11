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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // POST-Daten validieren
    if (!isset($_POST['ticket_id']) || !isset($_POST['message'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    $ticketId = (int)$_POST['ticket_id'];
    $message = trim($_POST['message']);
    $status = $_POST['status'] ?? '';
    
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Message cannot be empty']);
        exit;
    }
    
    // Ticket existiert prüfen
    $ticket = $db->fetchOne("SELECT id FROM support_tickets WHERE id = ?", [$ticketId]);
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket not found']);
        exit;
    }
    
    // Admin-Nachricht hinzufügen
    $stmt = $db->prepare("
        INSERT INTO ticket_messages (ticket_id, admin_id, message, is_admin_reply, created_at)
        VALUES (?, ?, ?, 1, NOW())
    ");
    $stmt->execute([$ticketId, $_SESSION['user_id'], $message]);
    $messageId = $db->lastInsertId();
    
    // Datei-Upload verarbeiten
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['attachment'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            // Nachricht wieder löschen bei Fehler
            $db->execute("DELETE FROM ticket_messages WHERE id = ?", [$messageId]);
            http_response_code(400);
            echo json_encode(['error' => 'Dateityp nicht erlaubt. Erlaubt sind: JPG, PNG, GIF, PDF, TXT']);
            exit;
        }
        
        if ($file['size'] > $max_size) {
            $db->execute("DELETE FROM ticket_messages WHERE id = ?", [$messageId]);
            http_response_code(400);
            echo json_encode(['error' => 'Datei ist zu groß. Maximum: 5MB']);
            exit;
        }
        
        // Upload-Verzeichnis erstellen
        $upload_dir = __DIR__ . '/../../uploads/tickets/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Eindeutigen Dateinamen generieren
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $unique_filename;
        
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Anhang in Datenbank speichern
            $stmt = $db->prepare("
                INSERT INTO ticket_attachments (ticket_id, original_filename, filename, file_path, file_size, mime_type, uploaded_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $ticketId,
                $file['name'],
                $unique_filename,
                'uploads/tickets/' . $unique_filename,
                $file['size'],
                $file['type'],
                $_SESSION['user_id']
            ]);
        }
    }
    
    // Ticket-Status aktualisieren
    if (!empty($status)) {
        $stmt = $db->prepare("UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $ticketId]);
    } else {
        // Standard: waiting_customer wenn Admin antwortet
        $stmt = $db->prepare("UPDATE support_tickets SET status = 'waiting_customer', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$ticketId]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Reply sent successfully']);
    
} catch (Exception $e) {
    error_log("Admin reply error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>