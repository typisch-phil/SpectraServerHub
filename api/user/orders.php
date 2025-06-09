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
    
    // Get user orders
    $stmt = $db->prepare("
        SELECT 
            id,
            service_name,
            total_amount,
            status,
            created_at,
            updated_at
        FROM orders 
        WHERE user_id = ? 
        ORDER BY created_at DESC
        LIMIT 10
    ");
    
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
    
    // Return orders with success flag
    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>