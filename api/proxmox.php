<?php
// Proxmox VE Integration für automatische Server-Bereitstellung
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

// Datenbankverbindung
try {
    $dsn = "mysql:host=37.114.32.205;dbname=s9281_spectrahost;port=3306;charset=utf8mb4";
    $pdo = new PDO($dsn, "s9281_spectrahost", getenv('MYSQL_PASSWORD') ?: '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Admin-Authentifizierung prüfen
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

// Proxmox-Konfiguration
$proxmox_config = [
    'host' => '45.137.68.202',
    'node' => 'bl1-4',
    'username' => 'spectrahost@pve',
    'password' => getenv('PROXMOX_PASSWORD') ?: ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'create_vm':
            createProxmoxVM($pdo, $proxmox_config, $input);
            break;
            
        case 'start_vm':
            startProxmoxVM($proxmox_config, $input['vmid'] ?? '');
            break;
            
        case 'stop_vm':
            stopProxmoxVM($proxmox_config, $input['vmid'] ?? '');
            break;
            
        case 'delete_vm':
            deleteProxmoxVM($pdo, $proxmox_config, $input['vmid'] ?? '');
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'list_vms':
            listProxmoxVMs($proxmox_config);
            break;
            
        case 'vm_status':
            getVMStatus($proxmox_config, $_GET['vmid'] ?? '');
            break;
            
        case 'node_status':
            getNodeStatus($proxmox_config);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}

function getProxmoxTicket($config) {
    $auth_data = [
        'username' => $config['username'],
        'password' => $config['password']
    ];
    
    $response = callProxmoxAPI('access/ticket', 'POST', $auth_data, $config['host']);
    
    if ($response && isset($response['data']['ticket'])) {
        return [
            'ticket' => $response['data']['ticket'],
            'CSRFPreventionToken' => $response['data']['CSRFPreventionToken']
        ];
    }
    
    return false;
}

function callProxmoxAPI($endpoint, $method, $data, $host, $auth = null) {
    $url = "https://{$host}:8006/api2/json/{$endpoint}";
    
    $headers = ['Content-Type: application/x-www-form-urlencoded'];
    
    if ($auth) {
        $headers[] = "Cookie: PVEAuthCookie={$auth['ticket']}";
        if ($method !== 'GET') {
            $headers[] = "CSRFPreventionToken: {$auth['CSRFPreventionToken']}";
        }
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code >= 200 && $http_code < 300) {
        return json_decode($response, true);
    }
    
    error_log("Proxmox API Error: HTTP $http_code - $response");
    return false;
}

function createProxmoxVM($pdo, $config, $input) {
    $user_service_id = $input['user_service_id'] ?? '';
    $template = $input['template'] ?? 'ubuntu-22.04';
    $memory = intval($input['memory'] ?? 1024);
    $disk = intval($input['disk'] ?? 20);
    $cores = intval($input['cores'] ?? 1);
    
    if (!$user_service_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'User service ID required']);
        return;
    }
    
    // Authentifizierung
    $auth = getProxmoxTicket($config);
    if (!$auth) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Proxmox authentication failed']);
        return;
    }
    
    // Nächste verfügbare VM-ID finden
    $vmlist = callProxmoxAPI("nodes/{$config['node']}/qemu", 'GET', null, $config['host'], $auth);
    $used_vmids = [];
    if ($vmlist && isset($vmlist['data'])) {
        foreach ($vmlist['data'] as $vm) {
            $used_vmids[] = $vm['vmid'];
        }
    }
    
    $vmid = 100;
    while (in_array($vmid, $used_vmids)) {
        $vmid++;
    }
    
    // VM-Konfiguration
    $vm_config = [
        'vmid' => $vmid,
        'name' => "spectrahost-vm-{$vmid}",
        'memory' => $memory,
        'cores' => $cores,
        'sockets' => 1,
        'scsi0' => "local-lvm:{$disk}",
        'net0' => 'virtio,bridge=vmbr0',
        'ostype' => 'l26',
        'boot' => 'order=scsi0',
        'agent' => 1,
        'tablet' => 0,
        'hotplug' => 'disk,network,usb',
        'protection' => 0
    ];
    
    // VM erstellen
    $create_response = callProxmoxAPI("nodes/{$config['node']}/qemu", 'POST', $vm_config, $config['host'], $auth);
    
    if ($create_response) {
        try {
            // VM-Info in Datenbank speichern
            $stmt = $pdo->prepare("
                UPDATE user_services 
                SET server_config = JSON_OBJECT(
                    'vmid', ?, 
                    'memory', ?, 
                    'disk', ?, 
                    'cores', ?,
                    'status', 'creating'
                )
                WHERE id = ?
            ");
            $stmt->execute([$vmid, $memory, $disk, $cores, $user_service_id]);
            
            echo json_encode([
                'success' => true,
                'vmid' => $vmid,
                'message' => 'VM creation initiated'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database update failed']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'VM creation failed']);
    }
}

function startProxmoxVM($config, $vmid) {
    if (!$vmid) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'VM ID required']);
        return;
    }
    
    $auth = getProxmoxTicket($config);
    if (!$auth) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Proxmox authentication failed']);
        return;
    }
    
    $response = callProxmoxAPI("nodes/{$config['node']}/qemu/{$vmid}/status/start", 'POST', [], $config['host'], $auth);
    
    if ($response) {
        echo json_encode(['success' => true, 'message' => 'VM start initiated']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'VM start failed']);
    }
}

