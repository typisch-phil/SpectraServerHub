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

$requiredFields = ['service_id', 'name', 'category', 'monthly_price'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing field: $field"]);
        exit;
    }
}

$serviceId = (int)$input['service_id'];
$name = trim($input['name']);
$description = trim($input['description'] ?? '');
$category = trim($input['category']);
$monthlyPrice = (float)$input['monthly_price'];
$isActive = isset($input['is_active']) ? (int)$input['is_active'] : 0;

// Validierung
if (empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Service name cannot be empty']);
    exit;
}

if ($monthlyPrice < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Price cannot be negative']);
    exit;
}

$allowedCategories = ['webspace', 'vserver', 'gameserver', 'domain'];
if (!in_array($category, $allowedCategories)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid category']);
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
    
    // Service aktualisieren
    $stmt = $db->prepare("
        UPDATE service_types 
        SET name = ?, description = ?, category = ?, monthly_price = ?, is_active = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $name,
        $description,
        $category,
        $monthlyPrice,
        $isActive,
        $serviceId
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Service successfully updated']);
    
} catch (Exception $e) {
    error_log("Update service error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>