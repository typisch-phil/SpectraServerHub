<?php
require_once 'includes/config.php';

try {
    // Check if services table exists
    $stmt = $db->query("SHOW TABLES LIKE 'services'");
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        echo "Services table does not exist!\n";
        exit;
    }
    
    echo "Services table exists.\n";
    
    // Check table structure
    $stmt = $db->query("DESCRIBE services");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    // Check data
    $stmt = $db->query("SELECT COUNT(*) as count FROM services");
    $count = $stmt->fetchColumn();
    echo "\nTotal services: $count\n";
    
    if ($count > 0) {
        $stmt = $db->query("SELECT * FROM services LIMIT 5");
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\nSample services:\n";
        foreach ($services as $service) {
            echo "- {$service['name']} (Type: {$service['type']}, Price: €{$service['price']}, Active: " . ($service['active'] ? 'Yes' : 'No') . ")\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>