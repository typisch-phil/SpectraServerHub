<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $this->initializeConnection();
    }
    
    private function initializeConnection() {
        // Try multiple connection methods for Plesk compatibility
        $connectionMethods = [
            $this->getEnvironmentConfig(),
            $this->getConfigFileConfig(),
            $this->getDefaultConfig()
        ];
        
        foreach ($connectionMethods as $config) {
            if ($this->attemptConnection($config)) {
                return;
            }
        }
        
        // If all methods fail, log and throw exception
        error_log("All database connection methods failed");
        throw new Exception('Database connection failed - check configuration');
    }
    
    private function getEnvironmentConfig() {
        return [
            'host' => $_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST'),
            'dbname' => $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE'),
            'username' => $_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER'),
            'password' => $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD'),
            'port' => $_ENV['MYSQL_PORT'] ?? getenv('MYSQL_PORT') ?: '3306'
        ];
    }
    
    private function getConfigFileConfig() {
        return [
            'host' => defined('DB_HOST') ? DB_HOST : 'localhost',
            'dbname' => defined('DB_NAME') ? DB_NAME : 'spectrahost',
            'username' => defined('DB_USER') ? DB_USER : 'root',
            'password' => defined('DB_PASS') ? DB_PASS : '',
            'port' => defined('DB_PORT') ? DB_PORT : '3306'
        ];
    }
    
    private function getDefaultConfig() {
        return [
            'host' => 'localhost',
            'dbname' => 'spectrahost',
            'username' => 'root',
            'password' => '',
            'port' => '3306'
        ];
    }
    
    private function attemptConnection($config) {
        // Skip if required values are missing
        if (empty($config['host']) || empty($config['dbname']) || empty($config['username'])) {
            return false;
        }
        
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::ATTR_TIMEOUT => 5
            ];
            
            $this->connection = new PDO($dsn, $config['username'], $config['password'], $options);
            
            // Test the connection
            $this->connection->query("SELECT 1");
            
            error_log("Database connection successful to {$config['host']}:{$config['port']}/{$config['dbname']}");
            return true;
            
        } catch (PDOException $e) {
            error_log("Connection attempt failed for {$config['host']}: " . $e->getMessage());
            return false;
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
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    private function initializeDatabase() {
        // Check if tables exist using PostgreSQL syntax
        $result = $this->connection->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'users')");
        $exists = $result->fetchColumn();
        if (!$exists) {
            $this->createTables();
            $this->insertDefaultData();
        } else {
            // Database already exists, ensure compatibility
            $this->ensureCompatibility();
        }
    }
    
    private function createTables() {
        // Create tables one by one to avoid conflicts
        $tables = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(100),
                last_name VARCHAR(100),
                role VARCHAR(20) DEFAULT 'user',
                balance DECIMAL(10,2) DEFAULT 0.00,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS services (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(50) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                features JSON,
                active BOOLEAN DEFAULT TRUE,
                cpu_cores INT DEFAULT 0,
                memory_gb INT DEFAULT 0,
                storage_gb INT DEFAULT 0,
                bandwidth_gb INT DEFAULT 0,
                status VARCHAR(50) DEFAULT 'available',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS user_services (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                service_id INT NOT NULL,
                domain VARCHAR(255),
                status VARCHAR(50) DEFAULT 'pending',
                next_payment TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                service_id INT NULL,
                amount DECIMAL(10,2) NOT NULL,
                currency VARCHAR(3) DEFAULT 'EUR',
                payment_method VARCHAR(50),
                payment_id VARCHAR(255),
                status VARCHAR(50) DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS sessions (
                session_id VARCHAR(128) PRIMARY KEY,
                user_id INT NULL,
                data TEXT,
                expires_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS servers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_service_id INT NOT NULL,
                server_name VARCHAR(255),
                ip_address VARCHAR(45),
                proxmox_vmid INT,
                root_password VARCHAR(255),
                status VARCHAR(50) DEFAULT 'creating',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_service_id) REFERENCES user_services(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS tickets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                subject TEXT NOT NULL,
                message TEXT NOT NULL,
                status VARCHAR(50) DEFAULT 'open',
                priority VARCHAR(20) DEFAULT 'medium',
                category VARCHAR(50) DEFAULT 'general',
                assigned_to INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS ticket_replies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ticket_id INT NOT NULL,
                user_id INT NOT NULL,
                message TEXT NOT NULL,
                is_internal BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS ticket_attachments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ticket_id INT NULL,
                reply_id INT NULL,
                filename VARCHAR(255) NOT NULL,
                original_filename VARCHAR(255) NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                file_size INT NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                uploaded_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
                FOREIGN KEY (reply_id) REFERENCES ticket_replies(id) ON DELETE CASCADE,
                FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];
        
        foreach ($tables as $sql) {
            $this->connection->exec($sql);
        }
    }
    
    private function ensureCompatibility() {
        // Ensure compatibility with existing MySQL database structure
        try {
            // Create tickets table if it doesn't exist (since we have support_tickets)
            $result = $this->connection->query("SHOW TABLES LIKE 'tickets'");
            if ($result->rowCount() == 0) {
                $this->connection->exec("
                    CREATE TABLE IF NOT EXISTS tickets (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        subject TEXT NOT NULL,
                        message TEXT NOT NULL,
                        status VARCHAR(50) DEFAULT 'open',
                        priority VARCHAR(20) DEFAULT 'medium',
                        category VARCHAR(50) DEFAULT 'general',
                        assigned_to INT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
            
            // Create ticket_replies table
            $result = $this->connection->query("SHOW TABLES LIKE 'ticket_replies'");
            if ($result->rowCount() == 0) {
                $this->connection->exec("
                    CREATE TABLE IF NOT EXISTS ticket_replies (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        ticket_id INT NOT NULL,
                        user_id INT NOT NULL,
                        message TEXT NOT NULL,
                        is_internal TINYINT(1) DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
            
            // Create ticket_attachments table
            $result = $this->connection->query("SHOW TABLES LIKE 'ticket_attachments'");
            if ($result->rowCount() == 0) {
                $this->connection->exec("
                    CREATE TABLE IF NOT EXISTS ticket_attachments (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        ticket_id INT NULL,
                        reply_id INT NULL,
                        filename VARCHAR(255) NOT NULL,
                        original_filename VARCHAR(255) NOT NULL,
                        file_path VARCHAR(500) NOT NULL,
                        file_size INT NOT NULL,
                        mime_type VARCHAR(100) NOT NULL,
                        uploaded_by INT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
                        FOREIGN KEY (reply_id) REFERENCES ticket_replies(id) ON DELETE CASCADE,
                        FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
            
            // Ensure users table has role column
            $dbname = $_ENV['MYSQL_DATABASE'];
            $result = $this->connection->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$dbname' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'");
            if ($result->rowCount() == 0) {
                $this->connection->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'");
            }
            
            // Ensure users table has is_admin column for compatibility
            $result = $this->connection->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$dbname' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_admin'");
            if ($result->rowCount() == 0) {
                $this->connection->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0");
                // Update existing admin users based on role
                $this->connection->exec("UPDATE users SET is_admin = 1 WHERE role = 'admin'");
            }
            
        } catch (Exception $e) {
            error_log("Database compatibility check failed: " . $e->getMessage());
        }
    }
    
    private function insertDefaultData() {
        // Check if admin user already exists
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute(['admin@spectrahost.de']);
        if ($stmt->fetchColumn() == 0) {
            // Insert admin user
            $adminHash = '$2y$10$cjF8EVV24pF/rRTN6AW8JuXaDEYZC2YOKrK1RdG9KXxQCBcS45QX6';
            $stmt = $this->connection->prepare("INSERT INTO users (email, password, first_name, last_name, role, balance) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute(['admin@spectrahost.de', $adminHash, 'Admin', 'User', 'admin', 100.00]);
        }
        
        // Check if services already exist
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM services");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            // Insert sample services
            $services = [
                ['Starter Webspace', 'webspace', 'Perfekt für kleine Websites und Blogs', 4.99, '{"php": "8.2", "mysql": true, "ssl": true, "domains": 1, "email_accounts": 5}', 1, 1, 10, 100],
                ['Business Webspace', 'webspace', 'Ideal für Unternehmenswebsites', 14.99, '{"php": "8.2", "mysql": true, "ssl": true, "domains": 5, "email_accounts": 25}', 2, 2, 50, 500],
                ['Enterprise Webspace', 'webspace', 'Maximale Leistung für große Projekte', 49.99, '{"php": "8.2", "mysql": true, "ssl": true, "domains": 25, "email_accounts": 100}', 4, 4, 200, 2000],
                ['Basic vServer', 'vserver', 'Virtueller Server für Entwickler', 19.99, '{"os": "Ubuntu 22.04", "root_access": true, "backup": "weekly"}', 2, 4, 50, 1000],
                ['Pro vServer', 'vserver', 'Leistungsstarker Server für Anwendungen', 39.99, '{"os": "Ubuntu 22.04", "root_access": true, "backup": "daily"}', 4, 8, 100, 2000],
                ['Minecraft Server', 'gameserver', 'Optimiert für Minecraft Multiplayer', 24.99, '{"slots": 20, "version": "1.20", "mods": true, "auto_backup": true}', 4, 6, 75, 1000],
                ['Domain .de', 'domain', 'Deutsche Top-Level-Domain', 12.99, '{"whois_privacy": true, "dns_management": true, "email_forwarding": 10}', 0, 0, 0, 0]
            ];
            
            $stmt = $this->connection->prepare("INSERT INTO services (name, type, description, price, features, cpu_cores, memory_gb, storage_gb, bandwidth_gb) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($services as $service) {
                $stmt->execute($service);
            }
        }
    }
}

// Global database instance
$db = Database::getInstance();
?>