<?php
require_once 'includes/config.php';

try {
    // Use centralized database connection
    require_once 'includes/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
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