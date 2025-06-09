<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get database instance
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get user ID from session
    $user = getCurrentUser();
    $user_id = $user ? $user['id'] : null;
    
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    // Get user services with service details
    $stmt = $db->prepare("
        SELECT 
            us.id,
            us.server_name,
            us.status,
            us.expires_at,
            us.created_at,
            us.updated_at,
            s.name as service_name,
            s.cpu_cores,
            s.memory_gb,
            s.storage_gb,
            s.price as total_amount
        FROM user_services us
        INNER JOIN services s ON us.service_id = s.id
        WHERE us.user_id = ? 
        ORDER BY us.created_at DESC
    ");
    
    $stmt->execute([$user_id]);
    $services = $stmt->fetchAll();
    
    // Return services with success flag
    echo json_encode([
        'success' => true,
        'services' => $services
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>