<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    // Use environment variable for Mollie API key
    $config = [
        'api_key' => $_ENV['MOLLIE_API_KEY'],
        'test_mode' => true, // Default to test mode for safety
        'webhook_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/api/webhooks/mollie.php',
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
    
    $result = $stmt->execute(['mollie', json_encode($config)]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Mollie-Konfiguration erfolgreich gespeichert',
            'webhook_url' => $config['webhook_url']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Fehler beim Speichern der Mollie-Konfiguration'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
?>