<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht authentifiziert']);
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$database = Database::getInstance();

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
    
    $sql = "
        SELECT 
            us.*,
            s.name as service_name,
            s.type,
            s.price
        FROM user_services us 
        JOIN services s ON us.service_id = s.id 
        WHERE us.user_id = ? 
        ORDER BY us.created_at DESC
    ";
    
    if ($limit) {
        $sql .= " LIMIT " . $limit;
    }
    
    $stmt = $database->prepare($sql);
    $stmt->execute([$user_id]);
    $services = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'services' => $services
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Laden der Services: ' . $e->getMessage()
    ]);
}
?>