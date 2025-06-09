-- SpectraHost MySQL Database Schema

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    role ENUM('user', 'admin') DEFAULT 'user',
    balance DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Services table
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('webspace', 'vserver', 'gameserver', 'domain') NOT NULL,
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
);

-- User services (customer orders)
CREATE TABLE user_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    domain VARCHAR(255),
    status ENUM('active', 'pending', 'suspended', 'terminated') DEFAULT 'pending',
    next_payment TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EUR',
    payment_method VARCHAR(50),
    payment_id VARCHAR(255),
    status ENUM('pending', 'paid', 'failed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
);

-- Sessions table (for session storage)
CREATE TABLE sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    data TEXT,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Server management table
CREATE TABLE servers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_service_id INT NOT NULL,
    server_name VARCHAR(255),
    ip_address VARCHAR(45),
    proxmox_vmid INT,
    root_password VARCHAR(255),
    status ENUM('creating', 'running', 'stopped', 'suspended') DEFAULT 'creating',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_service_id) REFERENCES user_services(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (email, password, first_name, last_name, role, balance) VALUES 
('admin@spectrahost.de', '$2y$10$cjF8EVV24pF/rRTN6AW8JuXaDEYZC2YOKrK1RdG9KXxQCBcS45QX6', 'Admin', 'User', 'admin', 100.00);

-- Insert sample services
INSERT INTO services (name, type, description, price, features, cpu_cores, memory_gb, storage_gb, bandwidth_gb) VALUES 
('Starter Webspace', 'webspace', 'Perfekt für kleine Websites und Blogs', 4.99, '{"php": "8.2", "mysql": true, "ssl": true, "domains": 1, "email_accounts": 5}', 1, 1, 10, 100),
('Business Webspace', 'webspace', 'Ideal für Unternehmenswebsites', 14.99, '{"php": "8.2", "mysql": true, "ssl": true, "domains": 5, "email_accounts": 25}', 2, 2, 50, 500),
('Enterprise Webspace', 'webspace', 'Maximale Leistung für große Projekte', 49.99, '{"php": "8.2", "mysql": true, "ssl": true, "domains": 25, "email_accounts": 100}', 4, 4, 200, 2000),
('Basic vServer', 'vserver', 'Virtueller Server für Entwickler', 19.99, '{"os": "Ubuntu 22.04", "root_access": true, "backup": "weekly"}', 2, 4, 50, 1000),
('Pro vServer', 'vserver', 'Leistungsstarker Server für Anwendungen', 39.99, '{"os": "Ubuntu 22.04", "root_access": true, "backup": "daily"}', 4, 8, 100, 2000),
('Minecraft Server', 'gameserver', 'Optimiert für Minecraft Multiplayer', 24.99, '{"slots": 20, "version": "1.20", "mods": true, "auto_backup": true}', 4, 6, 75, 1000),
('Domain .de', 'domain', 'Deutsche Top-Level-Domain', 12.99, '{"whois_privacy": true, "dns_management": true, "email_forwarding": 10}', 0, 0, 0, 0);