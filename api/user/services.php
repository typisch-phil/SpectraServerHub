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
    
    // Get user services with service details
    $stmt = $db->prepare("
        SELECT 
            us.*, 
            s.name as service_name,
            s.type as service_type,
            s.cpu_cores,
            s.memory_gb,
            s.storage_gb,
            s.bandwidth_gb
        FROM user_services us
        JOIN services s ON us.service_id = s.id
        WHERE us.user_id = ?
        ORDER BY us.created_at DESC
    ");
    $stmt->execute([$userId]);
    $services = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'services' => $services
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>