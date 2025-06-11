<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/database.php';

// Benutzer-Authentifizierung prüfen
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: /dashboard/support');
    exit;
}

$attachment_id = (int)$_GET['id'];

try {
    $db = Database::getInstance();
    
    // Anhang-Details abrufen
    $attachment = $db->fetchOne("
        SELECT * FROM ticket_attachments WHERE id = ?
    ", [$attachment_id]);
    
    if (!$attachment) {
        header('Location: /dashboard/support');
        exit;
    }
    
    // Dateipfad
    $file_path = __DIR__ . '/' . $attachment['file_path'];
    
    if (!file_exists($file_path)) {
        echo "Datei nicht gefunden: " . htmlspecialchars($attachment['original_filename']);
        exit;
    }
    
    // Content-Type basierend auf Dateierweiterung setzen
    $mime_type = $attachment['mime_type'];
    if (empty($mime_type)) {
        $ext = strtolower(pathinfo($attachment['original_filename'], PATHINFO_EXTENSION));
        $mime_types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg', 
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain'
        ];
        $mime_type = $mime_types[$ext] ?? 'application/octet-stream';
    }
    
    // Headers für Download setzen
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $attachment['original_filename'] . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Datei ausgeben
    readfile($file_path);
    exit;
    
} catch (Exception $e) {
    echo "Fehler beim Laden der Datei: " . htmlspecialchars($e->getMessage());
    exit;
}
?>