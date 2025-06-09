<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

try {
    // Check authentication
    if (!$auth->isLoggedIn()) {
        throw new Exception('Anmeldung erforderlich');
    }
    
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];
    
    // Get user orders with service details
    $stmt = $db->prepare("
        SELECT 
            o.*, 
            s.name as service_name,
            s.type as service_type
        FROM orders o
        JOIN services s ON o.service_id = s.id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>