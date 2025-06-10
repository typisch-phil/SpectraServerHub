<?php
require_once 'includes/config.php';

try {
    // Use centralized database connection
    require_once 'includes/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '" . DB_NAME . "' created or exists.\n";
    
    // Use the database
    $pdo->exec("USE `" . DB_NAME . "`");
    
    // Read and execute schema
    $schema = file_get_contents('database/schema.sql');
    
    // Remove database creation commands since we already did that
    $schema = preg_replace('/CREATE DATABASE.*?;/', '', $schema);
    $schema = preg_replace('/USE .*?;/', '', $schema);
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                echo "Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "Database setup completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Database setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>