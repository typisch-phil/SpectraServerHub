CREATE TABLE IF NOT EXISTS integrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    config JSON,
    status ENUM('disabled', 'configured', 'active', 'error') DEFAULT 'disabled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default entries for Mollie and Proxmox
INSERT IGNORE INTO integrations (name, status) VALUES 
('mollie', 'disabled'),
('proxmox', 'disabled');