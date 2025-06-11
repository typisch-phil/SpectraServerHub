-- Tabellen für das VPS-Bestellsystem

-- Orders Tabelle für Bestellungen
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_type_id INT NOT NULL,
    status ENUM('pending', 'active', 'failed', 'cancelled') DEFAULT 'pending',
    vmid INT NULL,
    hostname VARCHAR(255) NOT NULL,
    specifications JSON NULL,
    proxmox_vmid INT NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_vmid (vmid)
);

-- Services Tabelle für aktive Services
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_type_id INT NOT NULL,
    order_id INT NOT NULL,
    status ENUM('active', 'suspended', 'terminated') DEFAULT 'active',
    vmid INT NOT NULL,
    hostname VARCHAR(255) NOT NULL,
    root_password VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_vmid (vmid),
    INDEX idx_status (status),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Service Types Tabelle erweitern falls nicht vorhanden
CREATE TABLE IF NOT EXISTS service_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category ENUM('webspace', 'vserver', 'gameserver', 'domain') NOT NULL,
    description TEXT NULL,
    features JSON NULL,
    specifications JSON NULL,
    monthly_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    setup_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_status (status)
);

-- Beispiel VPS Pakete einfügen
INSERT INTO service_types (name, category, description, features, specifications, monthly_price) VALUES
('VPS Starter', 'vserver', 'Ideal für Einsteiger und kleine Projekte', 
 '["2 GB RAM", "2 vCPU Cores", "25 GB SSD", "1 Gbit/s Anbindung", "DDoS-Schutz"]',
 '{"memory": 2048, "cores": 2, "disk": 25}', 9.99),
 
('VPS Professional', 'vserver', 'Für anspruchsvolle Projekte und Unternehmen',
 '["4 GB RAM", "4 vCPU Cores", "50 GB SSD", "1 Gbit/s Anbindung", "DDoS-Schutz", "Priority Support"]',
 '{"memory": 4096, "cores": 4, "disk": 50}', 19.99),
 
('VPS Enterprise', 'vserver', 'Maximale Performance für große Anwendungen',
 '["8 GB RAM", "6 vCPU Cores", "100 GB SSD", "1 Gbit/s Anbindung", "DDoS-Schutz", "Managed Support"]',
 '{"memory": 8192, "cores": 6, "disk": 100}', 39.99)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;