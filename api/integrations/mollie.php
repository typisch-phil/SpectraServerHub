<?php
// Disable error reporting to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$database = Database::getInstance();
$stmt = $database->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

class MollieAPI {
    private $apiKey;
    private $isTestMode;
    
    public function __construct($apiKey, $isTestMode = false) {
        $this->apiKey = $apiKey;
        $this->isTestMode = $isTestMode;
    }
    
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = 'https://api.mollie.com/v2/' . $endpoint;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ]
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 400) {
            $error = json_decode($response, true);
            throw new Exception($error['detail'] ?? 'API request failed: HTTP ' . $httpCode);
        }
        
        return json_decode($response, true);
    }
    
    public function getProfile() {
        return $this->makeRequest('me');
    }
    
    public function getMethods() {
        return $this->makeRequest('methods');
    }
    
    public function getPayments($limit = 10) {
        return $this->makeRequest("payments?limit={$limit}");
    }
    
    public function createTestPayment() {
        $data = [
            'amount' => [
                'currency' => 'EUR',
                'value' => '10.00'
            ],
            'description' => 'SpectraHost Test Payment',
            'redirectUrl' => 'https://spectrahost.de/payment/success',
            'webhookUrl' => 'https://spectrahost.de/api/mollie/webhook'
        ];
        
        return $this->makeRequest('payments', 'POST', $data);
    }
    
    public function testConnection() {
        try {
            $start = microtime(true);
            
            // Get profile information
            $profile = $this->getProfile();
            
            // Get available payment methods
            $methods = $this->getMethods();
            
            // Get recent payments
            $payments = $this->getPayments(5);
            
            $responseTime = round((microtime(true) - $start) * 1000);
            
            return [
                'success' => true,
                'message' => 'Mollie API Verbindung erfolgreich',
                'details' => [
                    'profile_name' => $profile['name'] ?? 'Unknown',
                    'profile_email' => $profile['email'] ?? 'Unknown',
                    'api_version' => 'v2',
                    'test_mode' => $this->isTestMode,
                    'available_methods' => array_column($methods['_embedded']['methods'] ?? [], 'id'),
                    'recent_payments' => count($payments['_embedded']['payments'] ?? []),
                    'response_time' => $responseTime . 'ms'
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Mollie API Verbindung fehlgeschlagen: ' . $e->getMessage(),
                'details' => [
                    'error' => $e->getMessage(),
                    'suggestion' => 'Überprüfen Sie den API-Schlüssel und die Berechtigung'
                ]
            ];
        }
    }
}

function getMollieConfig() {
    global $database;
    
    try {
        $stmt = $database->prepare("SELECT config_value FROM system_configs WHERE config_key = 'mollie_config'");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result && $result['config_value']) {
            return json_decode($result['config_value'], true);
        }
    } catch (Exception $e) {
        // Try file fallback
        $configFile = __DIR__ . '/../../config/mollie.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if ($config) {
                return $config;
            }
        }
    }
    
    return [
        'live_key' => '',
        'test_key' => '',
        'webhook_url' => '',
        'test_mode' => true
    ];
}

function saveMollieConfig($config) {
    global $database;
    
    try {
        $configJson = json_encode($config);
        
        try {
            $stmt = $database->prepare("
                CREATE TABLE IF NOT EXISTS system_configs (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    config_key VARCHAR(100) NOT NULL UNIQUE,
                    config_value TEXT,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            $stmt->execute();
            
            $stmt = $database->prepare("
                INSERT INTO system_configs (config_key, config_value) 
                VALUES ('mollie_config', ?)
                ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
            ");
            $stmt->execute([$configJson]);
            
        } catch (Exception $e) {
            // Fallback: save to file if database fails
            $configDir = __DIR__ . '/../../config';
            if (!is_dir($configDir)) {
                mkdir($configDir, 0755, true);
            }
            file_put_contents($configDir . '/mollie.json', $configJson);
        }
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

try {
    switch ($method) {
        case 'POST':
            switch ($action) {
                case 'test':
                    $config = getMollieConfig();
                    
                    $apiKey = $config['test_mode'] ? $config['test_key'] : $config['live_key'];
                    
                    if (empty($apiKey)) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Mollie API nicht konfiguriert',
                            'details' => [
                                'error' => 'API-Schlüssel fehlt',
                                'suggestion' => 'Konfigurieren Sie die Mollie API-Schlüssel'
                            ]
                        ]);
                        break;
                    }
                    
                    $mollie = new MollieAPI($apiKey, $config['test_mode']);
                    $result = $mollie->testConnection();
                    echo json_encode($result);
                    break;
                    
                case 'configure':
                    $input = json_decode(file_get_contents('php://input'), true);
                    
                    if (!$input || !isset($input['config'])) {
                        echo json_encode(['success' => false, 'message' => 'Invalid configuration data']);
                        break;
                    }
                    
                    $config = $input['config'];
                    
                    // Validate required fields
                    if (empty($config['live_key']) && empty($config['test_key'])) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Mindestens ein API-Schlüssel muss angegeben werden'
                        ]);
                        break;
                    }
                    
                    if (saveMollieConfig($config)) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Mollie Konfiguration gespeichert'
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Fehler beim Speichern der Konfiguration'
                        ]);
                    }
                    break;
                    
                case 'create_test_payment':
                    $config = getMollieConfig();
                    $apiKey = $config['test_key'];
                    
                    if (empty($apiKey)) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Test-API-Schlüssel nicht konfiguriert'
                        ]);
                        break;
                    }
                    
                    $mollie = new MollieAPI($apiKey, true);
                    try {
                        $payment = $mollie->createTestPayment();
                        echo json_encode([
                            'success' => true,
                            'message' => 'Test-Zahlung erstellt',
                            'details' => [
                                'payment_id' => $payment['id'],
                                'checkout_url' => $payment['_links']['checkout']['href'],
                                'amount' => $payment['amount']['value'] . ' ' . $payment['amount']['currency']
                            ]
                        ]);
                    } catch (Exception $e) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Fehler beim Erstellen der Test-Zahlung: ' . $e->getMessage()
                        ]);
                    }
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid action']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>