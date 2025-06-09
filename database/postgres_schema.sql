-- SpectraHost Database Schema for PostgreSQL

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'suspended', 'deleted'))
);

-- Services table
CREATE TABLE IF NOT EXISTS services (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(20) NOT NULL CHECK (type IN ('webhosting', 'vps', 'gameserver', 'domain')),
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    cpu_cores INTEGER DEFAULT 1,
    memory_gb INTEGER DEFAULT 1,
    storage_gb INTEGER DEFAULT 10,
    bandwidth_gb INTEGER DEFAULT 1000,
    features JSONB,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User services (purchased services)
CREATE TABLE IF NOT EXISTS user_services (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    service_id INTEGER NOT NULL,
    server_name VARCHAR(255) NOT NULL,
    proxmox_vmid INTEGER NULL,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'active', 'suspended', 'terminated')),
    expires_at DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    service_id INTEGER NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    billing_period VARCHAR(20) DEFAULT 'monthly' CHECK (billing_period IN ('monthly', 'quarterly', 'yearly')),
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'paid', 'failed', 'cancelled')),
    payment_id VARCHAR(255) NULL,
    payment_method VARCHAR(50) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- Support tickets table
CREATE TABLE IF NOT EXISTS support_tickets (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority VARCHAR(20) DEFAULT 'medium' CHECK (priority IN ('low', 'medium', 'high', 'urgent')),
    status VARCHAR(20) DEFAULT 'open' CHECK (status IN ('open', 'answered', 'closed')),
    admin_response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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
('Domain .com', 'domain', 'Internationale Domain', 11.99, 0, 0, 0, 0, '{"dns": true, "whois": true, "redirect": true}')
ON CONFLICT DO NOTHING;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_user_services_user_id ON user_services(user_id);
CREATE INDEX IF NOT EXISTS idx_orders_user_id ON orders(user_id);
CREATE INDEX IF NOT EXISTS idx_support_tickets_user_id ON support_tickets(user_id);
CREATE INDEX IF NOT EXISTS idx_services_type ON services(type);

-- Create function to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Create triggers for updated_at fields
CREATE OR REPLACE TRIGGER update_users_updated_at BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE OR REPLACE TRIGGER update_user_services_updated_at BEFORE UPDATE ON user_services FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE OR REPLACE TRIGGER update_orders_updated_at BEFORE UPDATE ON orders FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE OR REPLACE TRIGGER update_support_tickets_updated_at BEFORE UPDATE ON support_tickets FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();