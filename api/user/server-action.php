<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/proxmox.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Parse URL to get service ID and action
$uri = $_SERVER['REQUEST_URI'];
$parts = explode('/', $uri);
$service_id = (int)$parts[4]; // /api/user/services/{id}/{action}
$action = $parts[5];

session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht authentifiziert']);
    exit;
}

$user = $_SESSION['user'];
$database = Database::getInstance();

try {
    // Verify user owns this service
    $stmt = $database->prepare("SELECT * FROM user_services WHERE id = ? AND user_id = ?");
    $stmt->execute([$service_id, $user['id']]);
    $service = $stmt->fetch();
    
    if (!$service) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Service nicht gefunden']);
        exit;
    }
    
    if ($service['status'] !== 'active' || !$service['proxmox_vmid']) {
        throw new Exception('Service ist nicht für Server-Aktionen verfügbar');
    }
    
    // Execute Proxmox action
    $proxmox = new ProxmoxAPI();
    $result = false;
    
    switch ($action) {
        case 'start':
            $result = $proxmox->startVM($service['proxmox_vmid']);
            break;
        case 'stop':
            $result = $proxmox->stopVM($service['proxmox_vmid']);
            break;
        case 'restart':
            $result = $proxmox->restartVM($service['proxmox_vmid']);
            break;
        case 'reset-password':
            $new_password = bin2hex(random_bytes(8));
            $result = $proxmox->resetPassword($service['proxmox_vmid'], $new_password);
            if ($result) {
                // Update password in database
                $stmt = $database->prepare("UPDATE user_services SET root_password = ? WHERE id = ?");
                $stmt->execute([$new_password, $service_id]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Passwort erfolgreich zurückgesetzt',
                    'new_password' => $new_password
                ]);
                exit;
            }
            break;
        default:
            throw new Exception('Unbekannte Aktion');
    }
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Aktion erfolgreich ausgeführt'
        ]);
    } else {
        throw new Exception('Fehler beim Ausführen der Aktion');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>