<?php
// Session mit korrektem Namen starten
if (session_status() == PHP_SESSION_NONE) {
    session_name('SPECTRAHOST_SESSION');
    session_start();
}

require_once __DIR__ . '/../../includes/database.php';

// Debug Session
error_log("Download Session Debug - Session ID: " . session_id());
error_log("Download Session Debug - User ID: " . ($_SESSION['user_id'] ?? 'nicht gesetzt'));

// Benutzer-Authentifizierung prüfen
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized - Please login');
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Bad Request');
}

$attachment_id = (int)$_GET['id'];

try {
    $db = Database::getInstance();
    
    // Anhang-Details abrufen
    $attachment = $db->fetchOne("
        SELECT * FROM ticket_attachments WHERE id = ?
    ", [$attachment_id]);
    
    if (!$attachment) {
        http_response_code(404);
        exit('Attachment not found');
    }
    
    // Dateipfad
    $file_path = __DIR__ . '/../../' . $attachment['file_path'];
    
    if (!file_exists($file_path)) {
        http_response_code(404);
        exit('File not found on disk');
    }
    
    // Content-Type setzen
    $mime_type = $attachment['mime_type'] ?: 'application/octet-stream';
    
    // Download-Headers setzen
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $attachment['original_filename'] . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Datei-Ausgabe
    readfile($file_path);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    exit('Server Error: ' . $e->getMessage());
}
?>