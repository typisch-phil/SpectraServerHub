<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            // Use PostgreSQL with MySQL syntax emulation
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            // Initialize database if it doesn't exist
            $this->initializeDatabase();
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die('Datenbankverbindung fehlgeschlagen');
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
        // Check if tables exist in PostgreSQL
        $result = $this->connection->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'users')");
        $exists = $result->fetchColumn();
        if (!$exists) {
            $this->createTables();
            $this->insertDefaultData();
        } else {
            $this->updateSchema();
        }
    }
    
    private function createTables() {
        // Create tables one by one to avoid conflicts
        $tables = [
            "CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(100),
                last_name VARCHAR(100),
                role VARCHAR(20) DEFAULT 'user',
                balance DECIMAL(10,2) DEFAULT 0.00,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS services (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(50) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                features JSONB,
                active BOOLEAN DEFAULT true,
                cpu_cores INTEGER DEFAULT 0,
                memory_gb INTEGER DEFAULT 0,
                storage_gb INTEGER DEFAULT 0,
                bandwidth_gb INTEGER DEFAULT 0,
                status VARCHAR(50) DEFAULT 'available',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS user_services (
                id SERIAL PRIMARY KEY,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                service_id INTEGER NOT NULL REFERENCES services(id) ON DELETE CASCADE,
                domain VARCHAR(255),
                status VARCHAR(50) DEFAULT 'pending',
                next_payment TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS payments (
                id SERIAL PRIMARY KEY,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                service_id INTEGER REFERENCES services(id) ON DELETE SET NULL,
                amount DECIMAL(10,2) NOT NULL,
                currency VARCHAR(3) DEFAULT 'EUR',
                payment_method VARCHAR(50),
                payment_id VARCHAR(255),
                status VARCHAR(50) DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS sessions (
                session_id VARCHAR(128) PRIMARY KEY,
                user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
                data TEXT,
                expires_at TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS servers (
                id SERIAL PRIMARY KEY,
                user_service_id INTEGER NOT NULL REFERENCES user_services(id) ON DELETE CASCADE,
                server_name VARCHAR(255),
                ip_address VARCHAR(45),
                proxmox_vmid INTEGER,
                root_password VARCHAR(255),
                status VARCHAR(50) DEFAULT 'creating',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS tickets (
                id SERIAL PRIMARY KEY,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                subject TEXT NOT NULL,
                message TEXT NOT NULL,
                status VARCHAR(50) DEFAULT 'open',
                priority VARCHAR(20) DEFAULT 'medium',
                category VARCHAR(50) DEFAULT 'general',
                assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS ticket_replies (
                id SERIAL PRIMARY KEY,
                ticket_id INTEGER NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                message TEXT NOT NULL,
                is_internal BOOLEAN DEFAULT false,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS ticket_attachments (
                id SERIAL PRIMARY KEY,
                ticket_id INTEGER REFERENCES tickets(id) ON DELETE CASCADE,
                reply_id INTEGER REFERENCES ticket_replies(id) ON DELETE CASCADE,
                filename VARCHAR(255) NOT NULL,
                original_filename VARCHAR(255) NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                file_size INTEGER NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                uploaded_by INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        ];
        
        foreach ($tables as $sql) {
            $this->connection->exec($sql);
        }
    }
    
    private function updateSchema() {
        // Check if assigned_to column exists in tickets table
        $result = $this->connection->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'tickets' AND column_name = 'assigned_to'");
        if ($result->rowCount() == 0) {
            $this->connection->exec("ALTER TABLE tickets ADD COLUMN assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL");
        }
        
        // Check if category column exists in tickets table
        $result = $this->connection->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'tickets' AND column_name = 'category'");
        if ($result->rowCount() == 0) {
            $this->connection->exec("ALTER TABLE tickets ADD COLUMN category VARCHAR(50) DEFAULT 'general'");
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