<?php
require_once 'includes/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    $schema = file_get_contents('database/extended_schema.sql');
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^\s*--/', $statement)) {
            try {
                $pdo->exec($statement);
                echo "✓ " . substr(trim($statement), 0, 60) . "...\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column name') === false && 
                    strpos($e->getMessage(), 'already exists') === false &&
                    strpos($e->getMessage(), 'Duplicate key name') === false) {
                    echo "⚠ " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "\n✅ Extended database setup completed!\n";
    
} catch (PDOException $e) {
    echo "❌ Database setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>