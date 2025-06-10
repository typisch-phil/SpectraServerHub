<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $this->initializeConnection();
    }
    
    private function initializeConnection() {
        // Zentrale MySQL-Verbindung
        $config = [
            'host' => $_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST') ?? '37.114.32.205',
            'dbname' => $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? 's9281_spectrahost',
            'username' => $_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER') ?? 's9281_spectrahost',
            'password' => $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?? '',
            'port' => $_ENV['MYSQL_PORT'] ?? getenv('MYSQL_PORT') ?? '3306'
        ];
        
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
            $this->connection = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
            
            error_log("Database connection successful to {$config['host']}:{$config['port']}/{$config['dbname']}");
        } catch (PDOException $e) {
            error_log("MySQL connection failed: " . $e->getMessage());
            throw new Exception('MySQL database connection failed: ' . $e->getMessage());
        }
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