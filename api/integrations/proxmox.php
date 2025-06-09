<?php
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

class ProxmoxAPI {
    private $host;
    private $username;
    private $password;
    private $port;
    private $ticket;
    private $csrfToken;
    
    public function __construct($host, $username, $password, $port = 8006) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
    }
    
    public function authenticate() {
        $url = "https://{$this->host}:{$this->port}/api2/json/access/ticket";
        
        $data = [
            'username' => $this->username,
            'password' => $this->password
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Authentication failed: HTTP ' . $httpCode);
        }
        
        $result = json_decode($response, true);
        
        if (!$result || !isset($result['data'])) {
            throw new Exception('Invalid authentication response');
        }
        
        $this->ticket = $result['data']['ticket'];
        $this->csrfToken = $result['data']['CSRFPreventionToken'];
        
        return true;
    }
    
    public function getNodes() {
        if (!$this->ticket) {
            $this->authenticate();
        }
        
        $url = "https://{$this->host}:{$this->port}/api2/json/nodes";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Cookie: PVEAuthCookie=' . $this->ticket
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to get nodes: HTTP ' . $httpCode);
        }
        
        $result = json_decode($response, true);
        return $result['data'] ?? [];
    }
    
    public function getVersion() {
        if (!$this->ticket) {
            $this->authenticate();
        }
        
        $url = "https://{$this->host}:{$this->port}/api2/json/version";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Cookie: PVEAuthCookie=' . $this->ticket
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to get version: HTTP ' . $httpCode);
        }
        
        $result = json_decode($response, true);
        return $result['data'] ?? [];
    }
    
    public function getVMs($node) {
        if (!$this->ticket) {
            $this->authenticate();
        }
        
        $url = "https://{$this->host}:{$this->port}/api2/json/nodes/{$node}/qemu";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Cookie: PVEAuthCookie=' . $this->ticket
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to get VMs: HTTP ' . $httpCode);
        }
        
        $result = json_decode($response, true);
        return $result['data'] ?? [];
    }
    
    public function testConnection() {
        try {
            $start = microtime(true);
            
            $this->authenticate();
            $version = $this->getVersion();
            $nodes = $this->getNodes();
            
            $totalVMs = 0;
            foreach ($nodes as $node) {
                $vms = $this->getVMs($node['node']);
                $totalVMs += count($vms);
            }
            
            $responseTime = round((microtime(true) - $start) * 1000);
            
            return [
                'success' => true,
                'message' => 'Proxmox VE Verbindung erfolgreich getestet',
                'details' => [
                    'version' => $version['version'] ?? 'Unknown',
                    'release' => $version['release'] ?? 'Unknown',
                    'nodes' => count($nodes),
                    'vms' => $totalVMs,
                    'response_time' => $responseTime . 'ms'
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Proxmox VE Verbindung fehlgeschlagen: ' . $e->getMessage(),
                'details' => [
                    'error' => $e->getMessage(),
                    'suggestion' => 'Überprüfen Sie Host, Benutzername und Passwort'
                ]
            ];
        }
    }
}

function getProxmoxConfig() {
    global $database;
    
    try {
        $stmt = $database->prepare("SELECT config FROM integration_settings WHERE integration_name = 'proxmox'");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result && $result['config']) {
            return json_decode($result['config'], true);
        }
    } catch (Exception $e) {
        // Table doesn't exist yet, return empty config
    }
    
    return [
        'host' => '',
        'username' => '',
        'password' => '',
        'port' => 8006
    ];
}

function saveProxmoxConfig($config) {
    global $database;
    
    try {
        // Try to create table if it doesn't exist
        $database->exec("CREATE TABLE IF NOT EXISTS integration_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            integration_name VARCHAR(50) NOT NULL UNIQUE,
            status ENUM('active', 'inactive', 'error', 'configuring') DEFAULT 'inactive',
            config JSON,
            last_test TIMESTAMP NULL,
            test_status ENUM('success', 'failed', 'pending') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        $stmt = $database->prepare("
            INSERT INTO integration_settings (integration_name, config, status, updated_at) 
            VALUES ('proxmox', ?, 'configuring', NOW())
            ON DUPLICATE KEY UPDATE 
            config = VALUES(config), 
            status = 'configuring',
            updated_at = NOW()
        ");
        $stmt->execute([json_encode($config)]);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to save Proxmox config: " . $e->getMessage());
        return false;
    }
}

try {
    switch ($method) {
        case 'POST':
            switch ($action) {
                case 'test':
                    $config = getProxmoxConfig();
                    
                    if (empty($config['host']) || empty($config['username']) || empty($config['password'])) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Proxmox VE nicht konfiguriert',
                            'details' => [
                                'error' => 'Host, Benutzername oder Passwort fehlen',
                                'suggestion' => 'Konfigurieren Sie die Proxmox VE Verbindung'
                            ]
                        ]);
                        break;
                    }
                    
                    $proxmox = new ProxmoxAPI($config['host'], $config['username'], $config['password'], $config['port']);
                    $result = $proxmox->testConnection();
                    echo json_encode($result);
                    break;
                    
                case 'configure':
                    $input = json_decode(file_get_contents('php://input'), true);
                    
                    if (!$input || !isset($input['config'])) {
                        echo json_encode(['success' => false, 'message' => 'Invalid configuration data']);
                        break;
                    }
                    
                    $config = $input['config'];
                    
                    if (saveProxmoxConfig($config)) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Proxmox VE Konfiguration gespeichert'
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Fehler beim Speichern der Konfiguration'
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