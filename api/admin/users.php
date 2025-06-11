<?php
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

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all users or specific user
            if (isset($_GET['id'])) {
                $stmt = $db->prepare("SELECT id, email, first_name, last_name, role, balance, created_at, updated_at FROM users WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                $userRecord = $stmt->fetch();
                
                if (!$userRecord) {
                    http_response_code(404);
                    echo json_encode(['error' => 'User not found']);
                    exit;
                }
                
                echo json_encode(['success' => true, 'user' => $userRecord]);
            } else {
                // Get all users with optional filtering
                $search = $_GET['search'] ?? '';
                $role = $_GET['role'] ?? '';
                
                $query = "SELECT id, email, first_name, last_name, role, balance, created_at, updated_at FROM users WHERE 1=1";
                $params = [];
                
                if (!empty($search)) {
                    $query .= " AND (email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
                    $searchTerm = "%$search%";
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                }
                
                if (!empty($role) && in_array($role, ['admin', 'customer'])) {
                    $query .= " AND role = ?";
                    $params[] = $role;
                }
                
                $query .= " ORDER BY created_at DESC";
                
                $users = $db->fetchAll($query, $params);
                
                echo json_encode(['success' => true, 'users' => $users]);
            }
            break;
            
        case 'POST':
            // Create new user
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['email']) || !isset($input['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Email and password are required']);
                exit;
            }
            
            // Validate email
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid email format']);
                exit;
            }
            
            // Check if email already exists
            $existingUser = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$input['email']]);
            if ($existingUser) {
                http_response_code(409);
                echo json_encode(['error' => 'Email already exists']);
                exit;
            }
            
            // Create user
            $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
            $db->execute("
                INSERT INTO users (email, password, first_name, last_name, role, balance, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ", [
                $input['email'],
                $hashedPassword,
                $input['first_name'] ?? '',
                $input['last_name'] ?? '',
                $input['role'] ?? 'customer',
                $input['balance'] ?? 0.00
            ]);
            
            $userId = $db->lastInsertId();
            
            // Get created user
            $newUser = $db->fetchOne("SELECT id, email, first_name, last_name, role, balance, created_at FROM users WHERE id = ?", [$userId]);
            
            echo json_encode(['success' => true, 'user' => $newUser, 'message' => 'User created successfully']);
            break;
            
        case 'PUT':
            // Update user
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'User ID is required']);
                exit;
            }
            
            // Build update query dynamically
            $updateFields = [];
            $params = [];
            
            if (isset($input['email'])) {
                if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid email format']);
                    exit;
                }
                
                // Check if email already exists for another user
                $existingUser = $db->fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$input['email'], $input['id']]);
                if ($existingUser) {
                    http_response_code(409);
                    echo json_encode(['error' => 'Email already exists']);
                    exit;
                }
                
                $updateFields[] = "email = ?";
                $params[] = $input['email'];
            }
            
            if (isset($input['first_name'])) {
                $updateFields[] = "first_name = ?";
                $params[] = $input['first_name'];
            }
            
            if (isset($input['last_name'])) {
                $updateFields[] = "last_name = ?";
                $params[] = $input['last_name'];
            }
            
            if (isset($input['role']) && in_array($input['role'], ['admin', 'customer'])) {
                $updateFields[] = "role = ?";
                $params[] = $input['role'];
            }
            
            if (isset($input['balance'])) {
                $updateFields[] = "balance = ?";
                $params[] = $input['balance'];
            }
            
            if (isset($input['password']) && !empty($input['password'])) {
                $updateFields[] = "password = ?";
                $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
            }
            
            if (empty($updateFields)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update']);
                exit;
            }
            
            $updateFields[] = "updated_at = NOW()";
            $params[] = $input['id'];
            
            $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $rowsAffected = $db->execute($query, $params);
            
            if ($rowsAffected === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                exit;
            }
            
            // Get updated user
            $updatedUser = $db->fetchOne("SELECT id, email, first_name, last_name, role, balance, updated_at FROM users WHERE id = ?", [$input['id']]);
            
            echo json_encode(['success' => true, 'user' => $updatedUser, 'message' => 'User updated successfully']);
            break;
            
        case 'DELETE':
            // Delete user
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'User ID is required']);
                exit;
            }
            
            // Prevent deleting admin users
            $userToDelete = $db->fetchOne("SELECT role FROM users WHERE id = ?", [$input['id']]);
            
            if (!$userToDelete) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                exit;
            }
            
            if ($userToDelete['role'] === 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Cannot delete admin users']);
                exit;
            }
            
            // Check if user has active services
            $activeServices = $db->fetchOne("SELECT COUNT(*) as count FROM user_services WHERE user_id = ? AND status = 'active'", [$input['id']]);
            
            if ($activeServices['count'] > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'Cannot delete user with active services']);
                exit;
            }
            
            // Soft delete - mark as deleted instead of actual deletion
            $rowsAffected = $db->execute("UPDATE users SET deleted_at = NOW(), updated_at = NOW() WHERE id = ?", [$input['id']]);
            
            if ($rowsAffected === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                exit;
            }
            
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    error_log('Admin Users API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>