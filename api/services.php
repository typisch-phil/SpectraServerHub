<?php
// Plesk-kompatible API für Services
require_once __DIR__ . '/../includes/plesk-config.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . getBaseUrl());
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    $stmt = $connection->prepare("SELECT * FROM services WHERE active = 1 ORDER BY name ASC");
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