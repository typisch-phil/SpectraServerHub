<?php
require_once 'includes/config.php';

try {
    // Connect to MySQL server without database first
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
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