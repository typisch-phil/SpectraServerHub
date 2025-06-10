<?php
require_once 'includes/config.php';

try {
    // Create integrations table
    $sql = "CREATE TABLE IF NOT EXISTS integrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        config JSON,
        status ENUM('disabled', 'configured', 'active', 'error') DEFAULT 'disabled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $db->exec($sql);
    echo "Integrations table created successfully.\n";
    
    // Insert default entries
    $stmt = $db->prepare("INSERT IGNORE INTO integrations (name, status) VALUES (?, 'disabled')");
    $stmt->execute(['mollie']);
    $stmt->execute(['proxmox']);
    
    echo "Default integration entries created.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>