function stopProxmoxVM($config, $vmid) {
    if (!$vmid) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'VM ID required']);
        return;
    }
    
    $auth = getProxmoxTicket($config);
    if (!$auth) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Proxmox authentication failed']);
        return;
    }
    
    $response = callProxmoxAPI("nodes/{$config['node']}/qemu/{$vmid}/status/stop", 'POST', [], $config['host'], $auth);
    
    if ($response) {
        echo json_encode(['success' => true, 'message' => 'VM stop initiated']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'VM stop failed']);
    }
}

function deleteProxmoxVM($pdo, $config, $vmid) {
    if (!$vmid) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'VM ID required']);
        return;
    }
    
    $auth = getProxmoxTicket($config);
    if (!$auth) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Proxmox authentication failed']);
        return;
    }
    
    // VM erst stoppen
    callProxmoxAPI("nodes/{$config['node']}/qemu/{$vmid}/status/stop", 'POST', [], $config['host'], $auth);
    
    // Warten und dann löschen
    sleep(5);
    $response = callProxmoxAPI("nodes/{$config['node']}/qemu/{$vmid}", 'DELETE', [], $config['host'], $auth);
    
    if ($response) {
        try {
            // VM-Info aus Datenbank entfernen
            $stmt = $pdo->prepare("
                UPDATE user_services 
                SET server_config = JSON_OBJECT('status', 'deleted')
                WHERE JSON_EXTRACT(server_config, '$.vmid') = ?
            ");
            $stmt->execute([$vmid]);
            
            echo json_encode(['success' => true, 'message' => 'VM deletion initiated']);
        } catch (Exception $e) {
            echo json_encode(['success' => true, 'message' => 'VM deleted, database update failed']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'VM deletion failed']);
    }
}

function listProxmoxVMs($config) {
    $auth = getProxmoxTicket($config);
    if (!$auth) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Proxmox authentication failed']);
        return;
    }
    
    $response = callProxmoxAPI("nodes/{$config['node']}/qemu", 'GET', null, $config['host'], $auth);
    
    if ($response && isset($response['data'])) {
        echo json_encode(['success' => true, 'data' => $response['data']]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to list VMs']);
    }
}

function getVMStatus($config, $vmid) {
    if (!$vmid) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'VM ID required']);
        return;
    }
    
    $auth = getProxmoxTicket($config);
    if (!$auth) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Proxmox authentication failed']);
        return;
    }
    
    $response = callProxmoxAPI("nodes/{$config['node']}/qemu/{$vmid}/status/current", 'GET', null, $config['host'], $auth);
    
    if ($response && isset($response['data'])) {
        echo json_encode(['success' => true, 'data' => $response['data']]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to get VM status']);
    }
}

function getNodeStatus($config) {
    $auth = getProxmoxTicket($config);
    if (!$auth) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Proxmox authentication failed']);
        return;
    }
    
    $response = callProxmoxAPI("nodes/{$config['node']}/status", 'GET', null, $config['host'], $auth);
    
    if ($response && isset($response['data'])) {
        echo json_encode(['success' => true, 'data' => $response['data']]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to get node status']);
    }
}
?>