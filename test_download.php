<?php
require_once __DIR__ . '/includes/database.php';

try {
    $db = Database::getInstance();
    
    echo "=== Download-System Diagnose ===\n";
    
    // Upload-Verzeichnis prüfen
    $upload_dir = __DIR__ . '/uploads/tickets/';
    echo "Upload-Verzeichnis: $upload_dir\n";
    echo "Verzeichnis existiert: " . (is_dir($upload_dir) ? 'JA' : 'NEIN') . "\n";
    
    if (is_dir($upload_dir)) {
        $files = scandir($upload_dir);
        echo "Dateien im Verzeichnis: " . count($files) . "\n";
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "  - $file\n";
            }
        }
    }
    
    // Anhänge in Datenbank prüfen
    $attachments = $db->fetchAll("SELECT * FROM ticket_attachments ORDER BY created_at DESC LIMIT 5");
    echo "\nAnhänge in Datenbank: " . count($attachments) . "\n";
    
    foreach ($attachments as $attachment) {
        echo "\nAnhang ID: {$attachment['id']}\n";
        echo "  Original: {$attachment['original_filename']}\n";
        echo "  Gespeichert: {$attachment['filename']}\n";
        echo "  Pfad: {$attachment['file_path']}\n";
        
        $full_path = __DIR__ . '/' . $attachment['file_path'];
        echo "  Vollständiger Pfad: $full_path\n";
        echo "  Datei existiert: " . (file_exists($full_path) ? 'JA' : 'NEIN') . "\n";
        
        if (file_exists($full_path)) {
            echo "  Dateigröße: " . filesize($full_path) . " bytes\n";
            echo "  Berechtigung: " . substr(sprintf('%o', fileperms($full_path)), -4) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
}
?>