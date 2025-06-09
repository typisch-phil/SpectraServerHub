-- SpectraHost Database Schema
CREATE DATABASE IF NOT EXISTS spectrahost CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE spectrahost;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'suspended', 'deleted') DEFAULT 'active'
);

-- Services table
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type ENUM('webhosting', 'vps', 'gameserver', 'domain') NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    cpu_cores INT DEFAULT 1,
    memory_gb INT DEFAULT 1,
    storage_gb INT DEFAULT 10,
    bandwidth_gb INT DEFAULT 1000,
    features JSON,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User services (purchased services)
CREATE TABLE user_services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    server_name VARCHAR(255) NOT NULL,
    proxmox_vmid INT NULL,
    status ENUM('pending', 'active', 'suspended', 'terminated') DEFAULT 'pending',
    expires_at DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- Orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    billing_period ENUM('monthly', 'quarterly', 'yearly') DEFAULT 'monthly',
    status ENUM('pending', 'paid', 'failed', 'cancelled') DEFAULT 'pending',
    payment_id VARCHAR(255) NULL,
    payment_method VARCHAR(50) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- Support tickets table
CREATE TABLE support_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'answered', 'closed') DEFAULT 'open',
    admin_response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default services
INSERT INTO services (name, type, description, price, cpu_cores, memory_gb, storage_gb, bandwidth_gb, features) VALUES
('Web Starter', 'webhosting', 'Perfekt für kleine Websites und Blogs', 4.99, 1, 1, 10, 100, '{"php": "8.2", "mysql": true, "ssl": true, "email": 5}'),
('Web Business', 'webhosting', 'Ideal für Unternehmenswebsites', 9.99, 2, 2, 25, 250, '{"php": "8.2", "mysql": true, "ssl": true, "email": 25}'),
('Web Premium', 'webhosting', 'Für große Websites mit hohem Traffic', 19.99, 4, 4, 50, 500, '{"php": "8.2", "mysql": true, "ssl": true, "email": 100}'),
('VPS Basic', 'vps', 'Virtueller Server für Einsteiger', 14.99, 1, 2, 40, 1000, '{"os": "Ubuntu/CentOS", "root": true, "backup": true}'),
('VPS Standard', 'vps', 'Leistungsstarker VPS für Entwickler', 29.99, 2, 4, 80, 2000, '{"os": "Ubuntu/CentOS", "root": true, "backup": true}'),
('VPS Premium', 'vps', 'High-Performance Server', 59.99, 4, 8, 160, 4000, '{"os": "Ubuntu/CentOS", "root": true, "backup": true}'),
('Minecraft Server', 'gameserver', 'Perfekt für Minecraft Communities', 12.99, 2, 4, 20, 1000, '{"players": 50, "plugins": true, "backup": true}'),
('Domain .de', 'domain', 'Deutsche Domain Registration', 8.99, 0, 0, 0, 0, '{"dns": true, "whois": true, "redirect": true}'),
('Domain .com', 'domain', 'Internationale Domain', 11.99, 0, 0, 0, 0, '{"dns": true, "whois": true, "redirect": true}');

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_user_services_user_id ON user_services(user_id);
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_support_tickets_user_id ON support_tickets(user_id);
CREATE INDEX idx_services_type ON services(type);