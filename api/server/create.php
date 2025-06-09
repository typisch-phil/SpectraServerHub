<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$database = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

class ProxmoxVMManager {
    private $host;
    private $username;
    private $password;
    private $port;
    private $ticket;
    private $csrfToken;
    
    public function __construct($config) {
        $this->host = $config['host'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->port = $config['port'] ?? 8006;
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
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Proxmox authentication failed: HTTP ' . $httpCode);
        }
        
        $result = json_decode($response, true);
        if (!$result || !isset($result['data'])) {
            throw new Exception('Invalid authentication response');
        }
        
        $this->ticket = $result['data']['ticket'];
        $this->csrfToken = $result['data']['CSRFPreventionToken'];
        
        return true;
    }
    
    public function getNextVMID($node) {
        if (!$this->ticket) {
            $this->authenticate();
        }
        
        $url = "https://{$this->host}:{$this->port}/api2/json/cluster/nextid";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Cookie: PVEAuthCookie=' . $this->ticket]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to get next VM ID: HTTP ' . $httpCode);
        }
        
        $result = json_decode($response, true);
        return $result['data'] ?? 100;
    }
    
    public function createVM($node, $vmSpecs) {
        if (!$this->ticket) {
            $this->authenticate();
        }
        
        $vmid = $this->getNextVMID($node);
        $url = "https://{$this->host}:{$this->port}/api2/json/nodes/{$node}/qemu";
        
        // VM configuration based on specifications
        $vmConfig = [
            'vmid' => $vmid,
            'name' => $vmSpecs['hostname'],
            'memory' => $vmSpecs['memory'],
            'cores' => $vmSpecs['cpu'],
            'sockets' => 1,
            'cpu' => 'host',
            'ostype' => $vmSpecs['os_type'] ?? 'l26',
            'net0' => 'virtio,bridge=vmbr0,firewall=1',
            'scsi0' => "local-lvm:{$vmSpecs['storage']},format=qcow2",
            'scsihw' => 'virtio-scsi-pci',
            'boot' => 'order=scsi0;net0',
            'agent' => '1',
            'onboot' => '1',
            'protection' => '0',
            'description' => "SpectraHost vServer\nCustomer: {$vmSpecs['customer_email']}\nCreated: " . date('Y-m-d H:i:s')
        ];
        
        // Add CD-ROM with OS template if specified
        if (!empty($vmSpecs['template'])) {
            $vmConfig['ide2'] = $vmSpecs['template'] . ',media=cdrom';
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($vmConfig),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [
                'Cookie: PVEAuthCookie=' . $this->ticket,
                'CSRFPreventionToken: ' . $this->csrfToken,
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('VM creation failed: HTTP ' . $httpCode . ' - ' . $response);
        }
        
        $result = json_decode($response, true);
        
        if (!$result || isset($result['errors'])) {
            throw new Exception('VM creation failed: ' . json_encode($result['errors'] ?? ['Unknown error']));
        }
        
        return [
            'vmid' => $vmid,
            'node' => $node,
            'task' => $result['data'] ?? null,
            'status' => 'creating'
        ];
    }
    
    public function startVM($node, $vmid) {
        if (!$this->ticket) {
            $this->authenticate();
        }
        
        $url = "https://{$this->host}:{$this->port}/api2/json/nodes/{$node}/qemu/{$vmid}/status/start";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Cookie: PVEAuthCookie=' . $this->ticket,
                'CSRFPreventionToken: ' . $this->csrfToken
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('VM start failed: HTTP ' . $httpCode);
        }
        
        return json_decode($response, true);
    }
    
    public function getVMStatus($node, $vmid) {
        if (!$this->ticket) {
            $this->authenticate();
        }
        
        $url = "https://{$this->host}:{$this->port}/api2/json/nodes/{$node}/qemu/{$vmid}/status/current";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Cookie: PVEAuthCookie=' . $this->ticket]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to get VM status: HTTP ' . $httpCode);
        }
        
        $result = json_decode($response, true);
        return $result['data'] ?? [];
    }
}

function getProxmoxConfig() {
    global $database;
    
    try {
        $stmt = $database->prepare("SELECT config_value FROM system_configs WHERE config_key = 'proxmox_config'");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result && $result['config_value']) {
            return json_decode($result['config_value'], true);
        }
    } catch (Exception $e) {
        $configFile = __DIR__ . '/../../config/proxmox.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if ($config) {
                return $config;
            }
        }
    }
    
    return null;
}

