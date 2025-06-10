<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $this->initializeConnection();
    }
    
    private function initializeConnection() {
        // Zentrale MySQL-Verbindung mit Fallback auf lokale Verbindung
        $config = [
            'host' => $_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST') ?? '37.114.32.205',
            'dbname' => $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? 's9281_spectrahost',
            'username' => $_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER') ?? 's9281_spectrahost',
            'password' => $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?? '',
            'port' => $_ENV['MYSQL_PORT'] ?? getenv('MYSQL_PORT') ?? '3306'
        ];
        
        // Try remote connection first, then localhost fallback
        $connectionAttempts = [
            [
                'host' => $config['host'],
                'port' => $config['port'],
                'dbname' => $config['dbname'],
                'username' => $config['username'],
                'password' => $config['password']
            ],
            [
                'host' => 'localhost',
                'port' => '3306',
                'dbname' => $config['dbname'],
                'username' => $config['username'],
                'password' => $config['password']
            ]
        ];
        
        $lastError = null;
        
        foreach ($connectionAttempts as $attempt) {
            try {
                $dsn = "mysql:host={$attempt['host']};port={$attempt['port']};dbname={$attempt['dbname']};charset=utf8mb4";
                $this->connection = new PDO($dsn, $attempt['username'], $attempt['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5
                ]);
                
                // Set charset explicitly for MySQL
                $this->connection->exec("SET NAMES utf8mb4");
                
                error_log("Database connection successful to {$attempt['host']}:{$attempt['port']}/{$attempt['dbname']}");
                return; // Connection successful, exit loop
                
            } catch (PDOException $e) {
                $lastError = $e;
                error_log("MySQL connection attempt failed for {$attempt['host']}: " . $e->getMessage());
                continue; // Try next connection
            }
        }
        
        // If all attempts failed, throw the last error
        throw new Exception('MySQL database connection failed: ' . ($lastError ? $lastError->getMessage() : 'Unknown error'));
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    public function execute($sql, $params = []) {
        return $this->query($sql, $params)->rowCount();
    }
}

// Globale Instanz für Rückwärtskompatibilität
function getDatabase() {
    return Database::getInstance();
}

function getMySQLConnection() {
    return Database::getInstance()->getConnection();
}
?>