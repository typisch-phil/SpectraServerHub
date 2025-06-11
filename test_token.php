<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/database.php';

// Simuliere eingeloggte Session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Test-User ID
}

try {
    $db = Database::getInstance();
    
    // Hol den neuesten Anhang
    $attachment = $db->fetchOne("
        SELECT a.*, t.user_id as ticket_owner_id
        FROM ticket_attachments a
        JOIN support_tickets t ON a.ticket_id = t.id
        ORDER BY a.created_at DESC LIMIT 1
    ");
    
    if ($attachment) {
        $user_id = $_SESSION['user_id'];
        
        echo "=== Token Test ===\n";
        echo "Anhang ID: {$attachment['id']}\n";
        echo "Ticket ID: {$attachment['ticket_id']}\n";
        echo "User ID: $user_id\n";
        echo "Ticket Owner ID: {$attachment['ticket_owner_id']}\n";
        
        $token = md5($attachment['id'] . $attachment['ticket_id'] . $user_id);
        echo "Generierter Token: $token\n";
        
        $download_url = "/download-attachment.php?id={$attachment['id']}&token=$token";
        echo "Download URL: $download_url\n";
        
        echo "\nDatei-Info:\n";
        echo "Original: {$attachment['original_filename']}\n";
        echo "Pfad: {$attachment['file_path']}\n";
        
        $full_path = __DIR__ . '/' . $attachment['file_path'];
        echo "Vollständiger Pfad: $full_path\n";
        echo "Datei existiert: " . (file_exists($full_path) ? 'JA' : 'NEIN') . "\n";
        
    } else {
        echo "Keine Anhänge gefunden\n";
    }
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
}
?>