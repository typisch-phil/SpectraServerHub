<?php
require_once 'includes/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Tabelle für IP-Adressen-Management erstellen
    $sql = "CREATE TABLE IF NOT EXISTS ip_addresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(15) NOT NULL UNIQUE,
        subnet_mask VARCHAR(15) NOT NULL DEFAULT '255.255.255.0',
        gateway VARCHAR(15) NOT NULL,
        dns_primary VARCHAR(15) DEFAULT '8.8.8.8',
        dns_secondary VARCHAR(15) DEFAULT '8.8.4.4',
        vlan_id INT DEFAULT NULL,
        is_available BOOLEAN DEFAULT TRUE,
        assigned_service_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_available (is_available),
        INDEX idx_assigned (assigned_service_id)
    )";
    $db->exec($sql);
    echo "✓ Tabelle ip_addresses erstellt\n";

    // Tabelle für Bestellungen erstellen
    $sql = "CREATE TABLE IF NOT EXISTS user_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        service_type VARCHAR(50) NOT NULL,
        server_name VARCHAR(100) NOT NULL,
        ram_gb INT NOT NULL,
        cpu_cores INT NOT NULL,
        storage_gb INT NOT NULL,
        os_template VARCHAR(100) NOT NULL,
        monthly_price DECIMAL(10,2) NOT NULL,
        ip_address VARCHAR(15),
        proxmox_vmid INT DEFAULT NULL,
        status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
        error_message TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_status (status),
        INDEX idx_vmid (proxmox_vmid)
    )";
    $db->exec($sql);
    echo "✓ Tabelle user_orders erstellt\n";

    // user_services Tabelle um IP-Adresse erweitern
    try {
        $db->exec("ALTER TABLE user_services ADD COLUMN ip_address VARCHAR(15) DEFAULT NULL");
        echo "✓ Spalte ip_address zu user_services hinzugefügt\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✓ Spalte ip_address bereits vorhanden\n";
        } else {
            throw $e;
        }
    }

    // Beispiel-IP-Adressen einfügen
    $ips = [
        ['185.237.96.10', '185.237.96.1'],
        ['185.237.96.11', '185.237.96.1'],
        ['185.237.96.12', '185.237.96.1'],
        ['185.237.96.13', '185.237.96.1'],
        ['185.237.96.14', '185.237.96.1'],
        ['185.237.96.15', '185.237.96.1'],
        ['185.237.96.16', '185.237.96.1'],
        ['185.237.96.17', '185.237.96.1'],
        ['185.237.96.18', '185.237.96.1'],
        ['185.237.96.19', '185.237.96.1']
    ];

    $stmt = $db->prepare("INSERT IGNORE INTO ip_addresses (ip_address, gateway) VALUES (?, ?)");
    $insertedCount = 0;
    foreach ($ips as $ip) {
        $stmt->execute($ip);
        $insertedCount += $stmt->rowCount();
    }
    echo "✓ {$insertedCount} IP-Adressen eingefügt\n";

    echo "\n✅ Datenbank-Setup erfolgreich abgeschlossen!\n";

} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}
?>