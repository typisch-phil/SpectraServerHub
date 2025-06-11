<?php
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

// Check authentication
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

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $service = $db->fetchOne("SELECT * FROM service_types WHERE id = ?", [$_GET['id']]);
                
                if (!$service) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Service not found']);
                    exit;
                }
                
                // Parse JSON specifications
                if ($service['specifications']) {
                    $service['specifications'] = json_decode($service['specifications'], true);
                }
                
                echo json_encode(['success' => true, 'service' => $service]);
            } else {
                // Get all services with filtering
                $category = $_GET['category'] ?? '';
                $active = $_GET['active'] ?? '';
                
                $query = "SELECT * FROM service_types WHERE 1=1";
                $params = [];
                
                if (!empty($category)) {
                    $query .= " AND category = ?";
                    $params[] = $category;
                }
                
                if ($active !== '') {
                    $query .= " AND is_active = ?";
                    $params[] = $active === 'true' ? 1 : 0;
                }
                
                $query .= " ORDER BY category, name";
                $services = $db->fetchAll($query, $params);
                
                // Parse JSON specifications for each service
                foreach ($services as &$service) {
                    if ($service['specifications']) {
                        $service['specifications'] = json_decode($service['specifications'], true);
                    }
                }
                
                echo json_encode(['success' => true, 'services' => $services]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['name']) || !isset($input['category'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Name and category are required']);
                exit;
            }
            
            // Validate category
            $validCategories = ['webspace', 'vserver', 'gameserver', 'domain'];
            if (!in_array($input['category'], $validCategories)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid category']);
                exit;
            }
            
            $specifications = null;
            if (isset($input['specifications'])) {
                $specifications = json_encode($input['specifications']);
            }
            
            $db->execute("
                INSERT INTO service_types (name, category, description, monthly_price, specifications, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ", [
                $input['name'],
                $input['category'],
                $input['description'] ?? '',
                $input['monthly_price'] ?? 0.00,
                $specifications,
                $input['is_active'] ?? 1
            ]);
            
            $serviceId = $db->lastInsertId();
            $newService = $db->fetchOne("SELECT * FROM service_types WHERE id = ?", [$serviceId]);
            
            if ($newService['specifications']) {
                $newService['specifications'] = json_decode($newService['specifications'], true);
            }
            
            echo json_encode(['success' => true, 'service' => $newService, 'message' => 'Service created successfully']);
            break;
            
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Service ID is required']);
                exit;
            }
            
            // Build update query
            $updateFields = [];
            $params = [];
            
            if (isset($input['name'])) {
                $updateFields[] = "name = ?";
                $params[] = $input['name'];
            }
            
            if (isset($input['category'])) {
                $validCategories = ['webspace', 'vserver', 'gameserver', 'domain'];
                if (!in_array($input['category'], $validCategories)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid category']);
                    exit;
                }
                $updateFields[] = "category = ?";
                $params[] = $input['category'];
            }
            
            if (isset($input['description'])) {
                $updateFields[] = "description = ?";
                $params[] = $input['description'];
            }
            
            if (isset($input['monthly_price'])) {
                $updateFields[] = "monthly_price = ?";
                $params[] = $input['monthly_price'];
            }
            
            if (isset($input['specifications'])) {
                $updateFields[] = "specifications = ?";
                $params[] = json_encode($input['specifications']);
            }
            
            if (isset($input['is_active'])) {
                $updateFields[] = "is_active = ?";
                $params[] = $input['is_active'] ? 1 : 0;
            }
            
            if (empty($updateFields)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update']);
                exit;
            }
            
            $updateFields[] = "updated_at = NOW()";
            $params[] = $input['id'];
            
            $query = "UPDATE service_types SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $rowsAffected = $db->execute($query, $params);
            
            if ($rowsAffected === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Service not found']);
                exit;
            }
            
            $updatedService = $db->fetchOne("SELECT * FROM service_types WHERE id = ?", [$input['id']]);
            if ($updatedService['specifications']) {
                $updatedService['specifications'] = json_decode($updatedService['specifications'], true);
            }
            
            echo json_encode(['success' => true, 'service' => $updatedService, 'message' => 'Service updated successfully']);
            break;
            
        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Service ID is required']);
                exit;
            }
            
            // Check if service has active user services
            $activeUserServices = $db->fetchOne("SELECT COUNT(*) as count FROM user_services WHERE service_id = ? AND status = 'active'", [$input['id']]);
            
            if ($activeUserServices['count'] > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'Cannot delete service with active user services']);
                exit;
            }
            
            $rowsAffected = $db->execute("DELETE FROM service_types WHERE id = ?", [$input['id']]);
            
            if ($rowsAffected === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Service not found']);
                exit;
            }
            
            echo json_encode(['success' => true, 'message' => 'Service deleted successfully']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    error_log('Admin Services API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>