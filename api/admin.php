<?php
// Admin-Dashboard API für SpectraHost
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

// Datenbankverbindung
try {
    $dsn = "mysql:host=37.114.32.205;dbname=s9281_spectrahost;port=3306;charset=utf8mb4";
    $pdo = new PDO($dsn, "s9281_spectrahost", getenv('MYSQL_PASSWORD') ?: '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Admin-Authentifizierung
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGetRequest($pdo, $action);
        break;
    case 'POST':
        handlePostRequest($pdo, $action);
        break;
    case 'PUT':
        handlePutRequest($pdo, $action);
        break;
    case 'DELETE':
        handleDeleteRequest($pdo, $action);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}

function handleGetRequest($pdo, $action) {
    switch ($action) {
        case 'dashboard_stats':
            getDashboardStats($pdo);
            break;
        case 'users':
            getUsers($pdo);
            break;
        case 'services':
            getServices($pdo);
            break;
        case 'orders':
            getOrders($pdo);
            break;
        case 'payments':
            getPayments($pdo);
            break;
        case 'tickets':
            getTickets($pdo);
            break;
        case 'system_status':
            getSystemStatus($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
}

function handlePostRequest($pdo, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'create_service':
            createService($pdo, $input);
            break;
        case 'create_user':
            createUser($pdo, $input);
            break;
        case 'send_notification':
            sendNotification($pdo, $input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
}

function handlePutRequest($pdo, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update_service':
            updateService($pdo, $input);
            break;
        case 'update_user':
            updateUser($pdo, $input);
            break;
        case 'update_order_status':
            updateOrderStatus($pdo, $input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
}

function handleDeleteRequest($pdo, $action) {
    $id = $_GET['id'] ?? '';
    
    switch ($action) {
        case 'delete_service':
            deleteService($pdo, $id);
            break;
        case 'delete_user':
            deleteUser($pdo, $id);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
}

function getDashboardStats($pdo) {
    try {
        $stats = [];
        
        // Benutzerstatistiken
        $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
        $stats['total_users'] = $stmt->fetch()['total_users'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as new_users FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['new_users_30d'] = $stmt->fetch()['new_users'];
        
        // Service-Statistiken
        $stmt = $pdo->query("SELECT COUNT(*) as active_services FROM user_services WHERE status = 'active'");
        $stats['active_services'] = $stmt->fetch()['active_services'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as pending_services FROM user_services WHERE status = 'pending'");
        $stats['pending_services'] = $stmt->fetch()['pending_services'];
        
        // Umsatzstatistiken
        $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total_revenue FROM payments WHERE status = 'completed'");
        $stats['total_revenue'] = $stmt->fetch()['total_revenue'];
        
        $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as monthly_revenue FROM payments WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['monthly_revenue'] = $stmt->fetch()['monthly_revenue'];
        
        // Ticket-Statistiken
        $stmt = $pdo->query("SELECT COUNT(*) as open_tickets FROM tickets WHERE status = 'open'");
        $stats['open_tickets'] = $stmt->fetch()['open_tickets'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as pending_tickets FROM tickets WHERE status = 'pending'");
        $stats['pending_tickets'] = $stmt->fetch()['pending_tickets'];
        
        // Service-Verteilung
        $stmt = $pdo->query("
            SELECT s.type, COUNT(*) as count 
            FROM user_services us 
            JOIN services s ON us.service_id = s.id 
            WHERE us.status = 'active' 
            GROUP BY s.type
        ");
        $stats['service_distribution'] = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $stats]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

function getUsers($pdo) {
    try {
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 50);
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';
        
        $where = '';
        $params = [];
        
        if ($search) {
            $where = "WHERE email LIKE ? OR first_name LIKE ? OR last_name LIKE ?";
            $search_term = "%{$search}%";
            $params = [$search_term, $search_term, $search_term];
        }
        
        $stmt = $pdo->prepare("
            SELECT id, email, first_name, last_name, role, balance, created_at 
            FROM users 
            {$where}
            ORDER BY created_at DESC 
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        
        // Gesamtanzahl für Pagination
        $count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users {$where}");
        $count_stmt->execute($params);
        $total = $count_stmt->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'data' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

function getServices($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT *, 
            (SELECT COUNT(*) FROM user_services WHERE service_id = services.id AND status = 'active') as active_count
            FROM services 
            ORDER BY type, price
        ");
        $services = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $services]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

function getOrders($pdo) {
    try {
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 50);
        $offset = ($page - 1) * $limit;
        $status = $_GET['status'] ?? '';
        
        $where = '';
        $params = [];
        
        if ($status) {
            $where = "WHERE us.status = ?";
            $params = [$status];
        }
        
        $stmt = $pdo->prepare("
            SELECT us.*, s.name as service_name, s.type as service_type, s.price,
                   u.email as user_email, u.first_name, u.last_name
            FROM user_services us
            JOIN services s ON us.service_id = s.id
            JOIN users u ON us.user_id = u.id
            {$where}
            ORDER BY us.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $orders = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $orders]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

function getPayments($pdo) {
    try {
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 50);
        $offset = ($page - 1) * $limit;
        
        $stmt = $pdo->prepare("
            SELECT p.*, u.email as user_email, u.first_name, u.last_name
            FROM payments p
            JOIN users u ON p.user_id = u.id
            ORDER BY p.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute();
        $payments = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $payments]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

function getTickets($pdo) {
    try {
        $status = $_GET['status'] ?? '';
        $where = '';
        $params = [];
        
        if ($status) {
            $where = "WHERE t.status = ?";
            $params = [$status];
        }
        
        $stmt = $pdo->prepare("
            SELECT t.*, u.email as user_email, u.first_name, u.last_name
            FROM tickets t
            JOIN users u ON t.user_id = u.id
            {$where}
            ORDER BY t.created_at DESC
            LIMIT 100
        ");
        $stmt->execute($params);
        $tickets = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $tickets]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

function createService($pdo, $input) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO services (name, description, type, price, features, active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $input['name'],
            $input['description'],
            $input['type'],
            $input['price'],
            json_encode($input['features'] ?? []),
            $input['active'] ?? 1
        ]);
        
        $service_id = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'service_id' => $service_id]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

function updateService($pdo, $input) {
    try {
        $stmt = $pdo->prepare("
            UPDATE services 
            SET name = ?, description = ?, type = ?, price = ?, features = ?, active = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $input['name'],
            $input['description'],
            $input['type'],
            $input['price'],
            json_encode($input['features'] ?? []),
            $input['active'] ?? 1,
            $input['id']
        ]);
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

function updateOrderStatus($pdo, $input) {
    try {
        $stmt = $pdo->prepare("UPDATE user_services SET status = ? WHERE id = ?");
        $stmt->execute([$input['status'], $input['id']]);
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

function getSystemStatus($pdo) {
    try {
        $status = [
            'database' => 'online',
            'proxmox' => checkProxmoxStatus(),
            'mollie' => checkMollieStatus(),
            'disk_space' => getDiskSpace(),
            'memory_usage' => getMemoryUsage(),
            'php_version' => phpversion(),
            'uptime' => getSystemUptime()
        ];
        
        echo json_encode(['success' => true, 'data' => $status]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'System status check failed']);
    }
}

function checkProxmoxStatus() {
    $host = '45.137.68.202';
    $fp = @fsockopen($host, 8006, $errno, $errstr, 5);
    if ($fp) {
        fclose($fp);
        return 'online';
    }
    return 'offline';
}

function checkMollieStatus() {
    $mollie_key = getenv('MOLLIE_API_KEY');
    if (!$mollie_key) return 'not_configured';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.mollie.com/v2/methods');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $mollie_key]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($http_code === 200) ? 'online' : 'offline';
}

function getDiskSpace() {
    $total = disk_total_space('/');
    $free = disk_free_space('/');
    $used = $total - $free;
    
    return [
        'total' => $total,
        'used' => $used,
        'free' => $free,
        'percentage' => round(($used / $total) * 100, 2)
    ];
}

function getMemoryUsage() {
    $memory = memory_get_usage(true);
    $peak = memory_get_peak_usage(true);
    
    return [
        'current' => $memory,
        'peak' => $peak,
        'limit' => ini_get('memory_limit')
    ];
}

function getSystemUptime() {
    if (file_exists('/proc/uptime')) {
        $uptime = file_get_contents('/proc/uptime');
        $uptime = floatval(explode(' ', $uptime)[0]);
        return $uptime;
    }
    return null;
}
?>