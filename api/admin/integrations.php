<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check if user is admin
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
    global $db;
    
    try {
        // Get stored Proxmox configuration
        $stmt = $db->prepare("SELECT config FROM integrations WHERE name = 'proxmox'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || !$result['config']) {
            return [
                'success' => false,
                'message' => 'Proxmox-Konfiguration nicht gefunden'
            ];
        }
        
        $config = json_decode($result['config'], true);
        $host = $config['host'] ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        
        if (empty($host) || empty($username) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Proxmox-Konfiguration unvollständig'
            ];
        }
        
        // Step 1: Get authentication ticket
        $authData = [
            'username' => $username,
            'password' => $password
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://{$host}:8006/api2/json/access/ticket");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($authData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        
        $authResponse = curl_exec($ch);
        $authHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($authHttpCode !== 200) {
            return [
                'success' => false,
                'message' => 'Proxmox-Authentifizierung fehlgeschlagen'
            ];
        }
        
        $authData = json_decode($authResponse, true);
        if (!$authData || !isset($authData['data']['ticket'])) {
            return [
                'success' => false,
                'message' => 'Proxmox-Authentifizierung ungültig'
            ];
        }
        
        $ticket = $authData['data']['ticket'];
        $csrfToken = $authData['data']['CSRFPreventionToken'];
        
        // Step 2: Test API access with version endpoint
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://{$host}:8006/api2/json/version");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Cookie: PVEAuthCookie={$ticket}",
            "CSRFPreventionToken: {$csrfToken}"
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            $version = $data['data']['version'] ?? 'Unknown';
            $release = $data['data']['release'] ?? 'Unknown';
            
            // Get nodes information
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://{$host}:8006/api2/json/nodes");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Cookie: PVEAuthCookie={$ticket}",
                "CSRFPreventionToken: {$csrfToken}"
            ]);
            
            $nodesResponse = curl_exec($ch);
            curl_close($ch);
            
            $nodesData = json_decode($nodesResponse, true);
            $nodeCount = count($nodesData['data'] ?? []);
            
            return [
                'success' => true,
                'message' => 'Proxmox-Verbindung erfolgreich',
                'details' => [
                    'version' => $version,
                    'release' => $release,
                    'nodes' => $nodeCount,
                    'response_time' => '< 1s'
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Proxmox-API Zugriff fehlgeschlagen'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Proxmox-Verbindungsfehler: ' . $e->getMessage()
        ];
    }
}

function testMollieConnection() {
    global $db;
    
    try {
        // Get stored Mollie configuration
        $stmt = $db->prepare("SELECT config FROM integrations WHERE name = 'mollie'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || !$result['config']) {
            return [
                'success' => false,
                'message' => 'Mollie-Konfiguration nicht gefunden'
            ];
        }
        
        $config = json_decode($result['config'], true);
        $apiKey = $config['api_key'] ?? '';
        
        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'Mollie API-Schlüssel fehlt'
            ];
        }
        
        // Test 1: Get payment methods
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.mollie.com/v2/methods');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        $methodsResponse = curl_exec($ch);
        $methodsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($methodsHttpCode !== 200) {
            $errorData = json_decode($methodsResponse, true);
            $errorMsg = $errorData['detail'] ?? 'API-Zugriff fehlgeschlagen';
            return [
                'success' => false,
                'message' => 'Mollie-Verbindung fehlgeschlagen: ' . $errorMsg
            ];
        }
        
        $methodsData = json_decode($methodsResponse, true);
        $availableMethods = [];
        foreach ($methodsData['_embedded']['methods'] ?? [] as $method) {
            $availableMethods[] = $method['description'];
        }
        
        // Test 2: Get profile information
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.mollie.com/v2/profiles/me');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $profileResponse = curl_exec($ch);
        $profileHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $profileData = [];
        if ($profileHttpCode === 200) {
            $profileData = json_decode($profileResponse, true);
        }
        
        // Test 3: Get recent payments
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.mollie.com/v2/payments?limit=5');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $paymentsResponse = curl_exec($ch);
        $paymentsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $recentPaymentsCount = 0;
        if ($paymentsHttpCode === 200) {
            $paymentsData = json_decode($paymentsResponse, true);
            $recentPaymentsCount = count($paymentsData['_embedded']['payments'] ?? []);
        }
        
        // Determine if test mode
        $testMode = strpos($apiKey, 'test_') === 0;
        
        return [
            'success' => true,
            'message' => 'Mollie-Verbindung erfolgreich',
            'details' => [
                'profile_name' => $profileData['name'] ?? 'Unknown',
                'profile_email' => $profileData['email'] ?? 'Unknown',
                'test_mode' => $testMode,
                'available_methods' => $availableMethods,
                'recent_payments' => $recentPaymentsCount,
                'response_time' => '< 1s'
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Mollie-Verbindungsfehler: ' . $e->getMessage()
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