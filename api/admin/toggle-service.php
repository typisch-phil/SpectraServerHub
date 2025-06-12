<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';

// Admin-Authentifizierung überprüfen
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['service_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing service ID']);
    exit;
}

$serviceId = (int)$input['service_id'];

try {
    // Aktuellen Status abrufen
    $stmt = $db->prepare("SELECT is_active FROM service_types WHERE id = ?");
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch();
    
    if (!$service) {
        http_response_code(404);
        echo json_encode(['error' => 'Service not found']);
        exit;
    }
    
    // Status umschalten
    $newStatus = $service['is_active'] ? 0 : 1;
    
    $stmt = $db->prepare("UPDATE service_types SET is_active = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newStatus, $serviceId]);
    
    $statusText = $newStatus ? 'aktiviert' : 'deaktiviert';
    echo json_encode([
        'success' => true, 
        'message' => "Service erfolgreich $statusText",
        'new_status' => $newStatus
    ]);
    
} catch (Exception $e) {
    error_log("Toggle service error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>