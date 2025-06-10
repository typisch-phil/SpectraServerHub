<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM services WHERE active = 1 ORDER BY name ASC");
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format services for frontend
    $formatted_services = array_map(function($service) {
        return [
            'id' => (int)$service['id'],
            'name' => $service['name'],
            'description' => $service['description'],
            'type' => $service['type'],
            'price' => (float)$service['price'],
            'cpu_cores' => (int)$service['cpu_cores'],
            'memory_gb' => (int)$service['memory_gb'],
            'storage_gb' => (int)$service['storage_gb'],
            'bandwidth_gb' => (int)$service['bandwidth_gb'],
            'active' => (bool)$service['active']
        ];
    }, $services);
    
    echo json_encode([
        'success' => true,
        'services' => $formatted_services
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fehler beim Laden der Services'
    ]);
}
?>