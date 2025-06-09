<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - Admin access required']);
    exit;
}

$database = Database::getInstance();
$pdo = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all services or specific service
            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                $service = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$service) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Service not found']);
                    exit;
                }
                
                // Parse JSON features
                if ($service['features']) {
                    $service['features'] = json_decode($service['features'], true);
                }
                
                echo json_encode(['success' => true, 'service' => $service]);
            } else {
                // Get all services with optional filtering
                $type = $_GET['type'] ?? '';
                $active = $_GET['active'] ?? '';
                
                $query = "SELECT * FROM services WHERE 1=1";
                $params = [];
                
                if (!empty($type) && in_array($type, ['webspace', 'vserver', 'gameserver', 'domain'])) {
                    $query .= " AND type = ?";
                    $params[] = $type;
                }
                
                if ($active !== '') {
                    $query .= " AND active = ?";
                    $params[] = $active === 'true' ? true : false;
                }
                
                $query .= " ORDER BY type, name";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Parse JSON features for all services
                foreach ($services as &$service) {
                    if ($service['features']) {
                        $service['features'] = json_decode($service['features'], true);
                    }
                }
                
                echo json_encode(['success' => true, 'services' => $services]);
            }
            break;
            
        case 'POST':
            // Create new service
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['name']) || !isset($input['type']) || !isset($input['price'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Name, type and price are required']);
                exit;
            }
            
            // Validate service type
            if (!in_array($input['type'], ['webspace', 'vserver', 'gameserver', 'domain'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid service type']);
                exit;
            }
            
            // Validate price
            if (!is_numeric($input['price']) || floatval($input['price']) < 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid price']);
                exit;
            }
            
            // Handle features array
            $features = isset($input['features']) && is_array($input['features']) 
                ? json_encode($input['features']) 
                : json_encode([]);
            
            // Create service
            $stmt = $pdo->prepare("
                INSERT INTO services (name, type, description, price, features, cpu_cores, memory_gb, storage_gb, bandwidth_gb, active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $input['name'],
                $input['type'],
                $input['description'] ?? '',
                floatval($input['price']),
                $features,
                intval($input['cpu_cores'] ?? 1),
                intval($input['memory_gb'] ?? 1),
                intval($input['storage_gb'] ?? 10),
                intval($input['bandwidth_gb'] ?? 1000),
                isset($input['active']) ? (bool)$input['active'] : true
            ]);
            
            $serviceId = $pdo->lastInsertId();
            
            // Get created service
            $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
            $stmt->execute([$serviceId]);
            $newService = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($newService['features']) {
                $newService['features'] = json_decode($newService['features'], true);
            }
            
            echo json_encode(['success' => true, 'service' => $newService, 'message' => 'Service created successfully']);
            break;
            
        case 'PUT':
            // Update service
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Service ID is required']);
                exit;
            }
            
            // Build update query dynamically
            $updateFields = [];
            $params = [];
            
            if (isset($input['name'])) {
                $updateFields[] = "name = ?";
                $params[] = $input['name'];
            }
            
            if (isset($input['type']) && in_array($input['type'], ['webspace', 'vserver', 'gameserver', 'domain'])) {
                $updateFields[] = "type = ?";
                $params[] = $input['type'];
            }
            
            if (isset($input['description'])) {
                $updateFields[] = "description = ?";
                $params[] = $input['description'];
            }
            
            if (isset($input['price']) && is_numeric($input['price']) && floatval($input['price']) >= 0) {
                $updateFields[] = "price = ?";
                $params[] = floatval($input['price']);
            }
            
            if (isset($input['features']) && is_array($input['features'])) {
                $updateFields[] = "features = ?";
                $params[] = json_encode($input['features']);
            }
            
            if (isset($input['cpu_cores']) && is_numeric($input['cpu_cores'])) {
                $updateFields[] = "cpu_cores = ?";
                $params[] = intval($input['cpu_cores']);
            }
            
            if (isset($input['memory_gb']) && is_numeric($input['memory_gb'])) {
                $updateFields[] = "memory_gb = ?";
                $params[] = intval($input['memory_gb']);
            }
            
            if (isset($input['storage_gb']) && is_numeric($input['storage_gb'])) {
                $updateFields[] = "storage_gb = ?";
                $params[] = intval($input['storage_gb']);
            }
            
            if (isset($input['bandwidth_gb']) && is_numeric($input['bandwidth_gb'])) {
                $updateFields[] = "bandwidth_gb = ?";
                $params[] = intval($input['bandwidth_gb']);
            }
            
            if (isset($input['active'])) {
                $updateFields[] = "active = ?";
                $params[] = (bool)$input['active'];
            }
            
            if (empty($updateFields)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update']);
                exit;
            }
            
            $params[] = $input['id'];
            
            $query = "UPDATE services SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Service not found']);
                exit;
            }
            
            // Get updated service
            $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
            $stmt->execute([$input['id']]);
            $updatedService = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($updatedService['features']) {
                $updatedService['features'] = json_decode($updatedService['features'], true);
            }
            
            echo json_encode(['success' => true, 'service' => $updatedService, 'message' => 'Service updated successfully']);
            break;
            
        case 'DELETE':
            // Delete service
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Service ID is required']);
                exit;
            }
            
            // Check if service has active user subscriptions
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_services WHERE service_id = ? AND status = 'active'");
            $stmt->execute([$input['id']]);
            $activeSubscriptions = $stmt->fetch();
            
            if ($activeSubscriptions['count'] > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'Cannot delete service with active subscriptions']);
                exit;
            }
            
            // Soft delete - mark as inactive instead of actual deletion
            $stmt = $pdo->prepare("UPDATE services SET active = false WHERE id = ?");
            $stmt->execute([$input['id']]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Service not found']);
                exit;
            }
            
            echo json_encode(['success' => true, 'message' => 'Service deactivated successfully']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Error in admin services API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>