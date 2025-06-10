-- SpectraHost Database Schema for MySQL (s9281_spectrahost)
-- ALLE PDO-Verbindungen verwenden ausschließlich MySQL!

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    balance DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'suspended', 'deleted') DEFAULT 'active',
    INDEX idx_users_email (email),
    INDEX idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Service types table (for product categories)
CREATE TABLE IF NOT EXISTS service_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category ENUM('webspace', 'vserver', 'gameserver', 'domain') NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    features JSON,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_service_types_category (category),
    INDEX idx_service_types_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Services table (user purchased services)
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_type_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    status ENUM('pending', 'active', 'suspended', 'terminated') DEFAULT 'pending',
    proxmox_vmid INT NULL,
    server_ip VARCHAR(45) NULL,
    expires_at DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_type_id) REFERENCES service_types(id),
    INDEX idx_services_user_id (user_id),
    INDEX idx_services_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_type_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    billing_period ENUM('monthly', 'quarterly', 'yearly') DEFAULT 'monthly',
    status ENUM('pending', 'paid', 'failed', 'cancelled') DEFAULT 'pending',
    payment_id VARCHAR(255) NULL,
    payment_method VARCHAR(50) NULL,
    mollie_payment_id VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_type_id) REFERENCES service_types(id),
    INDEX idx_orders_user_id (user_id),
    INDEX idx_orders_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Support tickets table
CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'answered', 'closed') DEFAULT 'open',
    admin_response TEXT NULL,
    attachments JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_support_tickets_user_id (user_id),
    INDEX idx_support_tickets_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ticket messages table (for ticket conversations)
CREATE TABLE IF NOT EXISTS ticket_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NULL,
    message TEXT NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    attachments JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ticket_messages_ticket_id (ticket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transactions table (for balance management)
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('deposit', 'withdrawal', 'payment', 'refund') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    reference_id VARCHAR(255) NULL,
    payment_method VARCHAR(50) NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_transactions_user_id (user_id),
    INDEX idx_transactions_type (type),
    INDEX idx_transactions_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default service types (MySQL-only data)
INSERT IGNORE INTO service_types (name, category, description, price, features) VALUES
('Webspace Starter', 'webspace', 'Perfekt für kleine Websites und Blogs', 4.99, '["5 GB SSD Speicher", "1 Domain inklusive", "5 E-Mail Postfächer", "MySQL Datenbanken", "SSL Zertifikat"]'),
('Webspace Business', 'webspace', 'Ideal für Unternehmenswebsites', 9.99, '["25 GB SSD Speicher", "3 Domains inklusive", "25 E-Mail Postfächer", "Unbegrenzte Datenbanken", "Priority Support"]'),
('Webspace Premium', 'webspace', 'Für große Websites mit hohem Traffic', 19.99, '["100 GB SSD Speicher", "10 Domains inklusive", "Unbegrenzte E-Mails", "Erweiterte Sicherheit", "Dedicated Support"]'),
('VPS Basic', 'vserver', 'Virtueller Server für Einsteiger', 14.99, '["2 vCPU Kerne", "4 GB RAM", "50 GB NVMe SSD", "Unlimited Traffic", "Root-Zugriff"]'),
('VPS Pro', 'vserver', 'Leistungsstarker VPS für Entwickler', 29.99, '["4 vCPU Kerne", "8 GB RAM", "100 GB NVMe SSD", "Priority Support", "Managed Services"]'),
('VPS Enterprise', 'vserver', 'High-Performance Server für Unternehmen', 59.99, '["8 vCPU Kerne", "16 GB RAM", "250 GB NVMe SSD", "24/7 Monitoring", "Dedicated Support"]'),
('Minecraft Basic', 'gameserver', 'Für kleine Minecraft Communities', 9.99, '["10 Spieler", "2 GB RAM", "DDoS-Schutz", "Instant Setup", "Plugin Support"]'),
('Minecraft Standard', 'gameserver', 'Perfekt für mittlere Communities', 14.99, '["20 Spieler", "4 GB RAM", "DDoS-Schutz", "Backups", "Mod Support"]'),
('Minecraft Premium', 'gameserver', 'Für große Minecraft Server', 24.99, '["50 Spieler", "8 GB RAM", "DDoS-Schutz", "Priority Support", "Custom Plugins"]'),
('Domain .de', 'domain', 'Deutsche Domain Registration', 8.99, '["DNS Management", "WHOIS-Schutz", "E-Mail Forwarding", "Domain Lock"]'),
('Domain .com', 'domain', 'Internationale Domain', 12.99, '["DNS Management", "WHOIS-Schutz", "E-Mail Forwarding", "Domain Lock"]'),
('Domain .org', 'domain', 'Organisation Domain', 14.99, '["DNS Management", "WHOIS-Schutz", "E-Mail Forwarding", "Domain Lock"]'),
('Domain .net', 'domain', 'Network Domain', 15.99, '["DNS Management", "WHOIS-Schutz", "E-Mail Forwarding", "Domain Lock"]');

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO users (email, password, first_name, last_name, role) VALUES
('admin@spectrahost.de', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'SpectraHost', 'admin');

-- Insert test user (password: test123)
INSERT IGNORE INTO users (email, password, first_name, last_name, role, balance) VALUES
('test@spectrahost.de', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test', 'User', 'customer', 50.00);