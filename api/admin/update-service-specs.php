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

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

if (!isset($input['service_id']) || !isset($input['specifications'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$serviceId = (int)$input['service_id'];
$specifications = trim($input['specifications']);

// JSON-Validierung
$decodedSpecs = json_decode($specifications, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON format in specifications']);
    exit;
}

try {
    // Service existiert überprüfen
    $stmt = $db->prepare("SELECT id FROM service_types WHERE id = ?");
    $stmt->execute([$serviceId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Service not found']);
        exit;
    }
    
    // Spezifikationen aktualisieren
    $stmt = $db->prepare("
        UPDATE service_types 
        SET specifications = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$specifications, $serviceId]);
    
    echo json_encode(['success' => true, 'message' => 'Specifications successfully updated']);
    
} catch (Exception $e) {
    error_log("Update service specs error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>