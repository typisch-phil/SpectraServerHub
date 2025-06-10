<?php
// Zentrale API-Router für Plesk-Live-Umgebung
require_once __DIR__ . '/../includes/plesk-config.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . getBaseUrl());
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get API endpoint from URL
$request_uri = $_SERVER['REQUEST_URI'];
$api_path = str_replace('/api/', '', parse_url($request_uri, PHP_URL_PATH));
$api_path = trim($api_path, '/');

// Parse endpoint and parameters
$parts = explode('/', $api_path);
$endpoint = $parts[0] ?? '';
$action = $parts[1] ?? '';
$id = $parts[2] ?? '';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Route API requests
    switch ($endpoint) {
        case 'services':
            handleServicesAPI($connection, $action, $id);
            break;
            
        case 'auth':
            handleAuthAPI($connection, $action);
            break;
            
        case 'login-new':
            require_once __DIR__ . '/login-new.php';
            exit;
            
        case 'register-new':
            require_once __DIR__ . '/register-new.php';
            exit;
            
        case 'user-status':
            require_once __DIR__ . '/user-status.php';
            exit;
            
        case 'user':
            handleUserAPI($connection, $action, $id);
            break;
            
        case 'orders':
            handleOrdersAPI($connection, $action, $id);
            break;
            
        case 'payments':
            handlePaymentsAPI($connection, $action, $id);
            break;
            
        case 'tickets':
            handleTicketsAPI($connection, $action, $id);
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'API endpoint not found',
                'available_endpoints' => ['services', 'auth', 'user', 'orders', 'payments', 'tickets']
            ]);
            break;
    }
    
} catch (Exception $e) {
    pleskLog("API Error: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}

// Services API Handler
function handleServicesAPI($connection, $action, $id) {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($id) {
                // Get specific service
                $stmt = $connection->prepare("SELECT * FROM services WHERE id = ? AND active = 1");
                $stmt->execute([$id]);
                $service = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($service) {
                    echo json_encode(['success' => true, 'data' => $service]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Service not found']);
                }
            } else {
                // Get all services
                $type_filter = $_GET['type'] ?? null;
                $sql = "SELECT * FROM services WHERE active = 1";
                $params = [];
                
                if ($type_filter) {
                    $sql .= " AND type = ?";
                    $params[] = $type_filter;
                }
                
                $sql .= " ORDER BY type, price ASC";
                $stmt = $connection->prepare($sql);
                $stmt->execute($params);
                $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $services]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
}

// Auth API Handler
function handleAuthAPI($connection, $action) {
    switch ($action) {
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            
            if (!isset($input['email']) || !isset($input['password'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Email and password required']);
                return;
            }
            
            $stmt = $connection->prepare("SELECT id, email, password, first_name, last_name, role, balance FROM users WHERE email = ?");
            $stmt->execute([$input['email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($input['password'], $user['password'])) {
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user'] = $user;
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'name' => $user['first_name'] . ' ' . $user['last_name'],
                        'role' => $user['role']
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
            }
            break;
            
        case 'logout':
            session_start();
            session_destroy();
            echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
            break;
            
        case 'user':
            session_start();
            if (isset($_SESSION['user_id'])) {
                echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Auth endpoint not found']);
            break;
    }
}

// User API Handler
function handleUserAPI($connection, $action, $id) {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        return;
    }
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $stmt = $connection->prepare("SELECT id, email, first_name, last_name, role, balance FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo json_encode(['success' => true, 'data' => $user]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'User not found']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
}

// Orders API Handler
function handleOrdersAPI($connection, $action, $id) {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        return;
    }
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $stmt = $connection->prepare("
                SELECT us.*, s.name as service_name, s.type as service_type, s.price 
                FROM user_services us 
                JOIN services s ON us.service_id = s.id 
                WHERE us.user_id = ? 
                ORDER BY us.created_at DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $orders]);
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            
            if (!isset($input['service_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Service ID required']);
                return;
            }
            
            // Create new order
            $stmt = $connection->prepare("
                INSERT INTO user_services (user_id, service_id, domain, status) 
                VALUES (?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $input['service_id'],
                $input['domain'] ?? null
            ]);
            
            $order_id = $connection->lastInsertId();
            echo json_encode(['success' => true, 'order_id' => $order_id]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
}

// Payments API Handler
function handlePaymentsAPI($connection, $action, $id) {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        return;
    }
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $stmt = $connection->prepare("
                SELECT * FROM payments 
                WHERE user_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $payments]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
}

// Tickets API Handler
function handleTicketsAPI($connection, $action, $id) {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        return;
    }
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($id) {
                // Get specific ticket with replies
                $stmt = $connection->prepare("
                    SELECT t.*, tr.id as reply_id, tr.message as reply_message, 
                           tr.created_at as reply_created, tr.is_internal,
                           u.first_name, u.last_name 
                    FROM tickets t 
                    LEFT JOIN ticket_replies tr ON t.id = tr.ticket_id 
                    LEFT JOIN users u ON tr.user_id = u.id 
                    WHERE t.id = ? AND t.user_id = ? 
                    ORDER BY tr.created_at ASC
                ");
                $stmt->execute([$id, $_SESSION['user_id']]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($results) {
                    $ticket = [
                        'id' => $results[0]['id'],
                        'subject' => $results[0]['subject'],
                        'message' => $results[0]['message'],
                        'status' => $results[0]['status'],
                        'priority' => $results[0]['priority'],
                        'category' => $results[0]['category'],
                        'created_at' => $results[0]['created_at'],
                        'replies' => []
                    ];
                    
                    foreach ($results as $row) {
                        if ($row['reply_id']) {
                            $ticket['replies'][] = [
                                'id' => $row['reply_id'],
                                'message' => $row['reply_message'],
                                'created_at' => $row['reply_created'],
                                'is_internal' => $row['is_internal'],
                                'author' => $row['first_name'] . ' ' . $row['last_name']
                            ];
                        }
                    }
                    
                    echo json_encode(['success' => true, 'data' => $ticket]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Ticket not found']);
                }
            } else {
                // Get all tickets for user
                $stmt = $connection->prepare("
                    SELECT * FROM tickets 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $tickets]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            
            if (!isset($input['subject']) || !isset($input['message'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Subject and message required']);
                return;
            }
            
            $stmt = $connection->prepare("
                INSERT INTO tickets (user_id, subject, message, priority, category, status) 
                VALUES (?, ?, ?, ?, ?, 'open')
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $input['subject'],
                $input['message'],
                $input['priority'] ?? 'medium',
                $input['category'] ?? 'general'
            ]);
            
            $ticket_id = $connection->lastInsertId();
            echo json_encode(['success' => true, 'ticket_id' => $ticket_id]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
}
?>