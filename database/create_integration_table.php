<?php
require_once __DIR__ . '/../includes/config.php';

try {
    // Create integration_configs table
    $sql = "CREATE TABLE IF NOT EXISTS integration_configs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        integration_name VARCHAR(50) NOT NULL,
        config_key VARCHAR(100) NOT NULL,
        config_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_integration_config (integration_name, config_key)
    )";
    
    $db->exec($sql);
    echo "Integration configs table created successfully.\n";
    
    // Create balance_transactions table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS balance_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        type ENUM('credit', 'debit') NOT NULL,
        description TEXT,
        payment_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at)
    )";
    
    $db->exec($sql);
    echo "Balance transactions table created successfully.\n";
    
    // Add columns to payments table if not exists
    $columns_to_add = [
        "ADD COLUMN mollie_payment_id VARCHAR(255) NULL AFTER id",
        "ADD COLUMN type VARCHAR(50) DEFAULT 'service_payment' AFTER payment_method",
        "ADD INDEX idx_mollie_payment_id (mollie_payment_id)"
    ];
    
    foreach ($columns_to_add as $column) {
        try {
            $db->exec("ALTER TABLE payments $column");
            echo "Added: $column\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') === false && 
                strpos($e->getMessage(), 'Duplicate key name') === false) {
                echo "Error adding $column: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "Database setup completed successfully.\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>