<?php
require_once 'includes/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Create integrations table
    $sql = "CREATE TABLE IF NOT EXISTS integrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        config JSON,
        status ENUM('disabled', 'configured', 'active', 'error') DEFAULT 'disabled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $connection->exec($sql);
    echo "Integrations table created successfully.\n";
    
    // Insert default entries
    $stmt = $connection->prepare("INSERT IGNORE INTO integrations (name, status) VALUES (?, 'disabled')");
    $stmt->execute(['mollie']);
    $stmt->execute(['proxmox']);
    
    echo "Default integration entries created.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>