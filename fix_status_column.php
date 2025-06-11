<?php
require_once __DIR__ . '/includes/database.php';

try {
    $db = Database::getInstance();
    
    echo "Überprüfe status Spalte in support_tickets...\n";
    
    // Aktuelle Struktur der status Spalte anzeigen
    $result = $db->fetchAll("SHOW COLUMNS FROM support_tickets LIKE 'status'");
    if (!empty($result)) {
        echo "Aktuelle status Spalte: " . $result[0]['Type'] . "\n";
        
        // Status Spalte erweitern um längere Werte zu unterstützen
        $db->execute("ALTER TABLE support_tickets MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'open'");
        echo "Status Spalte auf VARCHAR(50) erweitert.\n";
        
        // Neue Struktur anzeigen
        $result = $db->fetchAll("SHOW COLUMNS FROM support_tickets LIKE 'status'");
        echo "Neue status Spalte: " . $result[0]['Type'] . "\n";
    } else {
        echo "Status Spalte nicht gefunden.\n";
    }
    
    echo "Status Spalten-Reparatur abgeschlossen.\n";
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
}
?>