<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check if user is admin
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'test':
            $input = json_decode(file_get_contents('php://input'), true);
            $integration = $input['integration'] ?? '';
            
            switch ($integration) {
                case 'proxmox':
                    $result = testProxmoxConnection();
                    break;
                case 'mollie':
                    $result = testMollieConnection();
                    break;
                default:
                    throw new Exception('Unbekannte Integration');
            }
            
            echo json_encode($result);
            break;
            
        case 'configure':
            $input = json_decode(file_get_contents('php://input'), true);
            $integration = $input['integration'] ?? '';
            $config = $input['config'] ?? [];
            
            $result = configureIntegration($integration, $config);
            echo json_encode($result);
            break;
            
        case 'logs':
            $logs = getIntegrationLogs();
            echo json_encode(['success' => true, 'data' => $logs]);
            break;
            
        default:
            throw new Exception('Ungültige Aktion');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function testProxmoxConnection() {
    // Test Proxmox connection
    $proxmox_host = $_ENV['PROXMOX_HOST'] ?? '';
    $proxmox_user = $_ENV['PROXMOX_USER'] ?? '';
    $proxmox_password = $_ENV['PROXMOX_PASSWORD'] ?? '';
    
    if (empty($proxmox_host) || empty($proxmox_user) || empty($proxmox_password)) {
        return [
            'success' => false,
            'message' => 'Proxmox-Konfiguration unvollständig'
        ];
    }
    
    // Simple connection test
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://{$proxmox_host}:8006/api2/json/version");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return [
            'success' => true,
            'message' => 'Proxmox-Verbindung erfolgreich',
            'details' => 'Server erreichbar'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Proxmox-Verbindung fehlgeschlagen'
        ];
    }
}

function testMollieConnection() {
    $mollie_key = $_ENV['MOLLIE_API_KEY'] ?? '';
    
    if (empty($mollie_key)) {
        return [
            'success' => false,
            'message' => 'Mollie API-Schlüssel fehlt'
        ];
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.mollie.com/v2/methods');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $mollie_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return [
            'success' => true,
            'message' => 'Mollie-Verbindung erfolgreich',
            'details' => count($data['_embedded']['methods'] ?? []) . ' Zahlungsmethoden verfügbar'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Mollie-Verbindung fehlgeschlagen'
        ];
    }
}

function configureIntegration($integration, $config) {
    global $db;
    
    try {
        switch ($integration) {
            case 'mollie':
                // Save Mollie configuration
                $stmt = $db->prepare("
                    INSERT INTO integrations (name, config, status, updated_at) 
                    VALUES (?, ?, 'configured', NOW()) 
                    ON DUPLICATE KEY UPDATE 
                    config = VALUES(config), 
                    status = VALUES(status), 
                    updated_at = VALUES(updated_at)
                ");
                $configJson = json_encode($config);
                $stmt->execute(['mollie', $configJson]);
                
                // Update environment variables (in production, this would be handled differently)
                $_ENV['MOLLIE_API_KEY'] = $config['api_key'];
                
                return [
                    'success' => true,
                    'message' => 'Mollie-Konfiguration erfolgreich gespeichert'
                ];
                
            case 'proxmox':
                // Save Proxmox configuration
                $stmt = $db->prepare("
                    INSERT INTO integrations (name, config, status, updated_at) 
                    VALUES (?, ?, 'configured', NOW()) 
                    ON DUPLICATE KEY UPDATE 
                    config = VALUES(config), 
                    status = VALUES(status), 
                    updated_at = VALUES(updated_at)
                ");
                $configJson = json_encode($config);
                $stmt->execute(['proxmox', $configJson]);
                
                // Update environment variables
                $_ENV['PROXMOX_HOST'] = $config['host'];
                $_ENV['PROXMOX_USER'] = $config['username'];
                $_ENV['PROXMOX_PASSWORD'] = $config['password'];
                
                return [
                    'success' => true,
                    'message' => 'Proxmox VE-Konfiguration erfolgreich gespeichert'
                ];
                
            default:
                throw new Exception('Unbekannte Integration');
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Fehler beim Speichern: ' . $e->getMessage()
        ];
    }
}

function getIntegrationLogs() {
    // Return sample logs - in production this would come from database
    return [
        [
            'id' => 1,
            'type' => 'mollie',
            'message' => 'Zahlung erfolgreich verarbeitet',
            'details' => 'Payment ID: pay_123456',
            'status' => 'success',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 minutes'))
        ],
        [
            'id' => 2,
            'type' => 'proxmox',
            'message' => 'VM erfolgreich erstellt',
            'details' => 'VM-ID: 201',
            'status' => 'success',
            'created_at' => date('Y-m-d H:i:s', strtotime('-15 minutes'))
        ],
        [
            'id' => 3,
            'type' => 'email',
            'message' => 'E-Mail Zustellung fehlgeschlagen',
            'details' => 'SMTP Verbindung unterbrochen',
            'status' => 'error',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]
    ];
}
?>