function saveServerToDatabase($userId, $serviceId, $vmDetails, $status = 'creating') {
    global $database;
    
    try {
        $stmt = $database->prepare("
            INSERT INTO servers (
                user_id, service_id, proxmox_vmid, proxmox_node, 
                hostname, status, specs, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $specs = json_encode([
            'cpu' => $vmDetails['cpu'],
            'memory' => $vmDetails['memory'],
            'storage' => $vmDetails['storage'],
            'os_type' => $vmDetails['os_type']
        ]);
        
        $stmt->execute([
            $userId,
            $serviceId,
            $vmDetails['vmid'],
            $vmDetails['node'],
            $vmDetails['hostname'],
            $status,
            $specs
        ]);
        
        return $database->lastInsertId();
    } catch (Exception $e) {
        // Table might not exist, create it
        try {
            $database->exec("
                CREATE TABLE IF NOT EXISTS servers (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    service_id INT,
                    proxmox_vmid INT,
                    proxmox_node VARCHAR(50),
                    hostname VARCHAR(255),
                    status VARCHAR(50) DEFAULT 'creating',
                    specs JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_status (status)
                )
            ");
            
            // Retry the insert
            $stmt = $database->prepare("
                INSERT INTO servers (
                    user_id, service_id, proxmox_vmid, proxmox_node, 
                    hostname, status, specs, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $userId,
                $serviceId,
                $vmDetails['vmid'],
                $vmDetails['node'],
                $vmDetails['hostname'],
                $status,
                $specs
            ]);
            
            return $database->lastInsertId();
        } catch (Exception $e2) {
            throw new Exception('Failed to save server to database: ' . $e2->getMessage());
        }
    }
}

try {
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
                exit;
            }
            
            $action = $input['action'] ?? '';
            
            switch ($action) {
                case 'create_server':
                    // Get Proxmox configuration
                    $proxmoxConfig = getProxmoxConfig();
                    if (!$proxmoxConfig) {
                        echo json_encode([
                            'success' => false,
                            'error' => 'Proxmox VE nicht konfiguriert',
                            'message' => 'Bitte konfigurieren Sie zuerst die Proxmox VE Integration'
                        ]);
                        exit;
                    }
                    
                    // Validate required parameters
                    $requiredFields = ['service_id', 'cpu', 'memory', 'storage', 'hostname'];
                    foreach ($requiredFields as $field) {
                        if (empty($input[$field])) {
                            echo json_encode([
                                'success' => false,
                                'error' => "Missing required field: {$field}"
                            ]);
                            exit;
                        }
                    }
                    
                    // Get user email for VM description
                    $stmt = $database->prepare("SELECT email FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                    
                    // Prepare VM specifications
                    $vmSpecs = [
                        'hostname' => $input['hostname'],
                        'cpu' => (int)$input['cpu'],
                        'memory' => (int)$input['memory'],
                        'storage' => (int)$input['storage'],
                        'os_type' => $input['os_type'] ?? 'l26',
                        'template' => $input['template'] ?? '',
                        'customer_email' => $user['email'] ?? 'unknown@spectrahost.de'
                    ];
                    
                    // Create VM using Proxmox API
                    $proxmox = new ProxmoxVMManager($proxmoxConfig);
                    $node = $proxmoxConfig['node'] ?? 'pve';
                    
                    $vmResult = $proxmox->createVM($node, $vmSpecs);
                    
                    // Save server details to database
                    $vmResult['cpu'] = $vmSpecs['cpu'];
                    $vmResult['memory'] = $vmSpecs['memory'];
                    $vmResult['storage'] = $vmSpecs['storage'];
                    $vmResult['os_type'] = $vmSpecs['os_type'];
                    $vmResult['hostname'] = $vmSpecs['hostname'];
                    
                    $serverId = saveServerToDatabase(
                        $_SESSION['user_id'],
                        $input['service_id'],
                        $vmResult,
                        'creating'
                    );
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Server wird erstellt',
                        'data' => [
                            'server_id' => $serverId,
                            'vmid' => $vmResult['vmid'],
                            'node' => $vmResult['node'],
                            'hostname' => $vmSpecs['hostname'],
                            'status' => 'creating',
                            'specs' => [
                                'cpu' => $vmSpecs['cpu'],
                                'memory' => $vmSpecs['memory'],
                                'storage' => $vmSpecs['storage']
                            ]
                        ]
                    ]);
                    break;
                    
                case 'start_server':
                    $proxmoxConfig = getProxmoxConfig();
                    if (!$proxmoxConfig) {
                        echo json_encode(['success' => false, 'error' => 'Proxmox VE nicht konfiguriert']);
                        exit;
                    }
                    
                    $vmid = $input['vmid'] ?? null;
                    $node = $input['node'] ?? $proxmoxConfig['node'] ?? 'pve';
                    
                    if (!$vmid) {
                        echo json_encode(['success' => false, 'error' => 'VM ID required']);
                        exit;
                    }
                    
                    $proxmox = new ProxmoxVMManager($proxmoxConfig);
                    $result = $proxmox->startVM($node, $vmid);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Server wird gestartet',
                        'data' => $result
                    ]);
                    break;
                    
                case 'get_status':
                    $proxmoxConfig = getProxmoxConfig();
                    if (!$proxmoxConfig) {
                        echo json_encode(['success' => false, 'error' => 'Proxmox VE nicht konfiguriert']);
                        exit;
                    }
                    
                    $vmid = $input['vmid'] ?? null;
                    $node = $input['node'] ?? $proxmoxConfig['node'] ?? 'pve';
                    
                    if (!$vmid) {
                        echo json_encode(['success' => false, 'error' => 'VM ID required']);
                        exit;
                    }
                    
                    $proxmox = new ProxmoxVMManager($proxmoxConfig);
                    $status = $proxmox->getVMStatus($node, $vmid);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $status
                    ]);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'error' => 'Invalid action']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>