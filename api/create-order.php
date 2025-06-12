<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

// Benutzer muss eingeloggt sein
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

if (!isset($input['service_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing service_id']);
    exit;
}

$db = Database::getInstance();

try {
    // Service-Details abrufen
    $stmt = $db->prepare("SELECT * FROM service_types WHERE id = ? AND is_active = 1");
    $stmt->execute([$input['service_id']]);
    $service = $stmt->fetch();
    
    if (!$service) {
        http_response_code(404);
        echo json_encode(['error' => 'Service not found']);
        exit;
    }
    
    // Preisberechnung
    $basePrice = $service['monthly_price'];
    $totalPrice = $basePrice;
    
    // Zusätzliche Services
    if (!empty($input['backup_service'])) {
        $totalPrice += 5;
    }
    if (!empty($input['monitoring'])) {
        $totalPrice += 3;
    }
    if (!empty($input['operating_system']) && strpos($input['operating_system'], 'windows') !== false) {
        $totalPrice += 15;
    }
    
    // Rabatt für längere Vertragslaufzeiten
    $contractPeriod = (int)($input['contract_period'] ?? 1);
    $discount = 0;
    if ($contractPeriod === 12) {
        $discount = 0.1; // 10%
    } elseif ($contractPeriod === 24) {
        $discount = 0.2; // 20%
    }
    
    $discountedPrice = $totalPrice * (1 - $discount);
    
    // Bestellkonfiguration zusammenstellen
    $orderConfig = [
        'service_type' => $service['category'],
        'operating_system' => $input['operating_system'] ?? null,
        'server_name' => $input['server_name'] ?? null,
        'root_password' => $input['root_password'] ?? null,
        'domain_name' => $input['domain_name'] ?? null,
        'domain_extension' => $input['domain_extension'] ?? null,
        'game_type' => $input['game_type'] ?? null,
        'backup_service' => !empty($input['backup_service']),
        'monitoring' => !empty($input['monitoring']),
        'setup_service' => !empty($input['setup_service']),
        'contract_period' => $contractPeriod,
        'base_price' => $basePrice,
        'total_price' => $totalPrice,
        'discounted_price' => $discountedPrice,
        'discount_percent' => $discount * 100
    ];
    
    // Bestellung in Datenbank speichern
    $stmt = $db->prepare("
        INSERT INTO orders (user_id, service_type_id, configuration, monthly_price, status, created_at) 
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        $service['id'],
        json_encode($orderConfig),
        $discountedPrice
    ]);
    
    $orderId = $db->lastInsertId();
    
    // Benutzer-Balance überprüfen (falls implementiert)
    $stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    $response = [
        'success' => true,
        'order_id' => $orderId,
        'message' => 'Bestellung erfolgreich erstellt',
        'total_price' => $discountedPrice,
        'config' => $orderConfig
    ];
    
    // Wenn Guthaben vorhanden ist, sofort aktivieren
    if ($user && $user['balance'] >= $discountedPrice) {
        // Balance abziehen
        $stmt = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$discountedPrice, $_SESSION['user_id']]);
        
        // Bestellung aktivieren
        $stmt = $db->prepare("UPDATE orders SET status = 'active', activated_at = NOW() WHERE id = ?");
        $stmt->execute([$orderId]);
        
        $response['auto_activated'] = true;
        $response['message'] = 'Bestellung erfolgreich erstellt und aktiviert';
    } else {
        $response['payment_required'] = true;
        $response['message'] = 'Bestellung erstellt - Zahlung erforderlich';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Create order error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>