<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

try {
    $db = Database::getInstance();
    
    // Get all active services
    $stmt = $db->prepare("SELECT * FROM services WHERE active = 1 ORDER BY type, price");
    $stmt->execute();
    $services = $stmt->fetchAll();
    
    // Decode JSON features for each service
    foreach ($services as &$service) {
        $service['features'] = json_decode($service['features'], true);
    }
    
    echo json_encode([
        'success' => true,
        'services' => $services
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fehler beim Laden der Services'
    ]);
}
?>