<?php
require_once 'includes/config.php';

echo "Testing MySQL Connection...\n";

try {
    $host = $_ENV['MYSQL_HOST'] ?? 'localhost';
    $dbname = $_ENV['MYSQL_DATABASE'] ?? 'spectrahost';
    $username = $_ENV['MYSQL_USER'] ?? 'root';
    $password = $_ENV['MYSQL_PASSWORD'] ?? '';
    $port = $_ENV['MYSQL_PORT'] ?? '3306';
    
    echo "Connecting to MySQL:\n";
    echo "Host: $host\n";
    echo "Database: $dbname\n";
    echo "User: $username\n";
    echo "Port: $port\n\n";
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    echo "✓ MySQL connection successful!\n\n";
    
    // Check existing tables
    echo "Checking existing tables:\n";
    $result = $pdo->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "No tables found - will create new schema\n";
    } else {
        echo "Found " . count($tables) . " existing tables:\n";
        foreach ($tables as $table) {
            echo "- $table\n";
        }
    }
    
    echo "\nDatabase ready for SpectraHost!\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>