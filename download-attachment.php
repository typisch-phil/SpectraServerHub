<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/database.php';

// Benutzer-Authentifizierung prüfen
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Parameter validieren
if (!isset($_GET['id']) || !isset($_GET['token'])) {
    http_response_code(400);
    exit('Bad Request');
}

$attachment_id = (int)$_GET['id'];
$token = $_GET['token'];
$user_id = $_SESSION['user_id'];

try {
    $db = Database::getInstance();
    
    // Anhang-Details abrufen und Berechtigung prüfen
    $attachment = $db->fetchOne("
        SELECT a.*, t.user_id as ticket_owner_id
        FROM ticket_attachments a
        JOIN support_tickets t ON a.ticket_id = t.id
        WHERE a.id = ?
    ", [$attachment_id]);
    
    if (!$attachment) {
        http_response_code(404);
        exit('Attachment not found');
    }
    
    // Berechtigung prüfen - nur Ticket-Besitzer oder Admin
    $user = $db->fetchOne("SELECT role FROM users WHERE id = ?", [$user_id]);
    if ($attachment['ticket_owner_id'] != $user_id && $user['role'] !== 'admin') {
        http_response_code(403);
        exit('Access denied');
    }
    
    // Token validieren
    $expected_token = md5($attachment['id'] . $attachment['ticket_id'] . $user_id);
    if ($token !== $expected_token) {
        http_response_code(403);
        exit('Invalid token');
    }
    
    // Dateipfad
    $file_path = __DIR__ . '/' . $attachment['file_path'];
    
    if (!file_exists($file_path)) {
        http_response_code(404);
        exit('File not found');
    }
    
    // Download-Headers setzen
    header('Content-Type: ' . $attachment['mime_type']);
    header('Content-Disposition: attachment; filename="' . $attachment['original_filename'] . '"');
    header('Content-Length: ' . $attachment['file_size']);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Datei ausgeben
    readfile($file_path);
    exit;
    
} catch (Exception $e) {
    error_log("Download error: " . $e->getMessage());
    http_response_code(500);
    exit('Internal Server Error');
}
?>