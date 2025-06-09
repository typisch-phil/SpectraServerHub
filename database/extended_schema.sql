-- Extended SpectraHost Database Schema

-- Add missing columns to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS phone VARCHAR(20),
ADD COLUMN IF NOT EXISTS company VARCHAR(255),
ADD COLUMN IF NOT EXISTS street VARCHAR(255),
ADD COLUMN IF NOT EXISTS city VARCHAR(100),
ADD COLUMN IF NOT EXISTS postal_code VARCHAR(20),
ADD COLUMN IF NOT EXISTS country VARCHAR(2) DEFAULT 'DE',
ADD COLUMN IF NOT EXISTS balance DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS is_admin BOOLEAN DEFAULT FALSE;

-- Service categories table
CREATE TABLE IF NOT EXISTS service_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    sort_order INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Update services table structure
ALTER TABLE services 
ADD COLUMN IF NOT EXISTS category_id INT,
ADD COLUMN IF NOT EXISTS setup_fee DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS min_contract_months INT DEFAULT 1,
ADD COLUMN IF NOT EXISTS auto_setup BOOLEAN DEFAULT TRUE,
ADD COLUMN IF NOT EXISTS proxmox_template VARCHAR(100),
ADD COLUMN IF NOT EXISTS os_options JSON,
ADD COLUMN IF NOT EXISTS network_config JSON;

-- User services enhanced
ALTER TABLE user_services 
ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45),
ADD COLUMN IF NOT EXISTS root_password VARCHAR(255),
ADD COLUMN IF NOT EXISTS os_installed VARCHAR(100),
ADD COLUMN IF NOT EXISTS auto_renew BOOLEAN DEFAULT TRUE,
ADD COLUMN IF NOT EXISTS next_due_date DATE,
ADD COLUMN IF NOT EXISTS suspension_reason TEXT,
ADD COLUMN IF NOT EXISTS proxmox_node VARCHAR(50),
ADD COLUMN IF NOT EXISTS proxmox_config JSON;

-- Invoices table
CREATE TABLE IF NOT EXISTS invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    total_amount DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    due_date DATE NOT NULL,
    paid_at TIMESTAMP NULL,
    payment_method VARCHAR(50),
    payment_reference VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Invoice items
CREATE TABLE IF NOT EXISTS invoice_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    service_id INT NULL,
    user_service_id INT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id),
    FOREIGN KEY (user_service_id) REFERENCES user_services(id)
);

-- Balance transactions
CREATE TABLE IF NOT EXISTS balance_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('credit', 'debit') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    reference_type ENUM('payment', 'invoice', 'refund', 'admin', 'bonus') NOT NULL,
    reference_id INT NULL,
    balance_before DECIMAL(10,2) NOT NULL,
    balance_after DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Support tickets enhanced
ALTER TABLE support_tickets 
ADD COLUMN IF NOT EXISTS department ENUM('technical', 'billing', 'sales', 'abuse') DEFAULT 'technical',
ADD COLUMN IF NOT EXISTS assigned_admin_id INT NULL,
ADD COLUMN IF NOT EXISTS last_reply_by ENUM('customer', 'admin') DEFAULT 'customer',
ADD COLUMN IF NOT EXISTS last_reply_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS rating INT NULL CHECK (rating >= 1 AND rating <= 5);

-- Ticket replies
CREATE TABLE IF NOT EXISTS ticket_replies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    user_id INT NULL,
    admin_id INT NULL,
    message TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    attachments JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

-- System settings
CREATE TABLE IF NOT EXISTS system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Server monitoring data
CREATE TABLE IF NOT EXISTS server_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_service_id INT NOT NULL,
    cpu_usage DECIMAL(5,2),
    memory_usage DECIMAL(5,2),
    disk_usage DECIMAL(5,2),
    network_in BIGINT,
    network_out BIGINT,
    uptime BIGINT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_service_id) REFERENCES user_services(id) ON DELETE CASCADE
);

-- Insert default service categories
INSERT INTO service_categories (name, slug, description, icon, sort_order) VALUES
('Webspace', 'webspace', 'Webhosting-Pakete für Websites und Blogs', 'fas fa-globe', 1),
('vServer', 'vserver', 'Virtuelle Server mit voller Root-Berechtigung', 'fas fa-server', 2),
('GameServer', 'gameserver', 'Spezialisierte Gaming-Server', 'fas fa-gamepad', 3),
('Domain', 'domain', 'Domain-Registrierung und -Verwaltung', 'fas fa-link', 4)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Update services with categories
UPDATE services SET category_id = 1 WHERE type = 'webhosting';
UPDATE services SET category_id = 2 WHERE type = 'vps';
UPDATE services SET category_id = 3 WHERE type = 'gameserver';
UPDATE services SET category_id = 4 WHERE type = 'domain';

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description, setting_type) VALUES
('mollie_api_key', '', 'Mollie API Key für Zahlungen', 'string'),
('mollie_test_mode', 'true', 'Mollie Test-Modus aktiviert', 'boolean'),
('proxmox_host', '', 'Proxmox VE Server Adresse', 'string'),
('proxmox_user', '', 'Proxmox VE API Benutzer', 'string'),
('proxmox_password', '', 'Proxmox VE API Passwort', 'string'),
('proxmox_node', 'pve', 'Standard Proxmox Node', 'string'),
('proxmox_storage', 'local-lvm', 'Standard Storage für VMs', 'string'),
('proxmox_bridge', 'vmbr0', 'Standard Netzwerk Bridge', 'string'),
('proxmox_ipv4_pool', '192.168.1.100-192.168.1.200', 'IPv4 Adress-Pool für VMs', 'string'),
('site_name', 'SpectraHost', 'Name der Website', 'string'),
('site_email', 'admin@spectrahost.de', 'Admin E-Mail Adresse', 'string'),
('currency', 'EUR', 'Standard Währung', 'string'),
('tax_rate', '19', 'Steuersatz in Prozent', 'number'),
('invoice_prefix', 'SH-', 'Rechnungsnummer Prefix', 'string')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

-- Add foreign key for service categories
ALTER TABLE services ADD FOREIGN KEY (category_id) REFERENCES service_categories(id);

-- Create additional indexes
CREATE INDEX IF NOT EXISTS idx_invoices_user_id ON invoices(user_id);
CREATE INDEX IF NOT EXISTS idx_invoices_status ON invoices(status);
CREATE INDEX IF NOT EXISTS idx_balance_transactions_user_id ON balance_transactions(user_id);
CREATE INDEX IF NOT EXISTS idx_server_stats_user_service_id ON server_stats(user_service_id);
CREATE INDEX IF NOT EXISTS idx_server_stats_recorded_at ON server_stats(recorded_at);
CREATE INDEX IF NOT EXISTS idx_ticket_replies_ticket_id ON ticket_replies(ticket_id);