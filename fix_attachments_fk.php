<?php
require_once __DIR__ . '/includes/database.php';

try {
    $db = Database::getInstance();
    
    echo "Repariere Foreign Key Constraints für ticket_attachments...\n";
    
    // Foreign Key Constraint entfernen
    try {
        $db->execute("ALTER TABLE ticket_attachments DROP FOREIGN KEY ticket_attachments_ibfk_1");
        echo "Alte Foreign Key Constraint entfernt.\n";
    } catch (Exception $e) {
        echo "Warnung beim Entfernen der alten Constraint: " . $e->getMessage() . "\n";
    }
    
    // Neue Foreign Key Constraint zu support_tickets hinzufügen
    try {
        $db->execute("ALTER TABLE ticket_attachments ADD CONSTRAINT ticket_attachments_support_fk 
                   FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE");
        echo "Neue Foreign Key Constraint zu support_tickets hinzugefügt.\n";
    } catch (Exception $e) {
        echo "Fehler beim Hinzufügen der neuen Constraint: " . $e->getMessage() . "\n";
        
        // Alternative: Constraint komplett entfernen falls support_tickets Struktur nicht passt
        echo "Entferne alle Foreign Key Constraints...\n";
        try {
            // Alle Constraints für ticket_attachments anzeigen
            $constraints = $db->fetchAll("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = 's9281_spectrahost' 
                AND TABLE_NAME = 'ticket_attachments' 
                AND CONSTRAINT_NAME != 'PRIMARY'
            ");
            
            foreach ($constraints as $constraint) {
                try {
                    $db->execute("ALTER TABLE ticket_attachments DROP FOREIGN KEY " . $constraint['CONSTRAINT_NAME']);
                    echo "Constraint {$constraint['CONSTRAINT_NAME']} entfernt.\n";
                } catch (Exception $e2) {
                    echo "Fehler beim Entfernen von {$constraint['CONSTRAINT_NAME']}: " . $e2->getMessage() . "\n";
                }
            }
        } catch (Exception $e3) {
            echo "Fehler beim Auflisten der Constraints: " . $e3->getMessage() . "\n";
        }
    }
    
    echo "Foreign Key Reparatur abgeschlossen.\n";
    
} catch (Exception $e) {
    echo "Hauptfehler: " . $e->getMessage() . "\n";
}
?>