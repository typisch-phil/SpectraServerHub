<?php
require_once __DIR__ . '/config.php';

// Zeitzone auf Berlin setzen
date_default_timezone_set('Europe/Berlin');

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $this->initializeConnection();
    }
    
    private function initializeConnection() {
        // s9281_spectrahost MySQL-Verbindung (AUSSCHLIESSLICH MySQL!)
        $config = [
            'host' => '37.114.32.205',
            'dbname' => 's9281_spectrahost', 
            'username' => 's9281_spectrahost',
            'password' => getenv('MYSQL_PASSWORD') ?: '',
            'port' => '3306'
        ];
        
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
            $this->connection = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 10,
                PDO::ATTR_PERSISTENT => false
            ]);
            
            // MySQL Zeitzone auf Berlin setzen (automatisch MEZ/MESZ)
            $berlinTime = new DateTime('now', new DateTimeZone('Europe/Berlin'));
            $offset = $berlinTime->format('P');
            $this->connection->exec("SET time_zone = '$offset'");
            
            // Set charset explicitly for MySQL
            $this->connection->exec("SET NAMES utf8mb4");
            $this->connection->exec("SET CHARACTER SET utf8mb4");
            $this->connection->exec("SET time_zone = '+00:00'");
            
            error_log("s9281_spectrahost MySQL connection successful to {$config['host']}:{$config['port']}/{$config['dbname']}");
        } catch (PDOException $e) {
            error_log("s9281_spectrahost MySQL connection failed: " . $e->getMessage());
            throw new Exception('s9281_spectrahost MySQL database connection failed: ' . $e->getMessage());
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
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
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