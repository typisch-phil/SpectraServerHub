<?php
require_once __DIR__ . '/includes/database.php';

try {
    $db = Database::getInstance();
    
    // Überprüfen ob Tabelle existiert
    $result = $db->query("SHOW TABLES LIKE 'ticket_attachments'");
    if ($result->rowCount() == 0) {
        // Tabelle erstellen
        $sql = "
        CREATE TABLE ticket_attachments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            stored_filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            uploaded_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ticket_id (ticket_id),
            FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        echo "Tabelle ticket_attachments wurde erfolgreich erstellt.\n";
    } else {
        echo "Tabelle ticket_attachments existiert bereits.\n";
        
        // Struktur anzeigen
        $structure = $db->query("DESCRIBE ticket_attachments")->fetchAll();
        echo "Tabellenstruktur:\n";
        foreach ($structure as $column) {
            echo "- {$column['Field']}: {$column['Type']}\n";
        }
    }
    
    // Upload-Verzeichnis erstellen
    $upload_dir = __DIR__ . '/uploads/tickets/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
        echo "Upload-Verzeichnis erstellt: $upload_dir\n";
    } else {
        echo "Upload-Verzeichnis existiert bereits: $upload_dir\n";
    }
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
}
?>