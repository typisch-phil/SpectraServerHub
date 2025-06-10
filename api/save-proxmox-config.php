<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    // Use environment variables for Proxmox configuration
    $config = [
        'host' => $_ENV['PROXMOX_HOST'],
        'username' => $_ENV['PROXMOX_USERNAME'],
        'password' => $_ENV['PROXMOX_PASSWORD'],
        'node' => $_ENV['PROXMOX_NODE'],
        'ssl_verify' => false,
        'demo_mode' => false,
        'last_updated' => date('Y-m-d H:i:s')
    ];
    
    // Save to database
    $stmt = $db->prepare("
        INSERT INTO integrations (name, config, status, updated_at) 
        VALUES (?, ?, 'configured', NOW()) 
        ON DUPLICATE KEY UPDATE 
        config = VALUES(config), 
        status = VALUES(status), 
        updated_at = VALUES(updated_at)
    ");
    
    $result = $stmt->execute(['proxmox', json_encode($config)]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Proxmox-Konfiguration erfolgreich gespeichert'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Fehler beim Speichern der Proxmox-Konfiguration'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
?>