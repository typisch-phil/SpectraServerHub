<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/proxmox.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Check authentication
    if (!$auth->isLoggedIn()) {
        throw new Exception('Anmeldung erforderlich');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['serviceId']) || !isset($input['action'])) {
        throw new Exception('Service-ID und Aktion sind erforderlich');
    }
    
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];
    $serviceId = (int)$input['serviceId'];
    $action = $input['action'];
    
    // Validate action
    if (!in_array($action, ['start', 'stop', 'restart'])) {
        throw new Exception('Ungültige Aktion');
    }
    
    // Get user service
    $stmt = $db->prepare("
        SELECT us.*, s.type
        FROM user_services us
        JOIN services s ON us.service_id = s.id
        WHERE us.id = ? AND us.user_id = ?
    ");
    $stmt->execute([$serviceId, $userId]);
    $userService = $stmt->fetch();
    
    if (!$userService) {
        throw new Exception('Service nicht gefunden');
    }
    
    // Only VPS and game servers can be controlled
    if (!in_array($userService['type'], ['vps', 'gameserver'])) {
        throw new Exception('Dieser Service-Typ kann nicht gesteuert werden');
    }
    
    if (!$userService['proxmox_vmid']) {
        throw new Exception('Kein Server zugewiesen');
    }
    
    // Execute Proxmox action
    $proxmox = new ProxmoxAPI();
    
    if (!$proxmox->authenticate()) {
        throw new Exception('Proxmox-Verbindung fehlgeschlagen');
    }
    
    $result = null;
    $statusMessage = '';
    
    switch ($action) {
        case 'start':
            $result = $proxmox->startVM($userService['proxmox_vmid']);
            $statusMessage = 'Server wird gestartet';
            break;
        case 'stop':
            $result = $proxmox->stopVM($userService['proxmox_vmid']);
            $statusMessage = 'Server wird gestoppt';
            break;
        case 'restart':
            $result = $proxmox->restartVM($userService['proxmox_vmid']);
            $statusMessage = 'Server wird neugestartet';
            break;
    }
    
    if (!$result) {
        throw new Exception('Aktion konnte nicht ausgeführt werden');
    }
    
    echo json_encode([
        'success' => true,
        'message' => $statusMessage
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>